<?php

require __DIR__ . '/../src/autoload.php';
require __DIR__ . '/../src/helpers.php';

use App\Controllers\AuthController;
use App\Controllers\ExportController;
use App\Controllers\FotoController;
use App\Controllers\ImportController;
use App\Controllers\MenuController;
use App\Controllers\PiattoController;
use App\Controllers\PortataController;
use App\Controllers\SettingsController;
use App\Controllers\SetupController;
use App\Controllers\UserController;
use App\Support\Router;

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone'] ?? 'Europe/Rome');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']),
]);
session_start();

$router = new Router();

// Autenticazione
$router->get('/login', fn () => (new AuthController())->loginForm());
$router->post('/login', fn () => (new AuthController())->login());
$router->get('/logout', fn () => (new AuthController())->logout());

// Setup iniziale (creazione primo admin dal browser, disponibile solo se non ci sono ancora utenti)
$router->get('/setup', fn () => (new SetupController())->form());
$router->post('/setup', fn () => (new SetupController())->crea());

// Menu e stagioni
$router->get('/', fn () => (new MenuController())->index());
$router->get('/archivio', fn () => (new MenuController())->archivio());
$router->get('/menu/{id}', fn ($p) => (new MenuController())->show($p));
$router->post('/menu/crea-prossima', fn () => (new MenuController())->creaProssima());
$router->post('/menu/nuovo-vuoto', fn () => (new MenuController())->nuovoVuoto());
$router->post('/menu/{id}/duplica-come-base', fn ($p) => (new MenuController())->duplicaComeBase($p));
$router->post('/menu/{id}/stato', fn ($p) => (new MenuController())->aggiornaStato($p));

// Portate
$router->post('/portate', fn () => (new PortataController())->crea());
$router->post('/portate/{id}/modifica', fn ($p) => (new PortataController())->modifica($p));
$router->post('/portate/{id}/elimina', fn ($p) => (new PortataController())->elimina($p));
$router->post('/portate/riordina', fn () => (new PortataController())->riordina());

// Piatti
$router->get('/piatti/nuovo', fn () => (new PiattoController())->nuovoForm());
$router->get('/piatti/{id}', fn ($p) => (new PiattoController())->scheda($p));
$router->post('/piatti', fn () => (new PiattoController())->crea());
$router->post('/piatti/riordina', fn () => (new PiattoController())->riordina());
$router->post('/piatti/{id}/modifica', fn ($p) => (new PiattoController())->modifica($p));
$router->post('/piatti/{id}/duplica', fn ($p) => (new PiattoController())->duplica($p));
$router->post('/piatti/{id}/elimina', fn ($p) => (new PiattoController())->elimina($p));

// Foto
$router->post('/piatti/{id}/foto', fn ($p) => (new FotoController())->carica($p));
$router->post('/piatti/{id}/foto/rimuovi', fn ($p) => (new FotoController())->rimuovi($p));
$router->post('/piatti/{id}/foto/esterna', fn ($p) => (new FotoController())->segnaEsterna($p));
$router->get('/foto/mancanti', fn () => (new FotoController())->mancanti());
$router->get('/impostazioni/foto', fn () => (new FotoController())->gestione());
$router->post('/impostazioni/foto/elimina', fn () => (new FotoController())->eliminaMultiple());
$router->get('/impostazioni/foto/scarica/{file}', fn ($p) => (new FotoController())->scarica($p));

// Import CSV
$router->get('/import', fn () => (new ImportController())->form());
$router->post('/import/anteprima', fn () => (new ImportController())->anteprima());
$router->post('/import/conferma', fn () => (new ImportController())->conferma());

// Export
$router->get('/menu/{id}/export/csv', fn ($p) => (new ExportController())->csv($p));
$router->get('/menu/{id}/export/indesign', fn ($p) => (new ExportController())->indesignMenu($p));
$router->get('/menu/{id}/export/indesign/{gruppo}', fn ($p) => (new ExportController())->indesignFile($p));
$router->post('/menu/{id}/export/indesign/{gruppo}/segna-allineato', fn ($p) => (new ExportController())->segnaAllineato($p));
$router->get('/menu/{id}/stampa', fn ($p) => (new ExportController())->stampa($p));

// Impostazioni
$router->get('/impostazioni', fn () => (new SettingsController())->index());
$router->post('/impostazioni/stili', fn () => (new SettingsController())->salvaStili());
$router->post('/impostazioni/glifi', fn () => (new SettingsController())->salvaGlifi());
$router->post('/impostazioni/correggi-prezzi', fn () => (new SettingsController())->correggiPrezzi());
$router->get('/impostazioni/utenti', fn () => (new UserController())->index());
$router->post('/impostazioni/utenti', fn () => (new UserController())->crea());
$router->post('/impostazioni/utenti/{id}/attiva-disattiva', fn ($p) => (new UserController())->attivaDisattiva($p));
$router->post('/impostazioni/utenti/{id}/ruolo', fn ($p) => (new UserController())->cambiaRuolo($p));
$router->post('/impostazioni/utenti/{id}/password', fn ($p) => (new UserController())->reimpostaPassword($p));

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
