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
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="app-header">
    <a href="/">🍽 <?= e($nomeLocale) ?></a>
    <?php if ($utente): ?>
    <nav class="app-nav">
        <a href="/">Menu</a>
        <a href="/foto/mancanti">Foto mancanti</a>
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
