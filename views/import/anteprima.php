<?php use App\Csrf; ?>
<h1>Anteprima import</h1>

<div class="card">
    <h2 style="margin-top:0; font-size:1rem;">Mappatura colonne</h2>
    <form method="post" action="/import/anteprima" class="form-riga">
        <?= Csrf::field() ?>
        <?php
        $campi = ['categoria' => 'Categoria (→ portata)', 'nome' => 'Nome piatto', 'prezzo' => 'Prezzo', 'allergeni' => 'Allergeni'];
        foreach ($campi as $chiave => $etichetta):
        ?>
            <div>
                <label for="col_<?= $chiave ?>" style="font-weight:400;"><?= e($etichetta) ?></label>
                <select name="col_<?= $chiave ?>" id="col_<?= $chiave ?>">
                    <option value="-1">— nessuna —</option>
                    <?php foreach ($header as $idx => $nomeColonna): ?>
                        <option value="<?= $idx ?>" <?= $mappatura[$chiave] === $idx ? 'selected' : '' ?>><?= e($nomeColonna) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endforeach; ?>
        <div style="align-self:flex-end;"><button type="submit" class="btn-secondario">Aggiorna anteprima</button></div>
    </form>
</div>

<form method="post" action="/import/conferma">
    <?= Csrf::field() ?>

    <div class="card">
        <h2 style="margin-top:0; font-size:1rem;">Destinazione</h2>
        <div class="form-riga">
            <div>
                <label for="menu_id" style="font-weight:400;">Menu esistente</label>
                <select name="menu_id" id="menu_id">
                    <option value="0" <?= $targetMenuId === 0 ? 'selected' : '' ?>>— crea un nuovo menu —</option>
                    <?php foreach ($menus as $m): ?>
                        <option value="<?= (int) $m['id'] ?>" <?= $targetMenuId === (int) $m['id'] ? 'selected' : '' ?>><?= e(stagione_label($m['stagione'])) ?> <?= (int) $m['anno'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="stagione" style="font-weight:400;">oppure nuova stagione</label>
                <select name="stagione" id="stagione">
                    <option value="">—</option>
                    <?php foreach (['primavera', 'estate', 'autunno', 'inverno'] as $s): ?>
                        <option value="<?= $s ?>" <?= $nuovaStagione === $s ? 'selected' : '' ?>><?= e(stagione_label($s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="anno" style="font-weight:400;">anno</label>
                <input type="number" name="anno" id="anno" value="<?= (int) $nuovoAnno ?>">
            </div>
        </div>
    </div>

    <p class="help-text">Righe selezionate: verranno importate. Le righe placeholder ("(Seleziona)"/"Esempio Piatto") sono deselezionate di default.</p>

    <table class="tabella-semplice">
        <thead><tr><th></th><th>Categoria</th><th>Piatto</th><th>Prezzo</th><th>Allergeni</th></tr></thead>
        <tbody>
        <?php foreach ($righe as $idx => $r): ?>
            <tr style="<?= $r['placeholder'] ? 'opacity:0.5;' : '' ?>">
                <td><input type="checkbox" name="righe[]" value="<?= $idx ?>" <?= !$r['placeholder'] ? 'checked' : '' ?>></td>
                <td><?= e($r['categoria']) ?></td>
                <td>
                    <strong><?= e($r['nome']) ?></strong>
                    <?php if ($r['descrizione']): ?><div class="help-text"><?= e($r['descrizione']) ?></div><?php endif; ?>
                </td>
                <td><?= e($r['prezzo_testo']) ?></td>
                <td>
                    <?php if ($r['allergeni_non_trovati']): ?>
                        <span style="color:var(--colore-errore);">non riconosciuti: <?= e(implode(', ', $r['allergeni_non_trovati'])) ?></span>
                    <?php else: ?>
                        <?= count($r['allergeni_ids']) ?> allergeni
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin:1rem 0;">
        <button type="submit">Conferma e importa</button>
        <a class="btn btn-secondario" href="/import">Ricomincia</a>
    </div>
</form>
