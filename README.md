# Gestione Menu — Fattoria Maria

Web app per gestire i menu stagionali (con allergeni) al posto del Google Sheet, con export per
il controllo automatico e per l'impaginato InDesign.

Stack: **PHP 8.x + MySQL/MariaDB**, nessun framework, nessuna build JS (SortableJS caricato da CDN
per il drag&drop). Pensata per girare su un hosting Plesk con PHP/MySQL come quello già in uso.

## Requisiti sul server

- PHP 8.1+ con estensioni: `pdo_mysql`, `gd`, `mbstring`, `exif`, `fileinfo` (tutte comuni su Plesk).
- MySQL o MariaDB.
- Apache con `mod_rewrite` (Plesk lo abilita di default).

## Struttura del progetto

```
public/            document root da puntare su Plesk (index.php, assets, uploads foto)
src/                codice applicativo (Controllers, Repositories, Services, Support)
views/              template PHP
migrations/schema.sql  schema del database + seed allergeni/impostazioni
cron/               script CLI (creazione automatica stagione, creazione utenti)
config/             configurazione (config.local.php da creare, non versionato)
```

---

## 0. Aggiornare un'installazione già esistente

Ogni volta che carichi file nuovi/modificati sul server (vedi l'elenco che ti viene indicato di
volta in volta), controlla anche se c'è una nuova cartella `migrations/NNN_*.sql`: se c'è, vuol
dire che quella modifica ha bisogno anche di una modifica al database, da fare **una sola volta**
da phpMyAdmin (Plesk → Database → phpMyAdmin → scheda **SQL** → incolla il contenuto del file → Esegui).
`migrations/schema.sql` resta invece solo per le installazioni nuove da zero: non va rieseguito su
un database già esistente.

---

## 1. Deploy su Plesk

### 1.1 Dominio/sottodominio

1. In Plesk crea un sottodominio (es. `menu.fattoriamaria.it`) o un dominio dedicato.
2. Imposta come **document root** la cartella `public/` del progetto (in Plesk: "Hosting Settings" →
   "Document root" → `gestionemenu/public`). È importante: le altre cartelle (`src/`, `config/`,
   `migrations/`) non devono essere web-accessibili.
3. Carica tutto il codice nella cartella del dominio (via Git di Plesk, SFTP o zip), mantenendo la
   struttura con `public/` come sottocartella.
4. Assicurati che PHP sia impostato su una versione 8.1+ nelle impostazioni PHP del dominio.

### 1.2 Database

1. In Plesk → Database, crea un nuovo database MySQL/MariaDB (es. `gestionemenu`) e un utente con
   tutti i permessi su quel database.
2. Importa lo schema: apri **phpMyAdmin** dal pannello Plesk (Database → il tuo database →
   phpMyAdmin), scheda **Importa**, seleziona il file `migrations/schema.sql` del progetto e
   conferma. Non serve SSH: phpMyAdmin è sempre incluso in Plesk. Questo crea le tabelle e
   precompila i 14 allergeni UE e le impostazioni di export di default.

### 1.3 Configurazione applicazione

1. Copia `config/config.example.php` in `config/config.local.php` (stesso percorso, cartella
   `config/`) e inserisci host/nome/utente/password del database, l'URL base del sito e il nome
   del locale.
2. Verifica che `config/config.local.php` NON sia raggiungibile dal browser (è fuori da `public/`,
   quindi già protetto se il document root è impostato correttamente al punto 1.1).
3. La cartella `public/uploads/piatti/` è già inclusa nel codice caricato e normalmente è già
   scrivibile dall'utente PHP del dominio (è lo stesso utente che possiede tutti i file caricati).
   Se al primo caricamento di una foto ricevi un errore di permessi, apri il **File Manager** di
   Plesk, seleziona `public/uploads/piatti/` e imposta i permessi a 775 (non serve SSH).

### 1.4 Primo utente amministratore

