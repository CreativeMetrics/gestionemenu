<?php use App\Csrf; use App\Auth; ?>
<h1>Utenti</h1>

<table class="tabella-semplice">
    <thead><tr><th>Nome</th><th>Email</th><th>Ruolo</th><th>Stato</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($utenti as $u): ?>
        <tr>
            <td><?= e($u['nome']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td>
                <form method="post" action="/impostazioni/utenti/<?= (int) $u['id'] ?>/ruolo" style="display:inline;">
                    <?= Csrf::field() ?>
                    <select name="ruolo" onchange="this.form.submit()">
                        <option value="editor" <?= $u['ruolo'] === 'editor' ? 'selected' : '' ?>>editor</option>
                        <option value="admin" <?= $u['ruolo'] === 'admin' ? 'selected' : '' ?>>admin</option>
                    </select>
                </form>
            </td>
            <td><?= $u['attivo'] ? 'attivo' : 'disattivato' ?></td>
            <td>
                <form method="post" action="/impostazioni/utenti/<?= (int) $u['id'] ?>/attiva-disattiva" style="display:inline;">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn-small btn-secondario"><?= $u['attivo'] ? 'Disattiva' : 'Riattiva' ?></button>
                </form>
                <details style="display:inline-block;">
                    <summary style="cursor:pointer; display:inline;">reset password</summary>
                    <form method="post" action="/impostazioni/utenti/<?= (int) $u['id'] ?>/password">
                        <?= Csrf::field() ?>
                        <input type="password" name="password" placeholder="nuova password" minlength="8" required>
                        <button type="submit" class="btn-small">Salva</button>
                    </form>
                </details>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="card">
    <h2 style="margin-top:0; font-size:1.05rem;">Nuovo utente</h2>
    <form method="post" action="/impostazioni/utenti" class="form-riga">
        <?= Csrf::field() ?>
        <div><label style="font-weight:400;">Nome</label><input type="text" name="nome" required></div>
        <div><label style="font-weight:400;">Email</label><input type="email" name="email" required></div>
        <div><label style="font-weight:400;">Password</label><input type="password" name="password" minlength="8" required></div>
        <div>
            <label style="font-weight:400;">Ruolo</label>
            <select name="ruolo">
                <option value="editor">editor</option>
                <option value="admin">admin</option>
            </select>
        </div>
        <div style="align-self:flex-end;"><button type="submit">Crea</button></div>
    </form>
</div>

<p><a href="/impostazioni">← Impostazioni</a></p>
