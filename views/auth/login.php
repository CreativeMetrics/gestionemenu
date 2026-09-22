<?php
use App\Csrf;
?>
<div class="card" style="max-width: 380px; margin: 3rem auto;">
    <h1 style="margin-top:0; font-size:1.3rem;">Accedi</h1>
    <?php if (!empty($errore)): ?>
        <div class="flash flash-errore"><?= e($errore) ?></div>
    <?php endif; ?>
    <form method="post" action="/login">
        <?= Csrf::field() ?>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autocomplete="username">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
        <div style="margin-top: 1.2rem;">
            <button type="submit">Accedi</button>
        </div>
    </form>
</div>
