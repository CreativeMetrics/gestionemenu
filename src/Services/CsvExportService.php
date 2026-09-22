<?php

namespace App\Services;

use App\Repositories\PiattoRepository;
use App\Repositories\PortataRepository;

class CsvExportService
{
    public function __construct(
        private PortataRepository $portataRepo = new PortataRepository(),
        private PiattoRepository $piattoRepo = new PiattoRepository(),
    ) {
    }

    /** Scrive il CSV di controllo (Portata, Piatto, Prezzo, Allergeni) direttamente sullo stream dato. */
    public function scrivi($stream, int $menuId): void
    {
        fputcsv($stream, ['Portata', 'Piatto', 'Prezzo', 'Allergeni']);
        foreach ($this->portataRepo->forMenu($menuId) as $portata) {
            foreach ($this->piattoRepo->forPortata((int) $portata['id']) as $piatto) {
                $nomiAllergeni = array_map(
                    fn ($a) => $a['nome'],
                    $this->piattoRepo->allergeniPerPiatto((int) $piatto['id'])
                );
                fputcsv($stream, [
                    $portata['nome'],
                    $piatto['nome'],
                    $piatto['prezzo_testo'],
                    implode(', ', $nomiAllergeni),
                ]);
            }
        }
    }
}
