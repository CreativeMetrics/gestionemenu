<?php use App\Csrf; ?>
<h1>Impostazioni</h1>

<p><a href="/impostazioni/utenti">Gestione utenti →</a></p>

<div class="card">
    <h2 style="margin-top:0; font-size:1.05rem;">Export InDesign — nomi stili</h2>
    <p class="help-text">Devono corrispondere esattamente ai nomi degli stili di paragrafo/carattere creati nel documento InDesign.</p>
    <form method="post" action="/impostazioni/stili">
        <?= Csrf::field() ?>
        <label for="indesign_encoding">Codifica file</label>
        <select name="indesign_encoding" id="indesign_encoding">
            <option value="UNICODE-WIN" <?= $impostazioni['indesign_encoding'] === 'UNICODE-WIN' ? 'selected' : '' ?>>Unicode Windows</option>
            <option value="UNICODE-MAC" <?= $impostazioni['indesign_encoding'] === 'UNICODE-MAC' ? 'selected' : '' ?>>Unicode Mac</option>
        </select>

        <div class="form-riga">
            <div>
                <label for="indesign_stile_portata">Stile paragrafo — Portata</label>
                <input type="text" name="indesign_stile_portata" id="indesign_stile_portata" value="<?= e($impostazioni['indesign_stile_portata']) ?>">
            </div>
            <div>
                <label for="indesign_stile_piatto">Stile paragrafo — Nome piatto</label>
                <input type="text" name="indesign_stile_piatto" id="indesign_stile_piatto" value="<?= e($impostazioni['indesign_stile_piatto']) ?>">
            </div>
        </div>
        <div class="form-riga">
            <div>
                <label for="indesign_stile_descrizione">Stile paragrafo — Descrizione</label>
                <input type="text" name="indesign_stile_descrizione" id="indesign_stile_descrizione" value="<?= e($impostazioni['indesign_stile_descrizione']) ?>">
            </div>
            <div>
                <label for="indesign_stile_prezzo">Stile paragrafo — Prezzo</label>
                <input type="text" name="indesign_stile_prezzo" id="indesign_stile_prezzo" value="<?= e($impostazioni['indesign_stile_prezzo']) ?>">
            </div>
        </div>
        <div class="form-riga">
            <div>
                <label for="indesign_stile_carattere_allergeni">Stile carattere — icone allergeni</label>
                <input type="text" name="indesign_stile_carattere_allergeni" id="indesign_stile_carattere_allergeni" value="<?= e($impostazioni['indesign_stile_carattere_allergeni']) ?>">
            </div>
            <div>
                <label for="indesign_font_allergeni">Font icone allergeni</label>
                <input type="text" name="indesign_font_allergeni" id="indesign_font_allergeni" value="<?= e($impostazioni['indesign_font_allergeni']) ?>">
            </div>
            <div>
                <label for="indesign_fontstyle_allergeni">Stile font (es. Outline)</label>
                <input type="text" name="indesign_fontstyle_allergeni" id="indesign_fontstyle_allergeni" value="<?= e($impostazioni['indesign_fontstyle_allergeni']) ?>">
            </div>
        </div>
        <div style="margin-top:1rem;"><button type="submit">Salva stili</button></div>
    </form>
</div>

<div class="card">
    <h2 style="margin-top:0; font-size:1.05rem;">Mappa allergene → lettera del font Allergen-Outline</h2>
    <p class="help-text">
        Il font non usa codici Unicode dedicati: ogni icona è una normale lettera maiuscola digitata con quel font.
        7 valori sono già noti (estratti dalla legenda del menu Autunno 2026); gli altri vanno individuati aprendo
        il font in InDesign e provando le lettere libere — vedi il README per il procedimento.
    </p>
    <form method="post" action="/impostazioni/glifi">
        <?= Csrf::field() ?>
        <table class="tabella-semplice">
            <thead><tr><th>Allergene</th><th>Lettera</th></tr></thead>
            <tbody>
            <?php foreach ($allergeni as $a): ?>
                <tr>
                    <td><?= e($a['nome']) ?></td>
                    <td><input type="text" maxlength="4" style="width:4rem;" name="glifo[<?= (int) $a['id'] ?>]" value="<?= e($a['glifo_unicode'] ?? '') ?>"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="margin-top:1rem;"><button type="submit">Salva mappa glifi</button></div>
    </form>
</div>
