<?php

namespace App\Services;

use App\Repositories\PiattoRepository;

/**
 * Corregge una tantum il formato dei prezzi dei piatti già importati prima che scoprissimo il
 * formato giusto (numero seguito da €, es. "17€", supplementi "20€ +7,5€") guardando l'IDML reale.
 * Riconosce solo pattern noti del vecchio formato: se un prezzo è testo libero non riconosciuto
 * (già modificato a mano dal cliente, o un formato inatteso) lo lascia invariato per sicurezza.
 */
class PrezzoRepairService
{
    public function __construct(private PiattoRepository $piattoRepo = new PiattoRepository())
    {
    }

    /** Corregge tutti i piatti di tutti i menu. Restituisce quanti prezzi sono stati modificati. */
    public function correggiTutti(): int
    {
        $modificati = 0;
        foreach ($this->piattoRepo->tutti() as $piatto) {
            $nuovo = self::correggi((string) $piatto['prezzo_testo']);
            if ($nuovo !== null && $nuovo !== $piatto['prezzo_testo']) {
                $this->piattoRepo->aggiornaPrezzoTesto((int) $piatto['id'], $nuovo);
                $modificati++;
            }
        }
        return $modificati;
    }

    /**
     * Applica lo stesso formato riconosciuto da correggiTutti() a un singolo prezzo appena
     * digitato (es. in creazione/modifica piatto), aggiungendo "€" quando manca. Se il testo non
     * corrisponde a un pattern noto lo lascia invariato (può essere testo libero scritto a mano).
     */
    public static function formatta(string $testo): string
    {
        return self::correggi($testo) ?? $testo;
    }

    private static function correggi(string $testo): ?string
    {
        $testo = trim($testo);
        if ($testo === '') {
            return null;
        }

        // Già nel formato giusto (finisce con €, nessun vecchio separatore "/"): non tocca nulla.
        if (preg_match('/€\s*$/u', $testo) && !str_contains($testo, '/')) {
            return null;
        }

        // Vecchio formato supplemento: "€ 20 / +€ 7,5" -> "20€ +7,5€"
        if (preg_match('/^€\s*(\d+(?:,\d+)?)\s*\/\s*\+\s*€\s*(\d+(?:,\d+)?)$/u', $testo, $m)) {
            return $m[1] . '€ +' . $m[2] . '€';
        }

        // Numero semplice senza simbolo: "17" o "12,5" -> "17€" / "12,5€"
        if (preg_match('/^(\d+(?:,\d+)?)$/u', $testo)) {
            return $testo . '€';
        }

        // Prezzo con € davanti senza supplemento: "€ 17" o "€17" -> "17€"
        if (preg_match('/^€\s*(\d+(?:,\d+)?)$/u', $testo, $m)) {
            return $m[1] . '€';
        }

        // Formato non riconosciuto: non lo tocca (potrebbe essere testo libero scritto a mano).
        return null;
    }
}
