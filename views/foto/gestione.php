<?php use App\Csrf; ?>
<h1>Gestione foto</h1>
<p class="help-text">
    Tutti i file caricati nella cartella foto, indipendentemente dal menu. Da qui puoi scaricarli
    (con un nome leggibile basato sul piatto) o eliminarli in blocco per liberare spazio.
</p>

<?php if (empty($file)): ?>
    <p>Nessuna foto caricata.</p>
<?php else: ?>

<p class="help-text">
    <?= count($file) ?> file, <?= number_format($totaleByte / 1048576, 1, ',', '.') ?> MB totali.
</p>

<form method="post" action="/impostazioni/foto/elimina" onsubmit="return confirm('Eliminare le foto selezionate? Non si può annullare.');">
    <?= Csrf::field() ?>
    <div style="margin-bottom:0.6rem;">
        <button type="submit" class="btn-pericolo">Elimina selezionate</button>
    </div>

    <?php foreach ($file as $f): ?>
        <div class="card" style="display:flex; align-items:center; gap:0.8rem;">
            <input type="checkbox" name="file[]" value="<?= e($f['nome']) ?>" style="width:1.3rem; height:1.3rem; flex-shrink:0;">
            <img src="/uploads/piatti/<?= e($f['nome']) ?>" alt="" class="piatto-thumb" style="width:64px; height:64px;">
            <div style="flex:1; min-width:0;">
                <?php if ($f['piatto']): ?>
                    <strong><?= e($f['piatto']['nome']) ?></strong>
                    <div class="help-text">
                        <?= e($f['piatto']['portata_nome']) ?> ·
                        <?= e(stagione_label($f['piatto']['stagione'])) ?> <?= (int) $f['piatto']['anno'] ?>
                    </div>
                <?php else: ?>
                    <strong style="color:var(--colore-warn);">Non collegata a nessun piatto</strong>
                    <div class="help-text">Il piatto è stato eliminato o la foto non è più in uso.</div>
                <?php endif; ?>
                <div class="help-text">
                    <?= e($f['nome']) ?> · <?= number_format($f['dimensione'] / 1024, 0) ?> KB ·
                    <?= e(date('d/m/Y H:i', $f['modificato'])) ?>
                </div>
            </div>
            <a class="btn btn-small btn-secondario" href="/impostazioni/foto/scarica/<?= rawurlencode($f['nome']) ?>">Scarica</a>
        </div>
    <?php endforeach; ?>

    <div style="margin-top:0.6rem;">
        <button type="submit" class="btn-pericolo">Elimina selezionate</button>
    </div>
</form>
<?php endif; ?>

<p><a href="/impostazioni">← Impostazioni</a></p>
