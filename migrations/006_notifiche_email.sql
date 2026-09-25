-- Da eseguire UNA VOLTA SOLA su un database creato prima di questa modifica
-- (chi installa da zero non ne ha bisogno: le colonne sono già in migrations/schema.sql).
--
-- Aggiunge le notifiche email agli admin quando un editor modifica un piatto: un flag per
-- decidere quali admin le vogliono, e la data in cui ogni riga di storico è stata inclusa in
-- un digest (per non notificare due volte la stessa modifica).
--
-- Da phpMyAdmin: apri il tuo database → scheda SQL → incolla ed esegui questo contenuto.

ALTER TABLE users
    ADD COLUMN notifiche_email TINYINT(1) NOT NULL DEFAULT 0 AFTER attivo;

-- Gli admin già esistenti partono con le notifiche attive (possono disattivarle da Impostazioni
-- → Utenti); i nuovi admin creati dopo questa modifica partiranno spenti, attivabili a mano.
UPDATE users SET notifiche_email = 1 WHERE ruolo = 'admin';

ALTER TABLE piatto_storico
    ADD COLUMN notificato_il DATETIME NULL AFTER creato_il;
