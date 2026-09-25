<?php
/**
 * Da eseguire ogni 15-30 minuti via cron di Plesk:
 *   php /var/www/vhosts/tuodominio.it/gestionemenu/cron/notifica_modifiche.php
 *
 * Se un editor (non admin) ha modificato/creato/eliminato piatti dall'ultima esecuzione, manda
 * un'unica email di riepilogo agli admin che hanno attivato le notifiche (Impostazioni → Utenti).
 * Idempotente: le modifiche già notificate non vengono rimandate, quindi eseguirlo più spesso del
 * necessario non causa duplicati.
 */

require __DIR__ . '/../src/autoload.php';
require __DIR__ . '/../src/helpers.php';

use App\Services\NotificaEmailService;

$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['app']['timezone'] ?? 'Europe/Rome');

$notificate = (new NotificaEmailService())->inviaSeCiSonoModifiche();

echo date('c') . ($notificate > 0
    ? " — digest inviato, {$notificate} modifiche notificate.\n"
    : " — nessuna modifica da notificare.\n");
