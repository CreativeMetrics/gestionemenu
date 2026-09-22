<?php
use App\Csrf;
?>
<div class="card" style="max-width: 420px; margin: 3rem auto;">
    <h1 style="margin-top:0; font-size:1.3rem;">Crea il primo account amministratore</h1>
    <p class="help-text">
        Questa pagina è disponibile solo adesso, perché il database non ha ancora nessun utente.
        Dopo aver creato questo account amministratore si disattiva da sola: i prossimi utenti si
        creano da Impostazioni → Utenti.
    </p>
    <?php if (!empty($errore)): ?>
        <div class="flash flash-errore"><?= e($errore) ?></div>
    <?php endif; ?>
    <form method="post" action="/setup">
        <?= Csrf::field() ?>
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" required value="<?= e(old('nome')) ?>">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autocomplete="username" value="<?= e(old('email')) ?>">
        <label for="password">Password (minimo 8 caratteri)</label>
        <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
        <label for="password_conferma">Conferma password</label>
        <input type="password" id="password_conferma" name="password_conferma" minlength="8" required autocomplete="new-password">
        <div style="margin-top: 1.2rem;">
            <button type="submit">Crea account e accedi</button>
        </div>
    </form>
</div>