Non c'è una pagina di registrazione pubblica (per sicurezza, dato che gli utenti sono solo 2-3),
ma non serve SSH per creare il primo admin: **apri semplicemente `https://menu.tuodominio.it/setup`
nel browser**, subito dopo aver importato lo schema. Questa pagina funziona solo finché il database
non ha ancora nessun utente: compila nome, email e password e verrai loggato automaticamente come
amministratore. Dopo la creazione del primo utente la pagina `/setup` si disattiva da sola (mostra
solo un rimando alla pagina di login) e non è più utilizzabile, quindi visitala subito dopo
l'importazione dello schema, prima di comunicare in giro l'indirizzo del sottodominio.

Da lì potrai creare l'utente per il cliente/staff (ruolo `editor`) direttamente dal pannello
**Impostazioni → Utenti**, sempre dal browser.

Se invece hai accesso SSH (o al terminale incluso in alcuni piani Plesk), in alternativa puoi usare:
```
php cron/crea_utente.php "Il Tuo Nome" tuamail@esempio.it "PasswordSicura123" admin
```
Questo stesso script, senza SSH, si può anche lanciare **una tantum** da Plesk → Pianifica attività
(lo stesso strumento usato per il cron stagionale al punto 1.5): crea un'attività "Esegui subito",
comando `php cron/crea_utente.php ...`, e cancellala dopo l'esecuzione. Ma per il caso normale la
pagina `/setup` nel browser è la via più semplice.

### 1.5 Cron: creazione automatica del menu stagionale

Non serve SSH: in Plesk → **Pianifica attività**, clicca "Aggiungi attività", scegli "Esegui un
file PHP", seleziona `cron/crea_menu_stagione.php` dal selettore file (Plesk mostra la struttura
delle cartelle del tuo dominio, quindi non devi scrivere a mano il percorso assoluto), e imposta la
frequenza su giornaliera (es. ogni notte alle 4:00). Lo script:

- calcola la stagione successiva rispetto all'ultimo menu esistente;
- se siamo entrati nella finestra dei 30 giorni prima del suo inizio (20/3, 21/6, 23/9, 21/12) e il
  menu non esiste ancora, lo crea duplicando l'ultimo menu esistente (piatti, prezzi, allergeni,
  ordine, foto);
- è idempotente: se il menu esiste già non fa nulla, quindi eseguirlo ogni giorno è sicuro.

### 1.6 Backup

Nessuno dei due richiede SSH:

- **Database**: da phpMyAdmin (Plesk → Database → phpMyAdmin), scheda **Esporta**, scarica il
  file `.sql`. Oppure usa **Plesk → Backup Manager**, che se configurato include automaticamente
  anche il database nei backup pianificati del dominio.
- **Foto**: la cartella `public/uploads/piatti/` va copiata/backuppata insieme al database (le foto
  non sono nel DB, solo il nome file) — il Backup Manager di Plesk la include già se fai un backup
  completo del dominio; altrimenti scaricala periodicamente via File Manager o FTP/SFTP.

---

## 2. Import iniziale dal Google Sheet

1. Esporta il Google Sheet della stagione corrente in CSV (File → Scarica → CSV).
2. Accedi con l'utente admin, vai su **Importa CSV** dal menu.
3. Carica il file: l'app riconosce automaticamente le colonne Categoria/Nome Piatto/Prezzo/Allergeni
   (puoi correggere la mappatura manualmente se le intestazioni sono diverse).
4. Nell'anteprima: le righe placeholder ("(Seleziona)" / "Esempio Piatto") sono deselezionate di
   default; gli allergeni non riconosciuti (nomi scritti diversamente da quelli in app) vengono
   segnalati in rosso — correggili nel foglio e ricarica, oppure aggiungili a mano dopo l'import.
5. Scegli se importare in un menu esistente o crearne uno nuovo (stagione + anno), poi conferma.

Una volta importato e verificato il menu corrente, il Google Sheet non serve più: tutte le stagioni
successive nascono per duplicazione dentro l'app.

---

## 3. Export per InDesign

### 3.1 Cosa genera l'app

Da un menu, **Export InDesign** produce due file **InDesign Tagged Text (.txt)**, uno per il menu
principale e uno per dolci&drink (in base al campo "menu impaginato" di ogni portata, modificabile
in **Impostazioni** e sulla singola portata). I file sono veri file Unicode (UTF-16 con BOM, non
semplice UTF-8): è il formato che Tagged Text richiede per accenti ed € corretti, non un dettaglio
opzionale.

