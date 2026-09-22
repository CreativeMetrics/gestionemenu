<?php

namespace App\Support;

class Flash
{
    public static function set(string $tipo, string $messaggio): void
    {
        $_SESSION['flash'][] = ['tipo' => $tipo, 'messaggio' => $messaggio];
    }

    /**
     * @return array<int, array{tipo:string, messaggio:string}>
     */
    public static function pull(): array
    {
        $messaggi = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $messaggi;
    }
}
