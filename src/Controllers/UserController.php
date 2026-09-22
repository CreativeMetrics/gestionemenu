<?php

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Repositories\UserRepository;
use App\Support\View;

class UserController
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    public function index(): void
    {
        Auth::requireAdmin();
        View::render('settings/utenti', ['utenti' => $this->userRepo->all()]);
    }

    public function crea(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $ruolo = ($_POST['ruolo'] ?? 'editor') === 'admin' ? 'admin' : 'editor';

        if ($nome === '' || $email === '' || strlen($password) < 8) {
            flash('errore', 'Nome, email e password (minimo 8 caratteri) sono obbligatori.');
            redirect('/impostazioni/utenti');
            return;
        }
        if ($this->userRepo->emailEsiste($email)) {
            flash('errore', 'Esiste già un utente con questa email.');
            redirect('/impostazioni/utenti');
            return;
        }
        $this->userRepo->create($nome, $email, $password, $ruolo);
        flash('ok', 'Utente creato.');
        redirect('/impostazioni/utenti');
    }

    public function attivaDisattiva(array $params): void
    {
        $admin = Auth::requireAdmin();
        Csrf::verifyOrFail();
        $id = (int) $params['id'];
        if ($id === (int) $admin['id']) {
            flash('errore', 'Non puoi disattivare il tuo stesso account.');
            redirect('/impostazioni/utenti');
            return;
        }
        $utente = $this->userRepo->findById($id);
        if ($utente) {
            $this->userRepo->setAttivo($id, !$utente['attivo']);
        }
        redirect('/impostazioni/utenti');
    }

    public function cambiaRuolo(array $params): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();
        $id = (int) $params['id'];
        $ruolo = ($_POST['ruolo'] ?? 'editor') === 'admin' ? 'admin' : 'editor';
        $this->userRepo->updateRuolo($id, $ruolo);
        flash('ok', 'Ruolo aggiornato.');
        redirect('/impostazioni/utenti');
    }

    public function reimpostaPassword(array $params): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();
        $id = (int) $params['id'];
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($password) < 8) {
            flash('errore', 'La password deve avere almeno 8 caratteri.');
            redirect('/impostazioni/utenti');
            return;
        }
        $this->userRepo->updatePassword($id, $password);
        flash('ok', 'Password aggiornata.');
        redirect('/impostazioni/utenti');
    }
}
