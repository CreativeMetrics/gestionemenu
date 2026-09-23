<?php

namespace App\Services;

use App\Repositories\ImpostazioniRepository;
use App\Repositories\PiattoRepository;
use App\Repositories\PortataRepository;

/**
 * Genera un file InDesign Tagged Text (.txt) per un menu.
 *
 * Ogni piatto è UNA riga (paragrafo) "nome piatto <TAB> prezzo  icone allergeni", con lo stile di
 * paragrafo del nome piatto e uno stile di CARATTERE per il prezzo (non di paragrafo: deve stare
 * sulla stessa riga del nome, non andare a capo — la tabulazione tra i due va allineata con un
 * tab-stop nello stile di paragrafo "NomePiatto" in InDesign se si vuole il prezzo allineato a
 * destra). La descrizione (se c'è) va su un paragrafo a parte SENZA un nuovo tag <ParaStyle:>:
 * in Tagged Text lo stile dichiarato per un paragrafo resta valido per quelli successivi finché
 * non ne arriva uno nuovo, quindi eredita lo stile del nome piatto. Evita deliberatamente il tag
 * "forced line break" <0x2028>: risulta non riconosciuto da InDesign (vedi log errori) e ne
 * comprometteva l'intero import.
 *
 * Il nome della portata viene sempre esportato tutto minuscolo (es. "antipasti"), a prescindere da
 * come è scritto nell'app: è la convenzione del documento reale. Ogni portata può avere anche un
 * "suffisso export" (portate.suffisso_export) aggiunto subito dopo, solo in questo export — es.
 * "antipasti**" per un rimando a nota, "secondi e contorni".
 *
 * Nota sul font "Allergen Outline": non usa codepoint Unicode dedicati, ogni icona corrisponde a
 * una normale lettera maiuscola digitata con quel font (vedi allergeni.glifo_unicode).
 *
 * Il file viene prodotto come vero Tagged Text "Unicode" (UTF-16 con BOM), come richiesto dal
 * formato: scrivere <UNICODE-WIN>/<UNICODE-MAC> in testa a un file UTF-8 non è sufficiente,
 * InDesign si aspetta i byte in UTF-16.
 */
class IndesignExportService
{
    public function __construct(
        private ImpostazioniRepository $impostazioniRepo = new ImpostazioniRepository(),
        private PortataRepository $portataRepo = new PortataRepository(),
        private PiattoRepository $piattoRepo = new PiattoRepository(),
    ) {
    }

    /**
     * Controlla i piatti del menu prima di generare l'export, per intercettare a schermo problemi
     * che altrimenti si scoprirebbero solo dopo aver importato il file in InDesign: prezzo
     * mancante, nome mancante, o un allergene assegnato senza una lettera mappata nella tabella
     * "Mappa allergene -> glifo" di Impostazioni. Controlla tutte le portate del menu (sia "menu
     * principale" che "dolci & drink"), non solo il file che si sta per scaricare.
     * @return array<int, array{portata: string, piatto: string, messaggio: string}>
     */
    public function problemi(int $menuId): array
    {
        $problemi = [];
        foreach ($this->portataRepo->forMenu($menuId) as $portata) {
            foreach ($this->piattoRepo->forPortata((int) $portata['id']) as $piatto) {
                if (trim((string) $piatto['nome']) === '') {
                    $problemi[] = ['portata' => $portata['nome'], 'piatto' => '(senza nome)', 'messaggio' => 'Il piatto non ha un nome.'];
                }
                if (trim((string) $piatto['prezzo_testo']) === '') {
                    $problemi[] = ['portata' => $portata['nome'], 'piatto' => $piatto['nome'], 'messaggio' => 'Prezzo mancante.'];
                }

                $senzaGlifo = [];
                foreach ($this->piattoRepo->allergeniPerPiatto((int) $piatto['id']) as $a) {
                    if (empty($a['glifo_unicode'])) {
                        $senzaGlifo[] = $a['nome'];
                    }
                }
                if ($senzaGlifo !== []) {
                    $problemi[] = [
                        'portata' => $portata['nome'],
                        'piatto' => $piatto['nome'],
                        'messaggio' => 'Icona non mappata in Impostazioni per: ' . implode(', ', $senzaGlifo) . '.',
                    ];
                }
            }
        }
        return $problemi;
    }

