<?php
// Copia questo file in config.local.php e inserisci i dati reali.
// config.local.php NON va versionato (vedi .gitignore).
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'gestionemenu',
        'user' => 'gestionemenu',
        'pass' => 'cambia_questa_password',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        // URL base senza slash finale, es. https://menu.fattoriamaria.it
        'base_url' => 'http://localhost',
        // Nome ristorante mostrato in testata
        'nome_locale' => 'Fattoria Maria',
        'timezone' => 'Europe/Rome',
    ],
    'upload' => [
        // Percorso assoluto della cartella foto piatti (deve essere scrivibile da PHP)
        'dir' => __DIR__ . '/../public/uploads/piatti',
        'max_lato_lungo_px' => 1600,
        'jpeg_quality' => 82,
    ],
];
