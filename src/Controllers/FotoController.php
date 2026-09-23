<?php

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Repositories\MenuRepository;
use App\Repositories\PiattoRepository;
use App\Services\ImageService;
use App\Support\View;

class FotoController
{
    private PiattoRepository $piattoRepo;
    private MenuRepository $menuRepo;
    private ImageService $imageService;

    public function __construct()
    {
        $this->piattoRepo = new PiattoRepository();
        $this->menuRepo = new MenuRepository();
        $this->imageService = new ImageService();
    }

    public function form(array $params): void
    {
        Auth::requireLogin();
        $piatto = $this->piattoRepo->findConMenu((int) $params['id']);
        if (!$piatto) {
            http_response_code(404);
            die('Piatto non trovato.');
        }
        View::render('foto/carica', ['piatto' => $piatto]);
    }

    public function carica(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $id = (int) $params['id'];
        $piatto = $this->piattoRepo->findConMenu($id);
        if (!$piatto) {
            http_response_code(404);
            die('Piatto non trovato.');
        }

        try {
            $nomeFile = $this->imageService->salvaFotoCaricata($_FILES['foto'] ?? [], $id, $piatto['nome']);
        } catch (\RuntimeException $e) {
            flash('errore', $e->getMessage());
            redirect('/piatti/' . $id . '/foto');
            return;
        }

        $this->imageService->elimina($piatto['foto_path']);
        $this->piattoRepo->setFoto($id, $nomeFile);
        flash('ok', 'Foto caricata.');
        redirect('/menu/' . $piatto['menu_id']);
    }

    public function rimuovi(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $id = (int) $params['id'];
        $piatto = $this->piattoRepo->findConMenu($id);
        if (!$piatto) {
            http_response_code(404);
            die('Piatto non trovato.');
        }
        $this->imageService->elimina($piatto['foto_path']);
        $this->piattoRepo->setFoto($id, null);
        flash('ok', 'Foto rimossa.');
        redirect('/piatti/' . $id . '/foto');
    }

    /**
     * Segna (o toglie il segno) "foto già presente altrove": esclude il piatto dalla lista
     * "Foto mancanti" senza dover caricare un file, per chi ha già foto usate direttamente in
     * InDesign e non gestite da questa app.
     */
    public function segnaEsterna(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $id = (int) $params['id'];
        $piatto = $this->piattoRepo->findConMenu($id);
        if (!$piatto) {
            http_response_code(404);
            die('Piatto non trovato.');
        }
        $esterna = ($_POST['esterna'] ?? '1') === '1';
        $this->piattoRepo->setFotoEsterna($id, $esterna);
        flash('ok', $esterna
            ? 'Piatto segnato come "foto già presente altrove": non comparirà più tra le foto mancanti.'
            : 'Segnalazione rimossa: il piatto torna tra le foto mancanti.');

        if (($_POST['origine'] ?? '') === 'mancanti') {
            redirect('/foto/mancanti?menu_id=' . (int) $piatto['menu_id']);
        } else {
            redirect('/piatti/' . $id . '/foto');
        }
    }

    public function mancanti(): void
    {
        Auth::requireLogin();
        $menus = $this->menuRepo->attivi();
        $menuId = (int) ($_GET['menu_id'] ?? 0);
        $menu = $menuId > 0 ? $this->menuRepo->find($menuId) : ($menus[0] ?? null);

        $piatti = $menu ? $this->piattoRepo->senzaFotoPerMenu((int) $menu['id']) : [];

        View::render('foto/mancanti', [
            'menus' => $menus,
            'menuSelezionato' => $menu,
            'piatti' => $piatti,
        ]);
    }

    /**
     * Pagina di gestione di tutti i file foto caricati: per liberare spazio (elimina in blocco) e
     * per scaricarli con un nome leggibile invece del nome file interno.
     */
    public function gestione(): void
    {
        Auth::requireAdmin();

        $piattiPerFile = [];
        foreach ($this->piattoRepo->conFotoCaricata() as $p) {
            $piattiPerFile[$p['foto_path']] = $p;
        }

        $file = [];
        foreach ($this->imageService->elencoFile() as $nomeFile) {
            $percorso = $this->imageService->percorsoCompleto($nomeFile);
            $file[] = [
                'nome' => $nomeFile,
                'dimensione' => filesize($percorso),
                'modificato' => filemtime($percorso),
                'piatto' => $piattiPerFile[$nomeFile] ?? null,
            ];
        }
        usort($file, fn ($a, $b) => $b['modificato'] <=> $a['modificato']);

        $totaleByte = array_sum(array_column($file, 'dimensione'));

        View::render('foto/gestione', [
            'file' => $file,
            'totaleByte' => $totaleByte,
        ]);
    }

    public function eliminaMultiple(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();

        $selezionati = $_POST['file'] ?? [];
        if (!is_array($selezionati)) {
            $selezionati = [];
        }

        $eliminati = 0;
        foreach ($selezionati as $nomeFile) {
            $nomeFile = basename((string) $nomeFile);
            if ($nomeFile === '' || !$this->imageService->esiste($nomeFile)) {
                continue;
            }
            $this->imageService->elimina($nomeFile);
            $this->piattoRepo->azzeraFotoPerNomeFile($nomeFile);
            $eliminati++;
        }

        flash('ok', $eliminati > 0 ? $eliminati . ' foto eliminate.' : 'Nessuna foto selezionata.');
        redirect('/impostazioni/foto');
    }

    public function scarica(array $params): void
    {
        Auth::requireAdmin();

        $nomeFile = basename((string) $params['file']);
        if (!$this->imageService->esiste($nomeFile)) {
            http_response_code(404);
            die('File non trovato.');
        }

        $piatto = null;
        foreach ($this->piattoRepo->conFotoCaricata() as $p) {
            if ($p['foto_path'] === $nomeFile) {
                $piatto = $p;
                break;
            }
        }

        $nomeScaricato = $piatto ? ImageService::slug($piatto['nome']) . '.jpg' : $nomeFile;
        $percorso = $this->imageService->percorsoCompleto($nomeFile);

        header('Content-Type: image/jpeg');
        header('Content-Disposition: attachment; filename="' . $nomeScaricato . '"');
        header('Content-Length: ' . filesize($percorso));
        readfile($percorso);
    }
}
