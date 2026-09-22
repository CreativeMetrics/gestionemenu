<?php

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Support\View;

class AuthController
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        View::render('auth/login', ['errore' => null]);
    }

    public function login(): void
    {
        Csrf::verifyOrFail();
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '' || !Auth::attempt($email, $password)) {
            View::render('auth/login', ['errore' => 'Email o password non corretti.']);
            return;
        }

        redirect('/');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
