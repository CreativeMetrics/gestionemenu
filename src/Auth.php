<?php

namespace App;

use App\Repositories\UserRepository;

class Auth
{
    private static ?array $utente = null;

    public static function attempt(string $email, string $password): bool
    {
        $repo = new UserRepository();
        $utente = $repo->findByEmail($email);
        if (!$utente || !password_verify($password, $utente['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $utente['id'];
        self::$utente = $utente;
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$utente !== null) {
            return self::$utente;
        }
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            return null;
        }
        $repo = new UserRepository();
        $utente = $repo->findById((int) $id);
        if (!$utente || !$utente['attivo']) {
            return null;
        }
        self::$utente = $utente;
        return $utente;
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u !== null && $u['ruolo'] === 'admin';
    }

    public static function requireLogin(): array
    {
        $u = self::user();
        if ($u === null) {
            header('Location: /login');
            exit;
        }
        return $u;
    }

    public static function requireAdmin(): array
    {
        $u = self::requireLogin();
        if ($u['ruolo'] !== 'admin') {
            http_response_code(403);
            die('Accesso riservato agli amministratori.');
        }
        return $u;
    }
}
