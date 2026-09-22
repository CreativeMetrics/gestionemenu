<?php use App\Csrf; ?>
<h1>Foto piatto</h1>
<p class="help-text"><?= e($piatto['nome']) ?></p>

<?php if (!empty($piatto['foto_path'])): ?>
    <div class="card">
        <img src="/uploads/piatti/<?= e($piatto['foto_path']) ?>" style="max-width:100%; border-radius:8px;" alt="">
        <form method="post" action="/piatti/<?= (int) $piatto['id'] ?>/foto/rimuovi" style="margin-top:0.6rem;" onsubmit="return confirm('Rimuovere la foto?');">
            <?= Csrf::field() ?>
            <button type="submit" class="btn-pericolo">Rimuovi foto</button>
        </form>
    </div>
<?php endif; ?>

<form method="post" action="/piatti/<?= (int) $piatto['id'] ?>/foto" enctype="multipart/form-data" class="card">
    <?= Csrf::field() ?>
    <label for="foto">Carica nuova foto (tocca per scattare o scegliere dalla libreria)</label>
    <input type="file" id="foto" name="foto" accept="image/*" required>
    <div style="margin-top:0.8rem;">
        <button type="submit">Carica</button>
        <a class="btn btn-secondario" href="/menu/<?= (int) $piatto['menu_id'] ?>">Annulla</a>
    </div>
</form>

<?php if (empty($piatto['foto_path'])): ?>
<div class="card">
    <?php if (!empty($piatto['foto_esterna'])): ?>
        <p>✅ Segnato come "foto già presente altrove": non comparirà tra le foto mancanti.</p>
        <form method="post" action="/piatti/<?= (int) $piatto['id'] ?>/foto/esterna">
            <?= Csrf::field() ?>
            <input type="hidden" name="esterna" value="0">
            <button type="submit" class="btn-secondario">Annulla segnalazione</button>
        </form>
    <?php else: ?>
        <p class="help-text">
            Hai già una foto per questo piatto altrove (es. usata direttamente in InDesign) e non
            vuoi caricarla qui?
        </p>
        <form method="post" action="/piatti/<?= (int) $piatto['id'] ?>/foto/esterna">
            <?= Csrf::field() ?>
            <input type="hidden" name="esterna" value="1">
            <button type="submit" class="btn-secondario">Segna come già presente</button>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>