    /** Restituisce i byte pronti per il download (già codificati UTF-16 con BOM). */
    public function generaFile(int $menuId, string $gruppo): string
    {
        $imp = $this->impostazioniRepo->tutte();
        $encoding = ($imp['indesign_encoding'] ?? 'UNICODE-WIN') === 'UNICODE-MAC' ? 'UNICODE-MAC' : 'UNICODE-WIN';
        $eol = $encoding === 'UNICODE-MAC' ? "\r" : "\r\n";

        $stilePortata = $imp['indesign_stile_portata'] ?? 'Portata';
        $stilePiatto = $imp['indesign_stile_piatto'] ?? 'NomePiatto';
        $stilePrezzo = $imp['indesign_stile_prezzo'] ?? 'Prezzo';
        $stileCarattereAllergeni = $imp['indesign_stile_carattere_allergeni'] ?? 'IconeAllergeni';
        $fontAllergeni = $imp['indesign_font_allergeni'] ?? 'Allergen';
        $fontStyleAllergeni = $imp['indesign_fontstyle_allergeni'] ?? 'Outline';

        $righe = [];
        $righe[] = "<{$encoding}>";

        foreach ($this->portataRepo->forMenu($menuId) as $portata) {
            if ($portata['gruppo_impaginato'] !== $gruppo) {
                continue;
            }
            $piatti = $this->piattoRepo->forPortata((int) $portata['id']);
            if ($piatti === []) {
                continue;
            }

            $nomePortata = mb_strtolower($portata['nome'], 'UTF-8') . ($portata['suffisso_export'] ?? '');
            $righe[] = "<ParaStyle:{$stilePortata}>" . $this->escape($nomePortata);

            foreach ($piatti as $piatto) {
                $linea = "<ParaStyle:{$stilePiatto}>" . $this->escape($piatto['nome'])
                    . "\t<CharStyle:{$stilePrezzo}>" . $this->escape($piatto['prezzo_testo']) . '<CharStyle:>';

                $lettere = '';
                foreach ($this->piattoRepo->allergeniPerPiatto((int) $piatto['id']) as $a) {
                    if (!empty($a['glifo_unicode'])) {
                        $lettere .= $a['glifo_unicode'];
                    }
                }
                if ($lettere !== '') {
                    $linea .= '  '
                        . "<CharStyle:{$stileCarattereAllergeni}><cFont:{$fontAllergeni}><cTypeface:{$fontStyleAllergeni}>"
                        . $this->escape($lettere)
                        . '<CharStyle:>';
                }
                $righe[] = $linea;

                if (!empty($piatto['descrizione'])) {
                    foreach ($this->righe($piatto['descrizione']) as $rigaDescrizione) {
                        // Nessun <ParaStyle:> qui: eredita deliberatamente lo stile del nome piatto.
                        $righe[] = $this->escape($rigaDescrizione);
                    }
                }
            }
        }

        $testo = implode($eol, $righe) . $eol;

        return $encoding === 'UNICODE-MAC'
            ? "\xFE\xFF" . mb_convert_encoding($testo, 'UTF-16BE', 'UTF-8')
            : "\xFF\xFE" . mb_convert_encoding($testo, 'UTF-16LE', 'UTF-8');
    }

    private function escape(string $testo): string
    {
        $testo = str_replace('\\', '\\\\', $testo);
        return str_replace('<', '\\<', $testo);
    }

    /** Spezza un testo eventualmente su più righe in un array di righe (senza escaping). */
    private function righe(string $testo): array
    {
        return preg_split('/\r\n|\r|\n/', $testo) ?: [$testo];
    }
}
