<?php use App\Csrf; ?>
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

<?php
$etichetteGruppo = ['principale' => 'Menu principale', 'dolci_drink' => 'Dolci & drink'];
foreach ($etichetteGruppo as $gruppo => $etichetta):
    $stato = $statoExport[$gruppo];
    $mod = $modifiche[$gruppo] ?? null;
?>
<div class="card">
    <h2 style="margin-top:0; font-size:1.05rem;"><?= e($etichetta) ?></h2>
    <?php if ($stato['esportato_il'] === null): ?>
        <p class="help-text" style="margin-top:0;">Non ancora esportato.</p>
    <?php else: ?>
        <p class="help-text" style="margin-top:0;">
            Ultimo export scaricato: <?= e(date('d/m/Y H:i', strtotime($stato['esportato_il']))) ?>
        </p>
    <?php endif; ?>

    <?php if ($stato['ha_modifiche']): ?>
        <p style="color:var(--colore-warn); font-weight:600; margin:0.4rem 0;">
            ⚠ Ci sono modifiche non ancora in questo export.
        </p>
        <?php if ($mod !== null && ($mod['nuovi'] !== [] || $mod['modificati'] !== [] || $mod['rimossi'] !== [])): ?>
        <details open style="margin-bottom:0.6rem;">
            <summary style="cursor:pointer; font-weight:600;">Cosa correggere a mano in InDesign, senza reimportare tutto</summary>
            <ul style="margin:0.5rem 0 0; padding-left:1.2rem;">
                <?php foreach ($mod['nuovi'] as $p): ?>
                    <li><strong><?= e($p['nome']) ?></strong> <span class="help-text">(<?= e($p['portata']) ?>)</span> — nuovo piatto, va aggiunto.</li>
                <?php endforeach; ?>
                <?php foreach ($mod['rimossi'] as $p): ?>
                    <li><strong><?= e($p['nome']) ?></strong> — eliminato, va tolto dall'impaginato.</li>
                <?php endforeach; ?>
                <?php foreach ($mod['modificati'] as $p): ?>
                    <li>
                        <strong><?= e($p['nome']) ?></strong> <span class="help-text">(<?= e($p['portata']) ?>)</span>
                        <ul style="margin:0.2rem 0 0.4rem; padding-left:1.2rem;">
                            <?php foreach ($p['campi'] as $c): ?>
                                <li>
                                    <?= e(campo_label($c['campo'])) ?>:
                                    "<?= e(mb_strimwidth((string) $c['valore_precedente'], 0, 60, '…')) ?>"
                                    → "<?= e(mb_strimwidth((string) $c['valore_nuovo'], 0, 60, '…')) ?>"
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        </details>
        <?php endif; ?>
    <?php endif; ?>

    <div style="display:flex; gap:0.6rem; flex-wrap:wrap; align-items:center;">
        <a class="btn" href="/menu/<?= (int) $menu['id'] ?>/export/indesign/<?= $gruppo ?>">Scarica .txt</a>
        <?php if ($stato['ha_modifiche']): ?>
        <form method="post" action="/menu/<?= (int) $menu['id'] ?>/export/indesign/<?= $gruppo ?>/segna-allineato">
            <?= Csrf::field() ?>
            <button type="submit" class="btn-secondario">Ho già corretto a mano in InDesign</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<p><a href="/menu/<?= (int) $menu['id'] ?>">← Torna al menu</a></p>
