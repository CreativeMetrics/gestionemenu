<?php

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Repositories\MenuRepository;
use App\Repositories\PiattoRepository;
use App\Repositories\PortataRepository;
use App\Services\CsvImportService;
use App\Support\View;

class ImportController
{
    private CsvImportService $csvService;
    private MenuRepository $menuRepo;
    private PortataRepository $portataRepo;
    private PiattoRepository $piattoRepo;

    public function __construct()
    {
        $this->csvService = new CsvImportService();
        $this->menuRepo = new MenuRepository();
        $this->portataRepo = new PortataRepository();
        $this->piattoRepo = new PiattoRepository();
    }

    public function form(): void
    {
        Auth::requireLogin();
        View::render('import/upload', ['menus' => $this->menuRepo->all()]);
    }

    public function anteprima(): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();

        if (!empty($_FILES['csv']['tmp_name']) && is_uploaded_file($_FILES['csv']['tmp_name'])) {
            $_SESSION['import']['csv_raw'] = file_get_contents($_FILES['csv']['tmp_name']);
            $_SESSION['import']['target_menu_id'] = (int) ($_POST['menu_id'] ?? 0);
            $_SESSION['import']['nuova_stagione'] = $_POST['stagione'] ?? '';
            $_SESSION['import']['nuovo_anno'] = (int) ($_POST['anno'] ?? 0);
        }

        $raw = $_SESSION['import']['csv_raw'] ?? null;
        if ($raw === null) {
            flash('errore', 'Carica prima un file CSV.');
            redirect('/import');
            return;
        }

        $parsed = $this->csvService->parseCsv($raw);
        $mappaAuto = $this->csvService->rilevaMappaturaAutomatica($parsed['header']);

        $mappatura = [
            'categoria' => isset($_POST['col_categoria']) ? (int) $_POST['col_categoria'] : $mappaAuto['categoria'],
            'nome' => isset($_POST['col_nome']) ? (int) $_POST['col_nome'] : $mappaAuto['nome'],
            'prezzo' => isset($_POST['col_prezzo']) ? (int) $_POST['col_prezzo'] : $mappaAuto['prezzo'],
            'allergeni' => isset($_POST['col_allergeni']) ? (int) $_POST['col_allergeni'] : $mappaAuto['allergeni'],
        ];

        if (in_array(-1, [$mappatura['categoria'], $mappatura['nome']], true)) {
            flash('errore', 'Seleziona almeno le colonne Categoria e Nome piatto.');
        }

        $righeNormalizzate = $this->csvService->normalizza($parsed['righe'], array_map(fn ($v) => max($v, 0), $mappatura));
        $_SESSION['import']['righe'] = $righeNormalizzate;

        View::render('import/anteprima', [
            'header' => $parsed['header'],
            'mappatura' => $mappatura,
            'righe' => $righeNormalizzate,
            'menus' => $this->menuRepo->all(),
            'targetMenuId' => (int) ($_SESSION['import']['target_menu_id'] ?? 0),
            'nuovaStagione' => $_SESSION['import']['nuova_stagione'] ?? '',
            'nuovoAnno' => $_SESSION['import']['nuovo_anno'] ?? (int) date('Y'),
        ]);
    }

    public function conferma(): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $utente = Auth::user();

        $righe = $_SESSION['import']['righe'] ?? null;
        if ($righe === null) {
            flash('errore', 'Nessuna anteprima da confermare: ricarica il CSV.');
            redirect('/import');
            return;
        }

        $selezionate = array_map('intval', $_POST['righe'] ?? []);
        $targetMenuId = (int) ($_POST['menu_id'] ?? 0);

        if ($targetMenuId > 0) {
            $menu = $this->menuRepo->find($targetMenuId);
            if (!$menu) {
                flash('errore', 'Menu di destinazione non valido.');
                redirect('/import');
                return;
            }
            $menuId = $targetMenuId;
        } else {
            $stagione = $_POST['stagione'] ?? '';
            $anno = (int) ($_POST['anno'] ?? 0);
            if ($stagione === '' || $anno < 2000) {
                flash('errore', 'Indica stagione e anno per il nuovo menu.');
                redirect('/import');
                return;
            }
            if ($this->menuRepo->findByStagioneAnno($stagione, $anno) !== null) {
                flash('errore', 'Esiste già un menu per quella stagione: selezionalo come destinazione invece di crearne uno nuovo.');
                redirect('/import');
                return;
            }
            $menuId = $this->menuRepo->create($stagione, $anno, 'bozza');
        }

        $portateEsistenti = [];
        foreach ($this->portataRepo->forMenu($menuId) as $p) {
            $portateEsistenti[mb_strtolower($p['nome'])] = (int) $p['id'];
        }
        $gruppoDiDefault = [];
        foreach (PortataRepository::elencoDiDefault() as $d) {
            $gruppoDiDefault[mb_strtolower($d['nome'])] = $d['gruppo'];
        }

        $importati = 0;
        foreach ($selezionate as $idx) {
            $riga = $righe[$idx] ?? null;
            if ($riga === null || $riga['placeholder'] || $riga['nome'] === '') {
                continue;
            }

            $chiaveCategoria = mb_strtolower($riga['categoria']);
            if (!isset($portateEsistenti[$chiaveCategoria])) {
                $gruppo = $gruppoDiDefault[$chiaveCategoria] ?? 'principale';
                $ordine = $this->portataRepo->prossimoOrdine($menuId);
                $portateEsistenti[$chiaveCategoria] = $this->portataRepo->create($menuId, $riga['categoria'], $ordine, $gruppo);
            }
            $portataId = $portateEsistenti[$chiaveCategoria];

            $piattoId = $this->piattoRepo->create([
                'portata_id' => $portataId,
                'nome' => $riga['nome'],
                'descrizione' => $riga['descrizione'],
                'prezzo_testo' => $riga['prezzo_testo'],
                'prezzo_numero' => $riga['prezzo_numero'],
                'ordine' => $this->piattoRepo->prossimoOrdine($portataId),
                'user_id' => $utente['id'],
            ]);
            $this->piattoRepo->setAllergeni($piattoId, $riga['allergeni_ids']);
            $importati++;
        }

        unset($_SESSION['import']);
        flash('ok', $importati . ' piatti importati.');
        redirect('/menu/' . $menuId);
    }
}
