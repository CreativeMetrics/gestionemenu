<?php

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Repositories\MenuRepository;
use App\Repositories\PiattoRepository;
use App\Repositories\PortataRepository;
use App\Services\IndesignExportService;
use App\Services\SeasonService;
use App\Support\View;

class MenuController
{
    private MenuRepository $menuRepo;
    private PortataRepository $portataRepo;
    private PiattoRepository $piattoRepo;
    private SeasonService $seasonService;
    private IndesignExportService $indesignService;

    public function __construct()
    {
        $this->menuRepo = new MenuRepository();
        $this->portataRepo = new PortataRepository();
        $this->piattoRepo = new PiattoRepository();
        $this->seasonService = new SeasonService();
        $this->indesignService = new IndesignExportService();
    }

    public function index(): void
    {
        Auth::requireLogin();
        $menus = $this->menuRepo->attivi();
        View::render('menu/index', ['menus' => $menus]);
    }

    public function archivio(): void
    {
        Auth::requireLogin();
        $menus = $this->menuRepo->archiviati();
        View::render('menu/archivio', ['menus' => $menus]);
    }

    public function show(array $params): void
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
        View::render('menu/show', [
            'menu' => $menu,
            'portate' => $portate,
            'piattiPerPortata' => $piattiPerPortata,
            'soloLettura' => $menu['stato'] === 'archiviato',
            // L'export InDesign è riservato agli admin: niente senso calcolare/mostrare
            // l'avviso "modifiche non esportate" a chi non può comunque aprire quella pagina.
            'statoExport' => Auth::isAdmin() ? $this->indesignService->statoExport($menu) : [],
        ]);
    }

    public function creaProssima(): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $nuovoId = $this->seasonService->creaProssimaStagione();
        if ($nuovoId === null) {
            flash('errore', 'Nessun menu esistente da cui duplicare. Crea prima un menu manualmente o importa un CSV.');
            redirect('/');
            return;
        }
        flash('ok', 'Nuovo menu creato come bozza, duplicando quello precedente.');
        redirect('/menu/' . $nuovoId);
    }

    public function nuovoVuoto(): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $stagione = $_POST['stagione'] ?? '';
        $anno = (int) ($_POST['anno'] ?? 0);
        if (!in_array($stagione, SeasonService::ORDINE, true) || $anno < 2000) {
            flash('errore', 'Stagione o anno non validi.');
            redirect('/');
            return;
        }
        if ($this->menuRepo->findByStagioneAnno($stagione, $anno) !== null) {
            flash('errore', 'Esiste già un menu per quella stagione.');
            redirect('/');
            return;
        }
        $menuId = $this->menuRepo->create($stagione, $anno, 'bozza');
        foreach (PortataRepository::elencoDiDefault() as $i => $p) {
            $this->portataRepo->create($menuId, $p['nome'], $i + 1, $p['gruppo'], $p['suffisso']);
        }
        flash('ok', 'Nuovo menu creato con le portate di base.');
        redirect('/menu/' . $menuId);
    }

    public function duplicaComeBase(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $sourceId = (int) $params['id'];
        $source = $this->menuRepo->find($sourceId);
        if (!$source) {
            http_response_code(404);
            die('Menu non trovato.');
        }
        $ultimo = $this->menuRepo->ultimoEsistente();
        [$stagione, $anno] = $this->seasonService->prossima($ultimo['stagione'], (int) $ultimo['anno']);
        if ($this->menuRepo->findByStagioneAnno($stagione, $anno) !== null) {
            flash('errore', 'Esiste già un menu per la prossima stagione (' . stagione_label($stagione) . ' ' . $anno . '): modificalo direttamente invece di duplicare.');
            redirect('/menu/' . $sourceId);
            return;
        }
        $nuovoId = $this->seasonService->duplicaMenu($sourceId, $stagione, $anno);
        flash('ok', 'Menu duplicato come base per ' . stagione_label($stagione) . ' ' . $anno . '.');
        redirect('/menu/' . $nuovoId);
    }

    public function aggiornaStato(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $id = (int) $params['id'];
        $stato = $_POST['stato'] ?? '';
        if (!in_array($stato, ['bozza', 'in_revisione', 'pubblicato', 'archiviato'], true)) {
            flash('errore', 'Stato non valido.');
            redirect('/menu/' . $id);
            return;
        }
        $this->menuRepo->updateStato($id, $stato);
        flash('ok', 'Stato del menu aggiornato a "' . stato_label($stato) . '".');
        redirect('/menu/' . $id);
    }
}
