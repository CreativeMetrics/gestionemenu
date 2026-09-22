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
            $nomeFile = $this->imageService->salvaFotoCaricata($_FILES['foto'] ?? [], $id);
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
}
