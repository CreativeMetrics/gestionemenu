<?php use App\Csrf; ?>
<h1>Impostazioni</h1>

<p><a href="/impostazioni/utenti">Gestione utenti →</a></p>
<p><a href="/impostazioni/foto">Gestione foto (scarica/elimina in blocco) →</a></p>

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
        <p class="help-text">
            La descrizione del piatto (quando c'è) condivide lo stesso stile del nome, su una riga
            a parte all'interno dello stesso paragrafo — non serve uno stile a parte. Anche il
            nome della portata (es. "Antipasti") viene sempre esportato tutto minuscolo, qualunque
            maiuscola/minuscola usi qui nell'app.
        </p>
        <div class="form-riga">
            <div>
                <label for="indesign_stile_prezzo">Stile carattere — Prezzo</label>
                <input type="text" name="indesign_stile_prezzo" id="indesign_stile_prezzo" value="<?= e($impostazioni['indesign_stile_prezzo']) ?>">
                <div class="help-text">
                    Nome e prezzo stanno sulla stessa riga (separati da una tabulazione): questo
                    <strong>non</strong> è uno stile di paragrafo, ma uno stile di carattere da
                    creare in InDesign, applicato solo al prezzo. Se vuoi il prezzo allineato a
                    destra, imposta un tab-stop nello stile di paragrafo "Nome piatto".
                </div>
            </div>
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
        <p class="help-text">
            Importante: in InDesign lo stile carattere sopra va creato <strong>con il font già
            impostato al suo interno</strong> (Famiglia: quella indicata in "Font icone allergeni",
            Stile: quella indicata in "Stile font"), non lasciato vuoto — altrimenti le icone
            potrebbero non comparire con l'aspetto giusto.
        </p>
        <div style="margin-top:1rem;"><button type="submit">Salva stili</button></div>
    </form>
</div>

<div class="card">
    <h2 style="margin-top:0; font-size:1.05rem;">Correggi formato prezzi già importati</h2>
    <p class="help-text">
        Il formato giusto per i prezzi nell'impaginato è "numero€" (es. <code>17€</code>, supplementi
        <code>20€ +7,5€</code>). Se hai importato piatti prima che questa correzione fosse disponibile,
        i loro prezzi potrebbero essere ancora nel vecchio formato (es. <code>17</code> senza €, o
        <code>€ 20 / +€ 7,5</code>). Questo pulsante li sistema tutti in un click, senza toccare
        prezzi già corretti o scritti a mano in un formato diverso.
    </p>
    <form method="post" action="/impostazioni/correggi-prezzi">
        <?= Csrf::field() ?>
        <button type="submit" class="btn-secondario">Correggi formato prezzi</button>
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
        <div class="tabella-scroll">
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
        </div>
        <div style="margin-top:1rem;"><button type="submit">Salva mappa glifi</button></div>
    </form>
</div>
