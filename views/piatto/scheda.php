<?php use App\Csrf; ?>
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
    <h1 style="margin-bottom:0;"><?= e($piatto['nome']) ?></h1>
    <a class="btn btn-secondario" href="/menu/<?= (int) $piatto['menu_id'] ?>">← Torna al menu</a>
</div>
<p class="help-text">
    <?= e($piatto['portata_nome']) ?>
    <?php if ($menu): ?>
        · <?= e(stagione_label($menu['stagione'])) ?> <?= (int) $menu['anno'] ?>
        <span class="<?= stato_badge_class($menu['stato']) ?>"><?= e(stato_label($menu['stato'])) ?></span>
    <?php endif; ?>
</p>

<?php if ($soloLettura): ?>
<div class="flash flash-info">Questo menu è archiviato: la scheda è in sola lettura.</div>
<?php endif; ?>

<?php if (!$soloLettura): ?>
<form method="post" action="/piatti/<?= (int) $piatto['id'] ?>/modifica" class="card">
    <?= Csrf::field() ?>

    <label for="nome">Nome piatto</label>
    <input type="text" id="nome" name="nome" required value="<?= e($piatto['nome']) ?>">

    <label for="descrizione">Descrizione (facoltativa, anche su più righe)</label>
    <textarea id="descrizione" name="descrizione"><?= e($piatto['descrizione'] ?? '') ?></textarea>

    <label for="prezzo_testo">Prezzo</label>
    <input type="text" id="prezzo_testo" name="prezzo_testo" value="<?= e($piatto['prezzo_testo'] ?? '') ?>" placeholder="es. 18 oppure € 20 / +€ 7,5">
    <div class="help-text">Testo libero: puoi scrivere anche supplementi, es. "€ 20 / +€ 7,5".</div>

    <label>Allergeni (Reg. UE 1169/2011)</label>
    <div class="chip-allergeni">
        <?php foreach ($allergeni as $a): ?>
            <label class="chip <?= in_array((int) $a['id'], $allergeniSelezionati, true) ? 'selezionato' : '' ?>">
                <input type="checkbox" name="allergeni[]" value="<?= (int) $a['id'] ?>"
                    <?= in_array((int) $a['id'], $allergeniSelezionati, true) ? 'checked' : '' ?>
                    onchange="this.closest('label').classList.toggle('selezionato', this.checked)">
                <?= e($a['nome']) ?>
            </label>
        <?php endforeach; ?>
    </div>

    <label for="tracce_di">Può contenere tracce di</label>
    <input type="text" id="tracce_di" name="tracce_di" value="<?= e($piatto['tracce_di'] ?? '') ?>" placeholder="es. Frutta a Guscio, Sesamo">

    <label for="note_interne">Note interne (non visibili in stampa)</label>
    <textarea id="note_interne" name="note_interne"><?= e($piatto['note_interne'] ?? '') ?></textarea>

    <div style="margin-top:1.2rem;"><button type="submit">Salva modifiche</button></div>
