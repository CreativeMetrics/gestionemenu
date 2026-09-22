<?php
/**
 * Da eseguire una volta al giorno via cron di Plesk:
 *   php /var/www/vhosts/tuodominio.it/gestionemenu/cron/crea_menu_stagione.php
 *
 * Crea il menu della stagione successiva duplicando l'ultimo esistente quando si entra
 * nella finestra dei 30 giorni prima del suo inizio. Idempotente: se il menu esiste già
 * non fa nulla, quindi può essere eseguito ogni giorno senza rischio di doppioni.
 */

require __DIR__ . '/../src/autoload.php';
require __DIR__ . '/../src/helpers.php';

use App\Repositories\MenuRepository;
use App\Services\SeasonService;

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone'] ?? 'Europe/Rome');

$seasonService = new SeasonService();
$menuRepo = new MenuRepository();

$nuovoId = $seasonService->eseguiCreazioneSeDovuta();

if ($nuovoId === null) {
    echo date('c') . " — nessuna azione necessaria.\n";
    exit(0);
}

$menu = $menuRepo->find($nuovoId);
echo date('c') . sprintf(
    " — creato nuovo menu: %s %d (id %d), duplicato dall'ultimo menu esistente.\n",
    stagione_label($menu['stagione']),
    $menu['anno'],
    $nuovoId
);
