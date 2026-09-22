<?php

namespace App\Services;

use App\Repositories\ImpostazioniRepository;
use App\Repositories\PiattoRepository;
use App\Repositories\PortataRepository;

/**
 * Genera un file InDesign Tagged Text (.txt) per un menu, con stili di paragrafo per
 * portata/nome piatto/prezzo e stile carattere + font locale per le icone allergeni.
 *
 * Nome piatto e descrizione condividono lo stesso paragrafo/stile (separati da un a-capo forzato):
 * nel documento reale non esiste uno stile "Descrizione" distinto, il piatto è un blocco unico
 * basato sullo stile del nome.
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

            $righe[] = "<ParaStyle:{$stilePortata}>" . $this->escape($portata['nome']);

            foreach ($piatti as $piatto) {
                $nomeEDescrizione = $this->escape($piatto['nome']);
                if (!empty($piatto['descrizione'])) {
                    $nomeEDescrizione .= '<0x2028>' . $this->escapeMultilinea($piatto['descrizione']);
                }
                $righe[] = "<ParaStyle:{$stilePiatto}>" . $nomeEDescrizione;

                $lineaPrezzo = "<ParaStyle:{$stilePrezzo}>" . $this->escape($piatto['prezzo_testo']);

                $lettere = '';
                foreach ($this->piattoRepo->allergeniPerPiatto((int) $piatto['id']) as $a) {
                    if (!empty($a['glifo_unicode'])) {
                        $lettere .= $a['glifo_unicode'];
                    }
                }
                if ($lettere !== '') {
                    $lineaPrezzo .= '  '
                        . "<CharStyle:{$stileCarattereAllergeni}><cFont:{$fontAllergeni}><cTypeface:{$fontStyleAllergeni}>"
                        . $this->escape($lettere)
                        . '<CharStyle:>';
                }
                $righe[] = $lineaPrezzo;
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

    private function escapeMultilinea(string $testo): string
    {
        $testo = $this->escape($testo);
        return str_replace(["\r\n", "\r", "\n"], '<0x2028>', $testo);
    }
}
