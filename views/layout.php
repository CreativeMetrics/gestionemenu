<?php

use App\Auth;
use App\Csrf;
use App\Support\Flash;

$utente = Auth::user();
$nomeLocale = config_get('app', [])['nome_locale'] ?? 'Gestione Menu';
?>
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($nomeLocale) ?> — Gestione Menu</title>
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon-16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/img/apple-touch-icon.png">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="app-header">
    <a href="/" class="app-brand"><img src="/assets/img/logo.png" alt="" class="app-logo"> <?= e($nomeLocale) ?></a>
    <?php if ($utente): ?>
    <input type="checkbox" id="nav-toggle" class="nav-toggle-checkbox">
    <label for="nav-toggle" class="nav-toggle-label" aria-label="Apri il menu">☰</label>
    <nav class="app-nav">
        <a href="/">Menu</a>
        <a href="/foto/mancanti">Foto mancanti</a>
        <a href="/import">Importa CSV</a>
        <?php if (Auth::isAdmin()): ?>
        <a href="/impostazioni">Impostazioni</a>
        <?php endif; ?>
        <a href="/logout" onclick="return true;"><?= e($utente['nome']) ?> · esci</a>
    </nav>
    <?php endif; ?>
</header>
<main>
    <?php foreach (Flash::pull() as $f): ?>
        <div class="flash flash-<?= e($f['tipo']) ?>"><?= e($f['messaggio']) ?></div>
    <?php endforeach; ?>
    <?php $contentFile(); ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
