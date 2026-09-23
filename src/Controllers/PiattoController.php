<?php

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Repositories\AllergeneRepository;
use App\Repositories\PiattoRepository;
use App\Repositories\PortataRepository;
use App\Repositories\StoricoRepository;
use App\Services\ImageService;
use App\Services\PrezzoRepairService;
use App\Support\View;

class PiattoController
{
    private PiattoRepository $piattoRepo;
    private PortataRepository $portataRepo;
    private AllergeneRepository $allergeneRepo;
    private StoricoRepository $storicoRepo;
    private ImageService $imageService;

    public function __construct()
    {
        $this->piattoRepo = new PiattoRepository();
        $this->portataRepo = new PortataRepository();
        $this->allergeneRepo = new AllergeneRepository();
        $this->storicoRepo = new StoricoRepository();
        $this->imageService = new ImageService();
    }

    public function nuovoForm(): void
    {
        Auth::requireLogin();
        $portataId = (int) ($_GET['portata_id'] ?? 0);
        $portata = $this->portataRepo->find($portataId);
        if (!$portata) {
            http_response_code(404);
            die('Portata non trovata.');
        }
        View::render('piatto/form', [
            'piatto' => null,
            'portata' => $portata,
            'allergeni' => $this->allergeneRepo->all(),
            'allergeniSelezionati' => [],
            'tracceSelezionate' => [],
        ]);
    }

    public function crea(): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $utente = Auth::user();
        $portataId = (int) ($_POST['portata_id'] ?? 0);
        $portata = $this->portataRepo->find($portataId);
        if (!$portata) {
            http_response_code(404);
            die('Portata non trovata.');
        }

        $dati = $this->datiDaForm();
        $dati['portata_id'] = $portataId;
        $dati['ordine'] = $this->piattoRepo->prossimoOrdine($portataId);
        $dati['user_id'] = $utente['id'];

        if ($dati['nome'] === '') {
            flash('errore', 'Il nome del piatto è obbligatorio.');
            redirect('/menu/' . $portata['menu_id']);
            return;
        }

        $id = $this->piattoRepo->create($dati);
        $this->piattoRepo->setAllergeni($id, $this->allergeniPostati('allergeni'));
        $this->storicoRepo->log($id, $dati['nome'], $utente['id'], 'creazione', null, $dati['nome']);

