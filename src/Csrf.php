<?php

namespace App;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function verify(): bool
    {
        $inviato = $_POST['csrf_token'] ?? '';
        $atteso = $_SESSION['csrf_token'] ?? '';
        return $inviato !== '' && $atteso !== '' && hash_equals($atteso, $inviato);
    }

    /** @param array<string, mixed> $input */
    public static function verifyJson(array $input): bool
    {
        $inviato = (string) ($input['csrf_token'] ?? '');
        $atteso = $_SESSION['csrf_token'] ?? '';
        return $inviato !== '' && $atteso !== '' && hash_equals($atteso, $inviato);
    }

    public static function verifyOrFail(): void
    {
        if (!self::verify()) {
            http_response_code(419);
            die('Sessione scaduta o richiesta non valida. Torna indietro e riprova.');
        }
    }
}
