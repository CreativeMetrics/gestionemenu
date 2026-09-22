<?php
$local = __DIR__ . '/config.local.php';

if (!file_exists($local)) {
    http_response_code(500);
    die(
        "Configurazione mancante.\n" .
        "Copia config/config.example.php in config/config.local.php e inserisci i dati del database.\n"
    );
}

return require $local;
