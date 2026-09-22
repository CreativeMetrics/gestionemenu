<?php
use App\Csrf;
use App\Auth;

$titolo = stagione_label($menu['stagione']) . ' ' . $menu['anno'];
?>
<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
    <h1 style="margin-bottom:0;"><?= e($titolo) ?> <span class="<?= stato_badge_class($menu['stato']) ?>"><?= e(stato_label($menu['stato'])) ?></span></h1>
</div>

<div class="card no-print">
    <div class="form-riga" style="align-items:center;">
        <?php if (!$soloLettura): ?>
        <form method="post" action="/menu/<?= (int) $menu['id'] ?>/stato">
            <?= Csrf::field() ?>
            <label for="stato">Stato del menu</label>
            <select name="stato" id="stato" onchange="this.form.submit()">
                <?php foreach (['bozza', 'in_revisione', 'pubblicato', 'archiviato'] as $s): ?>
                    <option value="<?= $s ?>" <?= $s === $menu['stato'] ? 'selected' : '' ?>><?= e(stato_label($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php else: ?>
        <form method="post" action="/menu/<?= (int) $menu['id'] ?>/duplica-come-base">
            <?= Csrf::field() ?>
            <button type="submit" class="btn-secondario">Duplica come base per la prossima stagione</button>
        </form>
        <?php endif; ?>
        <div style="flex:1;"></div>
        <a class="btn btn-secondario" href="/menu/<?= (int) $menu['id'] ?>/stampa">Anteprima/stampa</a>
        <a class="btn btn-secondario" href="/menu/<?= (int) $menu['id'] ?>/export/csv">Export CSV</a>
        <a class="btn btn-secondario" href="/menu/<?= (int) $menu['id'] ?>/export/indesign">Export InDesign</a>
    </div>
</div>

<div id="portate-container" data-csrf="<?= e(Csrf::token()) ?>">
<?php foreach ($portate as $portata): ?>
    <div class="portata-blocco">
        <div class="portata-titolo">
            <h2><?= e($portata['nome']) ?> <small style="font-weight:400; color:#8a7f6c;">(<?= $portata['gruppo_impaginato'] === 'principale' ? 'menu principale' : 'dolci &amp; drink' ?>)</small></h2>
            <?php if (!$soloLettura): ?>
            <details class="no-print">
                <summary style="cursor:pointer;">⋯</summary>
                <form method="post" action="/portate/<?= (int) $portata['id'] ?>/modifica" class="form-riga" style="margin-top:0.5rem;">
                    <?= Csrf::field() ?>
                    <div><input type="text" name="nome" value="<?= e($portata['nome']) ?>" required></div>
                    <div>
                        <select name="gruppo_impaginato">
                            <option value="principale" <?= $portata['gruppo_impaginato'] === 'principale' ? 'selected' : '' ?>>Menu principale</option>
                            <option value="dolci_drink" <?= $portata['gruppo_impaginato'] === 'dolci_drink' ? 'selected' : '' ?>>Dolci &amp; drink</option>
                        </select>
                    </div>
                    <div>
                        <input type="text" name="suffisso_export" value="<?= e($portata['suffisso_export'] ?? '') ?>" placeholder="suffisso export, es. **" style="width:9rem;">
                    </div>
                    <button type="submit" class="btn-small">Salva</button>
                </form>
                <div class="help-text" style="margin-top:0.2rem;">
                    Il suffisso si aggiunge solo nell'export InDesign dopo il nome della portata
                    (es. "antipasti**"), non nell'app.
                </div>
                <form method="post" action="/portate/<?= (int) $portata['id'] ?>/elimina" onsubmit="return confirm('Eliminare la portata e tutti i suoi piatti?');" style="margin-top:0.4rem;">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn-small btn-pericolo">Elimina portata</button>
                </form>
            </details>
            <?php endif; ?>
        </div>

        <div class="lista-piatti" data-portata-id="<?= (int) $portata['id'] ?>">
            <?php foreach ($piattiPerPortata[$portata['id']] as $piatto): ?>
                <div class="piatto-riga" data-piatto-id="<?= (int) $piatto['id'] ?>">
                    <?php if (!$soloLettura): ?><div class="piatto-maniglia no-print">☰</div><?php endif; ?>
                    <?php if (!empty($piatto['foto_path'])): ?>
                        <img class="piatto-thumb" src="/uploads/piatti/<?= e($piatto['foto_path']) ?>" alt="">
                    <?php elseif (!empty($piatto['foto_esterna'])): ?>
                        <div class="piatto-thumb-placeholder" title="Foto già presente altrove">foto esterna</div>
                    <?php else: ?>
                        <div class="piatto-thumb-placeholder">no foto</div>
                    <?php endif; ?>
                    <div class="piatto-corpo">
                        <div class="nome"><?= e($piatto['nome']) ?></div>
                        <?php if (!empty($piatto['descrizione'])): ?>
                            <div class="desc"><?= e($piatto['descrizione']) ?></div>
                        <?php endif; ?>
                        <div class="chip-allergeni">
                            <?php foreach ($piatto['allergeni'] as $a): ?>
                                <span class="chip selezionato" style="cursor:default;"><?= e($a['nome']) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!empty($piatto['tracce_di'])): ?>
                            <div class="help-text">Può contenere tracce di: <?= e($piatto['tracce_di']) ?></div>
                        <?php endif; ?>
                        <div class="piatto-meta">
                            <span class="piatto-prezzo"><?= e($piatto['prezzo_testo']) ?></span>
                            <?php if (!$soloLettura): ?>
                            <div class="piatto-azioni no-print">
                                <a class="btn btn-small btn-secondario" href="/piatti/<?= (int) $piatto['id'] ?>/foto">Foto</a>
                                <a class="btn btn-small btn-secondario" href="/piatti/<?= (int) $piatto['id'] ?>/modifica">Modifica</a>
                                <a class="btn btn-small btn-secondario" href="/piatti/<?= (int) $piatto['id'] ?>/storico">Storico</a>
                                <form method="post" action="/piatti/<?= (int) $piatto['id'] ?>/elimina" onsubmit="return confirm('Eliminare questo piatto?');" style="display:inline;">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn-small btn-pericolo">Elimina</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (!$soloLettura): ?>
        <a class="btn btn-secondario btn-small no-print" href="/piatti/nuovo?portata_id=<?= (int) $portata['id'] ?>">+ Aggiungi piatto</a>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

<?php if (!$soloLettura): ?>
<div class="card no-print">
    <form method="post" action="/portate" class="form-riga">
        <?= Csrf::field() ?>
        <input type="hidden" name="menu_id" value="<?= (int) $menu['id'] ?>">
        <div><label for="nome-portata">Nuova portata</label><input type="text" id="nome-portata" name="nome" placeholder="es. Antipasti" required></div>
        <div>
            <label for="gruppo-portata">Menu impaginato</label>
            <select name="gruppo_impaginato" id="gruppo-portata">
                <option value="principale">Menu principale</option>
                <option value="dolci_drink">Dolci &amp; drink</option>
            </select>
        </div>
        <div>
            <label for="suffisso-portata">Suffisso export</label>
            <input type="text" id="suffisso-portata" name="suffisso_export" placeholder="es. **">
        </div>
        <div style="align-self:flex-end;"><button type="submit" class="btn-secondario">Aggiungi portata</button></div>
    </form>
</div>
<?php endif; ?>
