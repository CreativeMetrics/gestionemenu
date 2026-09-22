<?php
use App\Csrf;
use App\Auth;
?>
<h1>Menu</h1>

<div class="card">
    <h2 style="margin-top:0; font-size:1.05rem;">Crea nuovo menu</h2>
    <form method="post" action="/menu/crea-prossima" style="margin-bottom: 0.8rem;">
        <?= Csrf::field() ?>
        <button type="submit">Crea menu della prossima stagione (duplica l'ultimo)</button>
    </form>
    <details>
        <summary style="cursor:pointer; font-size:0.9rem; color:#5b5346;">oppure crea un menu vuoto per una stagione specifica</summary>
        <form method="post" action="/menu/nuovo-vuoto" class="form-riga" style="margin-top:0.6rem;">
            <?= Csrf::field() ?>
            <div>
                <label for="stagione">Stagione</label>
                <select name="stagione" id="stagione">
                    <option value="primavera">Primavera</option>
                    <option value="estate">Estate</option>
                    <option value="autunno">Autunno</option>
                    <option value="inverno">Inverno</option>
                </select>
            </div>
            <div>
                <label for="anno">Anno</label>
                <input type="number" name="anno" id="anno" value="<?= (int) date('Y') ?>">
            </div>
            <div style="align-self: flex-end;">
                <button type="submit" class="btn-secondario">Crea vuoto</button>
            </div>
        </form>
    </details>
    <p class="help-text" style="margin-top:0.8rem;">
        Prima volta? Se hai un export dal vecchio Google Sheet, conviene
        <a href="/import">importare il CSV</a> invece di creare un menu vuoto: porta subito con sé
        portate, piatti, prezzi e allergeni.
    </p>
</div>

<?php if (empty($menus)): ?>
    <p>Nessun menu ancora. Crea il primo menu qui sopra oppure <a href="/import">importa un CSV</a>.</p>
<?php endif; ?>

<?php foreach ($menus as $m): ?>
    <a href="/menu/<?= (int) $m['id'] ?>" class="card" style="display:block; text-decoration:none; color:inherit;">
        <div style="display:flex; justify-content: space-between; align-items:center;">
            <strong><?= e(stagione_label($m['stagione'])) ?> <?= (int) $m['anno'] ?></strong>
            <span class="<?= stato_badge_class($m['stato']) ?>"><?= e(stato_label($m['stato'])) ?></span>
        </div>
    </a>
<?php endforeach; ?>

<p><a href="/archivio">Vai all'archivio dei menu passati →</a></p>
