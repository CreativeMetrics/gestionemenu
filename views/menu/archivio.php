<h1>Archivio menu</h1>
<p class="help-text">Menu passati, in sola lettura. Puoi duplicarne uno come base per la prossima stagione.</p>

<?php if (empty($menus)): ?>
    <p>Nessun menu archiviato.</p>
<?php endif; ?>

<?php foreach ($menus as $m): ?>
    <div class="card" style="display:flex; justify-content: space-between; align-items:center;">
        <a href="/menu/<?= (int) $m['id'] ?>" style="text-decoration:none; color:inherit;">
            <strong><?= e(stagione_label($m['stagione'])) ?> <?= (int) $m['anno'] ?></strong>
        </a>
        <span class="<?= stato_badge_class($m['stato']) ?>"><?= e(stato_label($m['stato'])) ?></span>
    </div>
<?php endforeach; ?>

<p><a href="/">← Torna ai menu attivi</a></p>
