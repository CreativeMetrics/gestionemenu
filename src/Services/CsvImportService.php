<?php

namespace App\Services;

use App\Repositories\AllergeneRepository;

class CsvImportService
{
    /** Sinonimi di intestazione riconosciuti per la mappatura automatica delle colonne. */
    private const SINONIMI = [
        'categoria' => ['categoria', 'portata'],
        'nome' => ['nome piatto', 'piatto', 'nome'],
        'prezzo' => ['prezzo'],
        'allergeni' => ['allergeni', 'allergene'],
    ];

    public function __construct(private AllergeneRepository $allergeneRepo = new AllergeneRepository())
    {
    }

    /**
     * @return array{header: array<int,string>, righe: array<int, array<int,string>>}
     */
    public function parseCsv(string $testo): array
    {
        // Rimuove un eventuale BOM UTF-8.
        $testo = preg_replace('/^\xEF\xBB\xBF/', '', $testo);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $testo);
        rewind($stream);

        $righe = [];
        $header = [];
        $prima = true;
        while (($riga = fgetcsv($stream, 0, ',', '"', '\\')) !== false) {
            if ($riga === [null] || $riga === false) {
                continue;
            }
            if ($prima) {
                $header = array_map(fn ($h) => trim((string) $h), $riga);
                $prima = false;
                continue;
            }
            $righe[] = $riga;
        }
        fclose($stream);

        return ['header' => $header, 'righe' => $righe];
    }

    /**
     * @param array<int,string> $header
     * @return array<string, int> mappa campo => indice colonna (-1 se non trovato)
     */
    public function rilevaMappaturaAutomatica(array $header): array
    {
        $mappa = [];
        foreach (self::SINONIMI as $campo => $sinonimi) {
            $mappa[$campo] = -1;
            foreach ($header as $idx => $nomeColonna) {
                if (in_array(mb_strtolower(trim($nomeColonna)), $sinonimi, true)) {
                    $mappa[$campo] = $idx;
                    break;
                }
            }
        }
        return $mappa;
    }

    /**
     * @param array<int, array<int,string>> $righe
     * @param array<string, int> $mappatura campo => indice colonna
     * @return array<int, array<string, mixed>>
     */
    public function normalizza(array $righe, array $mappatura): array
    {
        $allergeniIndicizzati = [];
        foreach ($this->allergeneRepo->all() as $a) {
            $allergeniIndicizzati[mb_strtolower($a['nome'])] = (int) $a['id'];
        }

        $risultato = [];
        foreach ($righe as $riga) {
            $categoria = trim((string) ($riga[$mappatura['categoria']] ?? ''));
            $nomeGrezzo = (string) ($riga[$mappatura['nome']] ?? '');
            $prezzoGrezzo = (string) ($riga[$mappatura['prezzo']] ?? '');
            $allergeniGrezzo = (string) ($riga[$mappatura['allergeni']] ?? '');

            [$nome, $descrizione] = $this->splitNomeDescrizione($nomeGrezzo);
            [$prezzoTesto, $prezzoNumero] = $this->normalizzaPrezzo($prezzoGrezzo);

            $allergeniNomi = array_values(array_filter(array_map('trim', explode(',', $allergeniGrezzo))));
            $allergeniIds = [];
            $allergeniNonTrovati = [];
            foreach ($allergeniNomi as $nomeAllergene) {
                $chiave = mb_strtolower($nomeAllergene);
                if (isset($allergeniIndicizzati[$chiave])) {
                    $allergeniIds[] = $allergeniIndicizzati[$chiave];
                } else {
                    $allergeniNonTrovati[] = $nomeAllergene;
                }
            }

            $placeholder = $categoria === '(Seleziona)'
                || $nome === ''
                || mb_strtolower($nome) === 'esempio piatto';

            $risultato[] = [
                'categoria' => $categoria !== '' ? $categoria : '(senza categoria)',
                'nome' => $nome,
                'descrizione' => $descrizione,
                'prezzo_testo' => $prezzoTesto,
                'prezzo_numero' => $prezzoNumero,
                'allergeni_ids' => $allergeniIds,
                'allergeni_non_trovati' => $allergeniNonTrovati,
                'placeholder' => $placeholder,
            ];
        }
        return $risultato;
    }

    /** @return array{0:string,1:?string} [nome, descrizione] */
    private function splitNomeDescrizione(string $grezzo): array
    {
        $righe = preg_split('/\r\n|\r|\n/', trim($grezzo));
        $nome = trim($righe[0] ?? '');
        $resto = array_slice($righe, 1);
        $descrizione = trim(implode("\n", $resto));
        return [$nome, $descrizione !== '' ? $descrizione : null];
    }

    /** @return array{0:string,1:?float} [prezzo_testo, prezzo_numero] */
    private function normalizzaPrezzo(string $grezzo): array
    {
        $righe = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', trim($grezzo))),
            fn ($r) => $r !== ''
        ));

        if (count($righe) === 0) {
            return ['', null];
        }

        $primoNumero = null;
        if (preg_match('/([\d]+(?:[.,]\d+)?)/', $righe[0], $m)) {
            $primoNumero = (float) str_replace(',', '.', $m[1]);
        }

        if (count($righe) === 1) {
            // Prezzo semplice: rimuove il simbolo € e formatta all'italiana (17.00 -> 17, 12.50 -> 12,5).
            if ($primoNumero !== null) {
                return [$this->formattaNumeroItaliano($primoNumero), $primoNumero];
            }
            return [$righe[0], null];
        }

        // Prezzo con supplemento su più righe: mantiene il testo originale unendo con " / ".
        return [implode(' / ', $righe), $primoNumero];
    }

    private function formattaNumeroItaliano(float $numero): string
    {
        if (abs($numero - round($numero)) < 0.001) {
            return (string) (int) round($numero);
        }
        $testo = number_format($numero, 2, ',', '');
        return rtrim(rtrim($testo, '0'), ',');
    }
}