        flash('ok', 'Piatto "' . $dati['nome'] . '" aggiunto.');
        redirect('/menu/' . $portata['menu_id']);
    }

    public function modificaForm(array $params): void
    {
        Auth::requireLogin();
        $id = (int) $params['id'];
        $piatto = $this->piattoRepo->findConMenu($id);
        if (!$piatto) {
            http_response_code(404);
            die('Piatto non trovato.');
        }
        $portata = $this->portataRepo->find((int) $piatto['portata_id']);
        View::render('piatto/form', [
            'piatto' => $piatto,
            'portata' => $portata,
            'allergeni' => $this->allergeneRepo->all(),
            'allergeniSelezionati' => $this->piattoRepo->allergeniIds($id),
            'tracceSelezionate' => array_filter(array_map('trim', explode(',', (string) $piatto['tracce_di']))),
        ]);
    }

    public function modifica(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $utente = Auth::user();
        $id = (int) $params['id'];
        $piattoAttuale = $this->piattoRepo->findConMenu($id);
        if (!$piattoAttuale) {
            http_response_code(404);
            die('Piatto non trovato.');
        }

        $dati = $this->datiDaForm();
        $dati['user_id'] = $utente['id'];

        if ($dati['nome'] === '') {
            flash('errore', 'Il nome del piatto è obbligatorio.');
            redirect('/menu/' . $piattoAttuale['menu_id']);
            return;
        }

        foreach (['nome', 'descrizione', 'prezzo_testo', 'note_interne', 'tracce_di'] as $campo) {
            $this->storicoRepo->log(
                $id,
                $dati['nome'] ?: $piattoAttuale['nome'],
                $utente['id'],
                $campo,
                (string) $piattoAttuale[$campo],
                (string) ($dati[$campo] ?? '')
            );
        }

        $this->piattoRepo->update($id, $dati);
        $this->piattoRepo->setAllergeni($id, $this->allergeniPostati('allergeni'));

        flash('ok', 'Piatto aggiornato.');
        redirect('/menu/' . $piattoAttuale['menu_id']);
    }

    /**
     * Crea una copia del piatto nella stessa portata (nome, prezzo, allergeni), utile per varianti
     * simili (es. "senza glutine"). La foto non viene copiata: entrambi i piatti punterebbero allo
     * stesso file ed eliminarne uno cancellerebbe la foto anche dell'altro.
     */
    public function duplica(array $params): void
    {
        Auth::requireLogin();
        Csrf::verifyOrFail();
        $utente = Auth::user();
        $id = (int) $params['id'];
        $originale = $this->piattoRepo->findConMenu($id);
        if (!$originale) {
            http_response_code(404);
            die('Piatto non trovato.');
        }

        $nomeCopia = $originale['nome'] . ' (copia)';
        $nuovoId = $this->piattoRepo->create([
            'portata_id' => $originale['portata_id'],
            'nome' => $nomeCopia,
            'descrizione' => $originale['descrizione'],
            'prezzo_testo' => $originale['prezzo_testo'],
            'prezzo_numero' => $originale['prezzo_numero'],
            'tracce_di' => $originale['tracce_di'],
            'note_interne' => $originale['note_interne'],
            'ordine' => $this->piattoRepo->prossimoOrdine((int) $originale['portata_id']),
            'user_id' => $utente['id'],
        ]);
        $this->piattoRepo->setAllergeni($nuovoId, $this->piattoRepo->allergeniIds($id));
        $this->storicoRepo->log($nuovoId, $nomeCopia, $utente['id'], 'creazione', null, 'Duplicato da "' . $originale['nome'] . '"');

        flash('ok', 'Piatto duplicato (foto non copiata: caricane una nuova se serve).');
        redirect('/menu/' . $originale['menu_id']);
    }

    public function elimina(array $params): void
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
        $this->piattoRepo->delete($id);
        flash('ok', 'Piatto eliminato.');
        redirect('/menu/' . $piatto['menu_id']);
    }

    public function storico(array $params): void
    {
        Auth::requireLogin();
        $id = (int) $params['id'];
        $piatto = $this->piattoRepo->findConMenu($id);
        if (!$piatto) {
            http_response_code(404);
            die('Piatto non trovato.');
        }
        View::render('piatto/storico', [
            'piatto' => $piatto,
            'voci' => $this->storicoRepo->forPiatto($id),
        ]);
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
        foreach ($input['liste'] ?? [] as $lista) {
            $portataId = (int) ($lista['portata_id'] ?? 0);
            foreach ($lista['ids'] ?? [] as $ordine => $piattoId) {
                $this->piattoRepo->spostaOrdina((int) $piattoId, $portataId, $ordine + 1);
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    /** @return array<string, mixed> */
    private function datiDaForm(): array
    {
        $prezzoTesto = PrezzoRepairService::formatta(trim((string) ($_POST['prezzo_testo'] ?? '')));
        $tracce = trim((string) ($_POST['tracce_di'] ?? ''));

        return [
            'nome' => trim((string) ($_POST['nome'] ?? '')),
            'descrizione' => trim((string) ($_POST['descrizione'] ?? '')) ?: null,
            'prezzo_testo' => $prezzoTesto,
            'prezzo_numero' => $this->estraiPrimoNumero($prezzoTesto),
            'tracce_di' => $tracce ?: null,
            'note_interne' => trim((string) ($_POST['note_interne'] ?? '')) ?: null,
        ];
    }

    private function estraiPrimoNumero(string $prezzoTesto): ?float
    {
        if (preg_match('/(\d+(?:[.,]\d+)?)/', $prezzoTesto, $m)) {
            return (float) str_replace(',', '.', $m[1]);
        }
        return null;
    }

    /** @return array<int, int> */
    private function allergeniPostati(string $campo): array
    {
        $valori = $_POST[$campo] ?? [];
        if (!is_array($valori)) {
            return [];
        }
        return array_map('intval', $valori);
    }
}
