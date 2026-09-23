<h1>Storico modifiche</h1>
<p class="help-text">Piatto: <strong><?= e($piatto['nome']) ?></strong></p>

<?php if (empty($voci)): ?>
    <p>Nessuna modifica registrata.</p>
<?php endif; ?>

<div class="tabella-scroll">
<table class="tabella-semplice">
    <thead><tr><th>Quando</th><th>Chi</th><th>Campo</th><th>Prima</th><th>Dopo</th></tr></thead>
    <tbody>
    <?php foreach ($voci as $v): ?>
        <tr>
            <td><?= e(date('d/m/Y H:i', strtotime($v['creato_il']))) ?></td>
            <td><?= e($v['user_nome'] ?? '—') ?></td>
            <td><?= e($v['campo']) ?></td>
            <td><?= e(mb_strimwidth((string) $v['valore_precedente'], 0, 60, '…')) ?></td>
            <td><?= e(mb_strimwidth((string) $v['valore_nuovo'], 0, 60, '…')) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<p><a href="/menu/<?= (int) $piatto['menu_id'] ?>">← Torna al menu</a></p>
