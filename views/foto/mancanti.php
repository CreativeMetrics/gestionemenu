<?php use App\Csrf; ?>
<h1>Foto piatti mancanti</h1>

<?php if (empty($menus)): ?>
    <p>Non c'è ancora nessun menu. <a href="/">Crea il primo menu</a> oppure <a href="/import">importa un CSV</a>, poi torna qui.</p>
    <?php return; ?>
<?php endif; ?>

<form method="get" action="/foto/mancanti" class="card form-riga">
    <div>
        <label for="menu_id">Menu</label>
        <select name="menu_id" id="menu_id" onchange="this.form.submit()">
            <?php foreach ($menus as $m): ?>
                <option value="<?= (int) $m['id'] ?>" <?= $menuSelezionato && (int) $menuSelezionato['id'] === (int) $m['id'] ? 'selected' : '' ?>>
                    <?= e(stagione_label($m['stagione'])) ?> <?= (int) $m['anno'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if (empty($piatti)): ?>
    <p>Tutti i piatti di questo menu hanno una foto (o sono segnati come già presenti). 🎉</p>
<?php else: ?>
    <p class="help-text">
        Hai già una foto per questo piatto altrove (es. usata direttamente in InDesign) e non vuoi
        caricarla qui? Usa "Segna come già presente": il piatto sparisce da questo elenco senza
        bisogno di un file.
    </p>
<?php endif; ?>

<?php foreach ($piatti as $p): ?>
    <div class="card" style="display:flex; justify-content:space-between; align-items:center; gap:0.6rem; flex-wrap:wrap;">
        <div>
            <strong><?= e($p['nome']) ?></strong>
            <div class="help-text"><?= e($p['portata_nome']) ?></div>
        </div>
        <div class="piatto-azioni">
            <a class="btn btn-small" href="/piatti/<?= (int) $p['id'] ?>#foto">Aggiungi foto</a>
            <form method="post" action="/piatti/<?= (int) $p['id'] ?>/foto/esterna" style="display:inline;">
                <?= Csrf::field() ?>
                <input type="hidden" name="esterna" value="1">
                <input type="hidden" name="origine" value="mancanti">
                <button type="submit" class="btn btn-small btn-secondario">Segna come già presente</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>
