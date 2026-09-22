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
    <p>Tutti i piatti di questo menu hanno una foto. 🎉</p>
<?php endif; ?>

<?php foreach ($piatti as $p): ?>
    <div class="card" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <strong><?= e($p['nome']) ?></strong>
            <div class="help-text"><?= e($p['portata_nome']) ?></div>
        </div>
        <a class="btn btn-small" href="/piatti/<?= (int) $p['id'] ?>/foto">Aggiungi foto</a>
    </div>
<?php endforeach; ?>
