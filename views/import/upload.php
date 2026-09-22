<?php use App\Csrf; ?>
<h1>Importa CSV dal foglio Google</h1>
<p class="help-text">Carica l'export del vecchio Google Sheet. Al passo successivo potrai controllare la mappatura delle colonne e un'anteprima prima di salvare.</p>

<form method="post" action="/import/anteprima" enctype="multipart/form-data" class="card">
    <?= Csrf::field() ?>
    <label for="csv">File CSV</label>
    <input type="file" id="csv" name="csv" accept=".csv,text/csv" required>

    <label>Destinazione</label>
    <div class="form-riga">
        <div>
            <label for="menu_id" style="font-weight:400;">Menu esistente</label>
            <select name="menu_id" id="menu_id">
                <option value="0">— crea un nuovo menu —</option>
                <?php foreach ($menus as $m): ?>
                    <option value="<?= (int) $m['id'] ?>"><?= e(stagione_label($m['stagione'])) ?> <?= (int) $m['anno'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="stagione" style="font-weight:400;">oppure nuova stagione</label>
            <select name="stagione" id="stagione">
                <option value="">—</option>
                <option value="primavera">Primavera</option>
                <option value="estate">Estate</option>
                <option value="autunno">Autunno</option>
                <option value="inverno">Inverno</option>
            </select>
        </div>
        <div>
            <label for="anno" style="font-weight:400;">anno</label>
            <input type="number" name="anno" id="anno" value="<?= (int) date('Y') ?>">
        </div>
    </div>

    <div style="margin-top:1rem;">
        <button type="submit">Continua →</button>
    </div>
</form>