</form>
<?php else: ?>
<div class="card">
    <?php if (!empty($piatto['descrizione'])): ?><p><?= nl2br(e($piatto['descrizione'])) ?></p><?php endif; ?>
    <p class="piatto-prezzo"><?= e($piatto['prezzo_testo']) ?></p>
    <div class="chip-allergeni">
        <?php foreach ($allergeni as $a): ?>
            <?php if (in_array((int) $a['id'], $allergeniSelezionati, true)): ?>
                <span class="chip selezionato" style="cursor:default;"><?= e($a['nome']) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php if (!empty($piatto['tracce_di'])): ?>
        <p class="help-text">Può contenere tracce di: <?= e($piatto['tracce_di']) ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card" id="foto">
    <h2 style="margin-top:0; font-size:1.05rem;">Foto</h2>

    <?php if (!empty($piatto['foto_path'])): ?>
        <img src="/uploads/piatti/<?= e($piatto['foto_path']) ?>" style="max-width:100%; border-radius:8px;" alt="">
        <?php if (!$soloLettura): ?>
        <form method="post" action="/piatti/<?= (int) $piatto['id'] ?>/foto/rimuovi" style="margin-top:0.6rem;" onsubmit="return confirm('Rimuovere la foto?');">
            <?= Csrf::field() ?>
            <button type="submit" class="btn-pericolo">Rimuovi foto</button>
        </form>
        <?php endif; ?>
    <?php elseif (!empty($piatto['foto_esterna'])): ?>
        <p>✅ Segnato come "foto già presente altrove": non comparirà tra le foto mancanti.</p>
        <?php if (!$soloLettura): ?>
        <form method="post" action="/piatti/<?= (int) $piatto['id'] ?>/foto/esterna">
            <?= Csrf::field() ?>
            <input type="hidden" name="esterna" value="0">
            <button type="submit" class="btn-secondario">Annulla segnalazione</button>
        </form>
        <?php endif; ?>
    <?php else: ?>
        <p class="help-text">Nessuna foto caricata.</p>
    <?php endif; ?>

    <?php if (!$soloLettura): ?>
    <form method="post" action="/piatti/<?= (int) $piatto['id'] ?>/foto" enctype="multipart/form-data" style="margin-top:1rem;">
        <?= Csrf::field() ?>
        <label for="foto">Carica <?= empty($piatto['foto_path']) ? 'una foto' : 'una nuova foto' ?> (tocca per scattare o scegliere dalla libreria)</label>
        <input type="file" id="foto" name="foto" accept="image/*" required>
        <div style="margin-top:0.8rem;"><button type="submit">Carica</button></div>
    </form>

    <?php if (empty($piatto['foto_path']) && empty($piatto['foto_esterna'])): ?>
        <p class="help-text" style="margin-top:1rem;">
            Hai già una foto per questo piatto altrove (es. usata direttamente in InDesign) e non vuoi caricarla qui?
        </p>
        <form method="post" action="/piatti/<?= (int) $piatto['id'] ?>/foto/esterna">
            <?= Csrf::field() ?>
            <input type="hidden" name="esterna" value="1">
            <button type="submit" class="btn-secondario">Segna come già presente</button>
        </form>
    <?php endif; ?>
    <?php endif; ?>
</div>

<div class="card">
    <h2 style="margin-top:0; font-size:1.05rem;">Storico modifiche</h2>
    <?php if (empty($voci)): ?>
        <p class="help-text">Nessuna modifica registrata.</p>
    <?php else: ?>
    <div class="tabella-scroll">
    <table class="tabella-semplice">
        <thead><tr><th>Quando</th><th>Chi</th><th>Campo</th><th>Prima</th><th>Dopo</th></tr></thead>
        <tbody>
        <?php foreach ($voci as $v): ?>
            <tr>
                <td><?= e(date('d/m/Y H:i', strtotime($v['creato_il']))) ?></td>
                <td><?= e($v['user_nome'] ?? '—') ?></td>
                <td><?= e(campo_label($v['campo'])) ?></td>
                <td><?= e(mb_strimwidth((string) $v['valore_precedente'], 0, 60, '…')) ?></td>
                <td><?= e(mb_strimwidth((string) $v['valore_nuovo'], 0, 60, '…')) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php if (!$soloLettura): ?>
<div class="card" style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:0.6rem;">
    <form method="post" action="/piatti/<?= (int) $piatto['id'] ?>/duplica">
        <?= Csrf::field() ?>
        <button type="submit" class="btn-secondario">Duplica piatto</button>
    </form>
    <form method="post" action="/piatti/<?= (int) $piatto['id'] ?>/elimina" onsubmit="return confirm('Eliminare questo piatto?');">
        <?= Csrf::field() ?>
        <button type="submit" class="btn-pericolo">Elimina piatto</button>
    </form>
</div>
<?php endif; ?>
