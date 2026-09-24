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

/**
 * Aggiunge all'URL di un file in public/assets un "?v=" basato sulla data di ultima modifica del
 * file: cambia da solo ogni volta che il file viene sostituito su Plesk, così il browser (o una
 * cache intermedia) è costretto a scaricare la versione nuova invece di quella vecchia in cache.
 */
function asset_url(string $percorso): string
{
    $assoluto = __DIR__ . '/../public' . $percorso;
    $versione = is_file($assoluto) ? filemtime($assoluto) : time();
    return $percorso . '?v=' . $versione;
}

/** Etichetta leggibile per un nome di campo registrato nello storico modifiche di un piatto. */
function campo_label(string $campo): string
{
    return [
        'nome' => 'Nome',
        'descrizione' => 'Descrizione',
        'prezzo_testo' => 'Prezzo',
        'tracce_di' => 'Tracce di',
        'note_interne' => 'Note interne',
        'allergeni' => 'Allergeni',
    ][$campo] ?? ucfirst($campo);
}

/** Classe CSS da applicare alla voce di navigazione corrispondente alla pagina corrente. */
function nav_attivo(string $path): string
{
    $corrente = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $attivo = $path === '/' ? $corrente === '/' : str_starts_with($corrente, $path);
    return $attivo ? 'nav-attivo' : '';
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
