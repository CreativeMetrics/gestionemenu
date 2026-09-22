<?php

namespace App\Controllers;

use App\Auth;
use App\Repositories\MenuRepository;
use App\Repositories\PiattoRepository;
use App\Repositories\PortataRepository;
use App\Services\CsvExportService;
use App\Services\IndesignExportService;

class ExportController
{
    private MenuRepository $menuRepo;
    private PortataRepository $portataRepo;
    private PiattoRepository $piattoRepo;
    private CsvExportService $csvService;
    private IndesignExportService $indesignService;

    public function __construct()
    {
        $this->menuRepo = new MenuRepository();
        $this->portataRepo = new PortataRepository();
        $this->piattoRepo = new PiattoRepository();
        $this->csvService = new CsvExportService();
        $this->indesignService = new IndesignExportService();
    }

    public function csv(array $params): void
    {
        Auth::requireLogin();
        $menu = $this->menuRepo->find((int) $params['id']);
        if (!$menu) {
            http_response_code(404);
            die('Menu non trovato.');
        }
        $nomeFile = 'menu_' . $menu['stagione'] . '_' . $menu['anno'] . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nomeFile . '"');
        $stream = fopen('php://output', 'w');
        fwrite($stream, "\xEF\xBB\xBF"); // BOM per una corretta apertura in Excel
        $this->csvService->scrivi($stream, (int) $menu['id']);
        fclose($stream);
    }

    public function indesignMenu(array $params): void
    {
        Auth::requireLogin();
        $menu = $this->menuRepo->find((int) $params['id']);
        if (!$menu) {
            http_response_code(404);
            die('Menu non trovato.');
        }
        \App\Support\View::render('export/indesign', ['menu' => $menu]);
    }

    public function indesignFile(array $params): void
    {
        Auth::requireLogin();
        $menu = $this->menuRepo->find((int) $params['id']);
        $gruppo = $params['gruppo'] === 'dolci_drink' ? 'dolci_drink' : 'principale';
        if (!$menu) {
            http_response_code(404);
            die('Menu non trovato.');
        }
        $contenuto = $this->indesignService->generaFile((int) $menu['id'], $gruppo);
        $suffisso = $gruppo === 'dolci_drink' ? 'dolci-drink' : 'principale';
        $nomeFile = 'indesign_' . $menu['stagione'] . '_' . $menu['anno'] . '_' . $suffisso . '.txt';

        header('Content-Type: text/plain; charset=UTF-16');
        header('Content-Disposition: attachment; filename="' . $nomeFile . '"');
        header('Content-Length: ' . strlen($contenuto));
        echo $contenuto;
    }

    public function stampa(array $params): void
    {
        Auth::requireLogin();
        $menu = $this->menuRepo->find((int) $params['id']);
        if (!$menu) {
            http_response_code(404);
            die('Menu non trovato.');
        }
        $portate = $this->portataRepo->forMenu((int) $menu['id']);
        $piattiPerPortata = [];
        foreach ($portate as $portata) {
            $piatti = $this->piattoRepo->forPortata((int) $portata['id']);
            foreach ($piatti as &$piatto) {
                $piatto['allergeni'] = $this->piattoRepo->allergeniPerPiatto((int) $piatto['id']);
            }
            unset($piatto);
            $piattiPerPortata[$portata['id']] = $piatti;
        }
        \App\Support\View::renderPartial('export/stampa', [
            'menu' => $menu,
            'portate' => $portate,
            'piattiPerPortata' => $piattiPerPortata,
        ]);
    }
}
