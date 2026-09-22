<?php
use App\Csrf;

$modifica = $piatto !== null;
$azione = $modifica ? '/piatti/' . (int) $piatto['id'] . '/modifica' : '/piatti';
?>
<h1><?= $modifica ? 'Modifica piatto' : 'Nuovo piatto' ?></h1>
<p class="help-text">Portata: <strong><?= e($portata['nome']) ?></strong></p>

<form method="post" action="<?= e($azione) ?>" class="card">
    <?= Csrf::field() ?>
    <input type="hidden" name="portata_id" value="<?= (int) $portata['id'] ?>">

    <label for="nome">Nome piatto</label>
    <input type="text" id="nome" name="nome" required value="<?= e($piatto['nome'] ?? '') ?>">

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

    <div style="margin-top:1.2rem; display:flex; gap:0.6rem;">
        <button type="submit">Salva</button>
        <a class="btn btn-secondario" href="/menu/<?= (int) $portata['menu_id'] ?>">Annulla</a>
    </div>
</form>
