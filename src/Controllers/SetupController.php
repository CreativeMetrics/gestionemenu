<?php

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Repositories\UserRepository;
use App\Support\View;

/**
 * Creazione del primo utente admin dal browser, per chi non ha accesso SSH al server
 * (es. hosting condiviso Plesk) e quindi non può usare cron/crea_utente.php da riga di comando.
 * Funziona SOLO finché la tabella users è vuota: dopo la creazione del primo utente si disattiva
 * da sola. Da quel momento in poi i nuovi utenti si creano da Impostazioni → Utenti (admin).
 */
class SetupController
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    public function form(): void
    {
        if ($this->userRepo->count() > 0) {
            flash('info', 'Il setup iniziale è già stato completato: accedi con le tue credenziali.');
            redirect('/login');
            return;
        }
        View::render('auth/setup', ['errore' => null]);
    }

    public function crea(): void
    {
        if ($this->userRepo->count() > 0) {
            flash('info', 'Il setup iniziale è già stato completato: accedi con le tue credenziali.');
            redirect('/login');
            return;
        }
        Csrf::verifyOrFail();

        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $conferma = (string) ($_POST['password_conferma'] ?? '');

        if ($nome === '' || $email === '' || strlen($password) < 8) {
            View::render('auth/setup', ['errore' => 'Nome, email e una password di almeno 8 caratteri sono obbligatori.']);
            return;
        }
        if ($password !== $conferma) {
            View::render('auth/setup', ['errore' => 'Le due password non coincidono.']);
            return;
        }

        $this->userRepo->create($nome, $email, $password, 'admin');
        Auth::attempt($email, $password);

        flash('ok', 'Account amministratore creato. Benvenuto!');
        redirect('/');
    }
}
