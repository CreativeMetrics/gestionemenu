<h1>Export InDesign — <?= e(stagione_label($menu['stagione'])) ?> <?= (int) $menu['anno'] ?></h1>
<p class="help-text">
    File InDesign Tagged Text (.txt), codifica Unicode. Prima di importarli assicurati di avere nel documento
    InDesign gli stili di paragrafo/carattere con i nomi configurati in <a href="/impostazioni">Impostazioni</a>.
</p>

<?php if (!empty($problemi)): ?>
<div class="card" style="border-color:var(--colore-warn);">
    <h2 style="margin-top:0; font-size:1.05rem; color:var(--colore-warn);">
        <?= count($problemi) ?> cos<?= count($problemi) === 1 ? 'a' : 'e' ?> da controllare prima di impaginare
    </h2>
    <p class="help-text" style="margin-top:0;">
        Non impedisce il download: sono le cose che tipicamente si scoprono solo dopo aver aperto il file in InDesign.
    </p>
    <ul style="margin:0.3rem 0 0; padding-left:1.2rem;">
        <?php foreach ($problemi as $p): ?>
            <li><strong><?= e($p['piatto']) ?></strong> <span class="help-text">(<?= e($p['portata']) ?>)</span> — <?= e($p['messaggio']) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php else: ?>
<div class="flash flash-ok">Nessun problema rilevato: prezzi e icone allergeni sono a posto.</div>
<?php endif; ?>

<div class="card">
    <h2 style="margin-top:0; font-size:1.05rem;">Menu principale</h2>
    <a class="btn" href="/menu/<?= (int) $menu['id'] ?>/export/indesign/principale">Scarica .txt</a>
</div>

<div class="card">
    <h2 style="margin-top:0; font-size:1.05rem;">Dolci &amp; drink</h2>
    <a class="btn" href="/menu/<?= (int) $menu['id'] ?>/export/indesign/dolci_drink">Scarica .txt</a>
</div>

<p><a href="/menu/<?= (int) $menu['id'] ?>">← Torna al menu</a></p>
