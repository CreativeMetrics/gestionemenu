<?php

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Repositories\MenuRepository;
use App\Repositories\PortataRepository;

class PortataController
{
    private PortataRepository $portataRepo;
    private MenuRepository $menuRepo;

    public function __construct()
    {
        $this->portataRepo = new PortataRepository();
        $this->menuRepo = new MenuRepository();
    }

    public function crea(): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $menuId = (int) ($_POST['menu_id'] ?? 0);
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $gruppo = ($_POST['gruppo_impaginato'] ?? 'principale') === 'dolci_drink' ? 'dolci_drink' : 'principale';
        $suffisso = trim((string) ($_POST['suffisso_export'] ?? ''));

        if ($menuId <= 0 || $nome === '') {
            flash('errore', 'Nome portata obbligatorio.');
            redirect('/menu/' . $menuId);
            return;
        }
        $ordine = $this->portataRepo->prossimoOrdine($menuId);
        $this->portataRepo->create($menuId, $nome, $ordine, $gruppo, $suffisso);
        flash('ok', 'Portata "' . $nome . '" aggiunta.');
        redirect('/menu/' . $menuId);
    }

    public function modifica(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $id = (int) $params['id'];
        $portata = $this->portataRepo->find($id);
        if (!$portata) {
            http_response_code(404);
            die('Portata non trovata.');
        }
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $gruppo = ($_POST['gruppo_impaginato'] ?? 'principale') === 'dolci_drink' ? 'dolci_drink' : 'principale';
        $suffisso = trim((string) ($_POST['suffisso_export'] ?? ''));
        if ($nome === '') {
            flash('errore', 'Nome portata obbligatorio.');
            redirect('/menu/' . $portata['menu_id']);
            return;
        }
        $this->portataRepo->update($id, $nome, $gruppo, $suffisso);
        flash('ok', 'Portata aggiornata.');
        redirect('/menu/' . $portata['menu_id']);
    }

    public function elimina(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $id = (int) $params['id'];
        $portata = $this->portataRepo->find($id);
        if (!$portata) {
            http_response_code(404);
            die('Portata non trovata.');
        }
        $menuId = $portata['menu_id'];
        $this->portataRepo->delete($id);
        flash('ok', 'Portata eliminata (con tutti i suoi piatti).');
        redirect('/menu/' . $menuId);
    }

    public function riordina(): void
    {
        Auth::requireLogin();
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (!Csrf::verifyJson($input)) {
            http_response_code(419);
            echo json_encode(['ok' => false, 'errore' => 'CSRF non valido']);
            return;
        }
        foreach ($input['ids'] ?? [] as $ordine => $portataId) {
            $this->portataRepo->updateOrdine((int) $portataId, $ordine + 1);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }
}