Ogni portata viene esportata sempre tutta minuscola (es. "antipasti"), qualunque maiuscola/minuscola
usi nell'app. Ogni piatto viene esportato come:

```
<ParaStyle:Portata>nome portata
<ParaStyle:NomePiatto>Nome piatto<TAB><CharStyle:Prezzo>Prezzo<CharStyle:>  <CharStyle:IconeAllergeni><cFont:Allergen><cTypeface:Outline>LETTERE<CharStyle:>
Descrizione (se presente, su un paragrafo a parte ma SENZA un nuovo tag ParaStyle: eredita lo
stesso stile del nome piatto — il documento reale non ha mai avuto uno stile "Descrizione" a sé)
```

Nome e prezzo stanno sempre sulla **stessa riga**, separati da una tabulazione (`<TAB>` nello
schema sopra è un vero carattere di tabulazione, non un tag): per questo `Prezzo` è uno stile di
**carattere**, non di paragrafo. Se vuoi il prezzo allineato a destra, imposta un tab-stop nello
stile di paragrafo del nome piatto in InDesign.

I nomi degli stili (`Portata`, `NomePiatto`, `Prezzo`, `IconeAllergeni`) sono **configurabili** in
**Impostazioni → Export InDesign** e devono corrispondere esattamente ai nomi degli stili di
paragrafo/carattere che hai (o creerai) nel documento InDesign.

### 3.2 Una cosa importante sul font "Allergen Outline"

Analizzando il file IDML del menu impaginato ("Menu A4 - autunno 2026") è emerso che questo font
**non usa codici Unicode dedicati per le icone**: ogni icona allergene è semplicemente una lettera
maiuscola ASCII digitata con quel font (es. la lettera "A" con font Allergen Outline disegna
l'icona "Uovo"). Per questo in **Impostazioni → mappa allergene → lettera** trovi un campo "lettera"
per allergene, non un codice Unicode esotico.

**7 lettere sono già note**, estratte dalla legenda allergeni presente in quell'IDML:

| Allergene | Lettera |
|---|---|
| Uovo | A |
| Glutine | B |
| Senape | C |
| Frutta a Guscio | D |
| Sedano | G |
| Latte e derivati | I |
| Solfiti | M |

Le altre 7 (Crostacei, Pesce, Arachidi, Soia, Semi di Sesamo, Lupini, Molluschi) non compaiono nel
menu Autunno 2026 e quindi non erano nella legenda. Per trovarle:

1. Apri InDesign, crea una cornice di testo di prova.
2. Applica il font **Allergen Outline** e prova le lettere dell'alfabeto una per una (o l'intera
   sequenza A-Z) finché non compaiono le icone mancanti.
3. Annota quale lettera corrisponde a quale icona e inseriscile in **Impostazioni** nell'app.

In alternativa, se in futuro impagini una stagione che usa uno di questi allergeni, potrai
individuare la lettera direttamente dal nuovo IDML con lo stesso metodo usato per estrarre le 7
attuali (vedi § 3.4 "Estrarre dati da un IDML" se vuoi rifarlo tu stesso).

### 3.3 Preparare il documento InDesign prima di importare

Il documento IDML analizzato **non ha ancora stili di paragrafo/carattere nominati** per portata,
nome piatto, descrizione, prezzo o icone allergeni: il testo è formattato solo localmente. Perché
l'import di Tagged Text funzioni bene (assegnando automaticamente la formattazione giusta), prima
di usarlo per la prima volta:

1. In InDesign, crea gli stili di paragrafo `Portata` e `NomePiatto` (o i nomi che preferisci,
   basta che corrispondano a quelli in **Impostazioni**) con la formattazione attuale di ciascun
   elemento. Non serve uno stile per la descrizione: eredita quello del nome piatto. Se vuoi il
   prezzo allineato a destra sulla stessa riga del nome, aggiungi un tab-stop a destra nello
   stile `NomePiatto`.
2. Crea uno stile di **carattere** (non di paragrafo) `Prezzo`, con la formattazione che vuoi per
   il prezzo — verrà applicato solo al testo del prezzo, che condivide la riga col nome piatto.
