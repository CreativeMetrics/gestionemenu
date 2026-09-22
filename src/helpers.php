<?php

use App\Support\Flash;

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function config_get(string $chiave, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }
    return $config[$chiave] ?? $default;
}

function base_url(string $path = ''): string
{
    $base = config_get('app', [])['base_url'] ?? '';
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function stagione_label(string $stagione): string
{
    return [
        'primavera' => 'Primavera',
        'estate' => 'Estate',
        'autunno' => 'Autunno',
        'inverno' => 'Inverno',
    ][$stagione] ?? ucfirst($stagione);
}

function stato_label(string $stato): string
{
    return [
        'bozza' => 'Bozza',
        'in_revisione' => 'In revisione',
        'pubblicato' => 'Pubblicato',
        'archiviato' => 'Archiviato',
    ][$stato] ?? ucfirst($stato);
}

function stato_badge_class(string $stato): string
{
    return [
        'bozza' => 'badge badge-bozza',
        'in_revisione' => 'badge badge-revisione',
        'pubblicato' => 'badge badge-pubblicato',
        'archiviato' => 'badge badge-archiviato',
    ][$stato] ?? 'badge';
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $tipo, string $messaggio): void
{
    Flash::set($tipo, $messaggio);
}

function old(string $chiave, string $default = ''): string
{
    return $_SESSION['old_input'][$chiave] ?? $default;
}
