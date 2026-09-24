<?php

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Repositories\MenuRepository;
use App\Repositories\PiattoRepository;
use App\Repositories\PortataRepository;
use App\Repositories\StoricoRepository;
use App\Services\ImageService;

class PortataController
{
    private PortataRepository $portataRepo;
    private MenuRepository $menuRepo;
    private PiattoRepository $piattoRepo;
    private StoricoRepository $storicoRepo;
    private ImageService $imageService;

    public function __construct()
    {
        $this->portataRepo = new PortataRepository();
        $this->menuRepo = new MenuRepository();
        $this->piattoRepo = new PiattoRepository();
        $this->storicoRepo = new StoricoRepository();
        $this->imageService = new ImageService();
    }

    public function crea(): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $menuId = (int) ($_POST['menu_id'] ?? 0);
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $gruppo = ($_POST['gruppo_impaginato'] ?? 'principale') === 'dolci_drink' ? 'dolci_drink' : 'principale';
        $suffisso = $this->suffissoDaPost();

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
        $suffisso = $this->suffissoDaPost();
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
        $utente = Auth::user();
        $id = (int) $params['id'];
        $portata = $this->portataRepo->find($id);
        if (!$portata) {
            http_response_code(404);
            die('Portata non trovata.');
        }
        $menuId = $portata['menu_id'];

        // Eliminare la portata cancella i suoi piatti "a cascata" nel database: senza registrarlo
        // qui, l'elenco "modifiche dall'ultimo export" non si accorgerebbe che sono spariti.
        foreach ($this->piattoRepo->forPortata($id) as $piatto) {
            $this->storicoRepo->logEliminazione(
                (int) $piatto['id'],
                $piatto['nome'],
                $utente['id'],
                (int) $menuId,
                $portata['gruppo_impaginato']
            );
            $this->imageService->elimina($piatto['foto_path']);
        }

        $this->portataRepo->delete($id);
        flash('ok', 'Portata eliminata (con tutti i suoi piatti).');
        redirect('/menu/' . $menuId);
    }

    /**
     * A differenza degli altri campi, il suffisso export NON va trimmato: uno spazio iniziale
     * (es. " e contorni") è intenzionale e va preservato. Considera vuoto solo un valore che è
     * tutto spazi/assente.
     */
    private function suffissoDaPost(): string
    {
        $grezzo = (string) ($_POST['suffisso_export'] ?? '');
        return trim($grezzo) === '' ? '' : $grezzo;
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