3. Crea uno stile di carattere `IconeAllergeni` e **imposta già al suo interno** il font
   Famiglia "Allergen", Stile "Outline" (non lasciarlo vuoto): l'export invia comunque anche un
   override locale dello stesso font, ma per sicurezza non affidarti solo a quello — impostalo
   anche nello stile stesso.
4. Da quel momento, `File → Importa → Tagged Text` (o trascina il .txt in una cornice) applicherà
   automaticamente questi stili al testo importato.

**Nota sulla struttura del layout attuale**: nel documento analizzato, nome piatto/descrizione,
prezzo e icone allergeni di ogni portata sono in **cornici di testo separate** (una colonna per il
nome, una per il prezzo, una per le icone di ciascun piatto), non in un unico blocco di testo per
piatto. L'export attuale genera un unico file per portata con tutto in sequenza (nome →
descrizione → prezzo+icone), pensato per un layout con **una cornice di testo che scorre per
portata** (tecnica comune e più comoda da mantenere: quando aggiungi/togli un piatto, il testo si
riflette automaticamente senza dover spostare a mano cornici separate). Se preferisci mantenere
l'attuale struttura a cornici multiple per campo, resta comunque possibile: puoi concatenare/threadare
le cornici "nome", "prezzo" e "icone" di una portata ciascuna in una propria catena di testo e
importare rispettivamente solo la parte che ti serve copiando/adattando il file esportato, oppure
chiedere di estendere l'export con file separati per colonna (nomi / prezzi / icone) — è una
modifica mirata se in futuro preferisci restare sul layout a cornici fisse.

### 3.4 Altri export disponibili

- **CSV di controllo** (Portata, Piatto, Prezzo, Allergeni separati da virgola): per lo strumento
  di controllo automatico dell'impaginato che usi già.
- **Anteprima/stampa HTML**: pagina pulita, stampabile, per far rivedere il menu al cliente prima
  di mandarlo in impaginazione.

### 3.5 Perché Tagged Text e non XML/Data Merge

- **XML strutturato**: adatto se il layout ha cornici fisse per portata da tenere identiche stagione
  dopo stagione; richiede però di taggare manualmente le cornici in InDesign (pannello Struttura) e
  si rompe più facilmente quando il numero di piatti cambia. Non implementato: se in futuro il
  template si stabilizza su frame fissi, è un'estensione mirata dell'export attuale.
- **CSV per Data Merge (UTF-16)**: adatto per layout "a scheda" (un record = un piccolo layout
  ripetuto), non per un menu con paragrafi di lunghezza variabile come questo. Scartato per lo
  stesso motivo.
- **Tagged Text** (quello implementato): gestisce bene un numero variabile di piatti per portata,
  supporta più stili in un unico file, e con l'encoding Unicode corretto gestisce senza problemi
  accenti ed €. È il metodo con meno lavoro manuale per aggiornamenti stagione dopo stagione.

---

## 4. Note tecniche

- Ruoli: **admin** (gestisce utenti e impostazioni export) ed **editor** (gestisce menu/piatti/foto).
- Storico modifiche: ogni piatto tiene traccia di chi ha cambiato cosa (nome, descrizione, prezzo,
  note, tracce) e quando — utile per capire le correzioni richieste dal cliente.
- Sicurezza: password con `password_hash`, protezione CSRF su tutti i form e sulle chiamate AJAX di
  riordino, upload foto validati per contenuto reale (non solo estensione) e ridimensionati lato
  server, cartella upload con esecuzione PHP disabilitata via `.htaccess`.
- Il file CSV di esempio allegato (Autunno 2026) è stato usato per testare l'import end-to-end,
  incluse le righe con descrizione/prezzo su più righe (es. "Battuta di cavallo... 20€ +7,5€")
  e le righe placeholder "(Seleziona)"/"Esempio Piatto", che vengono correttamente riconosciute e
  proposte deselezionate.
- Formato prezzi: numero seguito da € senza spazio (es. `17€`, supplementi `20€ +7,5€`), verificato
  sull'IDML reale. In **Impostazioni** c'è un'azione per correggere in un click i piatti importati
  prima che questa correzione fosse disponibile.
