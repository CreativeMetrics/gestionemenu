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
    <label for="foto">Carica nuova foto (anche dalla fotocamera del telefono)</label>
    <input type="file" id="foto" name="foto" accept="image/*" capture="environment" required>
    <div style="margin-top:0.8rem;">
        <button type="submit">Carica</button>
        <a class="btn btn-secondario" href="/menu/<?= (int) $piatto['menu_id'] ?>">Annulla</a>
    </div>
</form>
