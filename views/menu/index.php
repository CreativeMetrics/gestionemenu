<?php
use App\Csrf;
?>
<h1>Menu</h1>

<?php if (empty($menus)): ?>
    <p>Nessun menu ancora. Crea il primo menu qui sotto oppure <a href="/import">importa un CSV</a>.</p>
<?php else: ?>

<div class="dash-mobile">
    <a href="/menu/<?= (int) $menuCorrente['id'] ?>" class="dash-hero">
        <div class="dash-hero-label">Menu corrente</div>
        <div class="dash-hero-riga">
            <span class="dash-hero-titolo"><?= e(stagione_label($menuCorrente['stagione'])) ?> <?= (int) $menuCorrente['anno'] ?></span>
            <span class="<?= stato_badge_class($menuCorrente['stato']) ?>"><?= e(stato_label($menuCorrente['stato'])) ?></span>
        </div>
        <div class="dash-hero-meta">
            <?= $piattiCorrente ?> piatt<?= $piattiCorrente === 1 ? 'o' : 'i' ?><?php if ($senzaFotoCorrente > 0): ?> · <?= $senzaFotoCorrente ?> senza foto<?php endif; ?>
        </div>
        <span class="dash-hero-bottone">Apri menu</span>
    </a>

    <div class="dash-azioni-rapide">
        <a class="dash-azione" href="#crea-nuovo-menu">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Nuovo menu</span>
        </a>
        <a class="dash-azione" href="/import">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M4 19h16"/></svg>
            <span>Importa CSV</span>
        </a>
        <a class="dash-azione" href="/foto/mancanti">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="6" width="18" height="14" rx="2"/><circle cx="9" cy="12" r="2.2"/></svg>
            <span>Foto mancanti</span>
        </a>
    </div>

    <?php if (!empty($altriMenu)): ?>
        <div class="dash-sottotitolo">Altri menu</div>
        <?php foreach ($altriMenu as $m): ?>
            <a href="/menu/<?= (int) $m['id'] ?>" class="dash-riga-compatta">
                <span><?= e(stagione_label($m['stagione'])) ?> <?= (int) $m['anno'] ?></span>
                <span class="<?= stato_badge_class($m['stato']) ?>"><?= e(stato_label($m['stato'])) ?></span>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>

    <p><a href="/archivio">Vai all'archivio dei menu passati →</a></p>
</div>

<div class="dash-desktop">
    <div class="dash-desktop-principale">
        <div class="dash-desktop-titolo-riga">
            <h2 style="margin:0; font-size:1.2rem;">I tuoi menu</h2>
            <a href="#crea-nuovo-menu" class="btn">+ Nuovo menu</a>
        </div>
        <div class="dash-desktop-griglia">
            <a href="/menu/<?= (int) $menuCorrente['id'] ?>" class="dash-desktop-card dash-desktop-card-corrente">
                <div class="dash-hero-label">Corrente</div>
                <div class="dash-desktop-card-titolo"><?= e(stagione_label($menuCorrente['stagione'])) ?> <?= (int) $menuCorrente['anno'] ?></div>
                <span class="<?= stato_badge_class($menuCorrente['stato']) ?>"><?= e(stato_label($menuCorrente['stato'])) ?></span>
            </a>
            <?php foreach ($altriMenu as $m): ?>
                <a href="/menu/<?= (int) $m['id'] ?>" class="dash-desktop-card">
                    <div class="dash-desktop-card-titolo"><?= e(stagione_label($m['stagione'])) ?> <?= (int) $m['anno'] ?></div>
                    <span class="<?= stato_badge_class($m['stato']) ?>"><?= e(stato_label($m['stato'])) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <p style="margin-top:1.2rem;"><a href="/archivio">Vai all'archivio dei menu passati →</a></p>
    </div>

    <aside class="dash-desktop-pannello">
        <div class="dash-desktop-pannello-titolo">Statistiche</div>
        <div class="dash-stat">
            <div class="dash-stat-numero"><?= $piattiCorrente ?></div>
            <div class="dash-stat-testo">piatti in <?= e(stagione_label($menuCorrente['stagione'])) ?> <?= (int) $menuCorrente['anno'] ?></div>
        </div>
        <?php if ($senzaFotoCorrente > 0): ?>
        <div class="dash-stat">
            <div class="dash-stat-numero" style="color:var(--colore-warn);"><?= $senzaFotoCorrente ?></div>
            <div class="dash-stat-testo">piatti senza foto</div>
        </div>
        <?php endif; ?>
        <?php if ($giorniProssimaStagione !== null): ?>
        <div class="dash-stat">
            <div class="dash-stat-numero" style="color:var(--colore-ok);"><?= $giorniProssimaStagione ?></div>
            <div class="dash-stat-testo">giorni a <?= e($prossimaStagioneLabel) ?></div>
        </div>
        <?php endif; ?>
        <div class="dash-pannello-azioni">
            <a href="/foto/mancanti">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="6" width="18" height="14" rx="2"/><circle cx="9" cy="12" r="2.2"/></svg>
                Foto mancanti
            </a>
            <a href="/import">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M4 19h16"/></svg>
                Importa CSV
            </a>
        </div>
    </aside>
</div>

<?php endif; ?>

<div class="card" id="crea-nuovo-menu">
    <h2 style="margin-top:0; font-size:1.05rem;">Crea nuovo menu</h2>
    <form method="post" action="/menu/crea-prossima" style="margin-bottom: 0.8rem;">
        <?= Csrf::field() ?>
        <button type="submit">Crea menu della prossima stagione (duplica l'ultimo)</button>
    </form>
    <details>
        <summary style="cursor:pointer; font-size:0.9rem; color:#5b5346;">oppure crea un menu vuoto per una stagione specifica</summary>
        <form method="post" action="/menu/nuovo-vuoto" class="form-riga" style="margin-top:0.6rem;">
            <?= Csrf::field() ?>
            <div>
                <label for="stagione">Stagione</label>
                <select name="stagione" id="stagione">
                    <option value="primavera">Primavera</option>
                    <option value="estate">Estate</option>
                    <option value="autunno">Autunno</option>
                    <option value="inverno">Inverno</option>
                </select>
            </div>
            <div>
                <label for="anno">Anno</label>
                <input type="number" name="anno" id="anno" value="<?= (int) date('Y') ?>">
            </div>
            <div style="align-self: flex-end;">
                <button type="submit" class="btn-secondario">Crea vuoto</button>
            </div>
        </form>
    </details>
    <p class="help-text" style="margin-top:0.8rem;">
        Prima volta? Se hai un export dal vecchio Google Sheet, conviene
        <a href="/import">importare il CSV</a> invece di creare un menu vuoto: porta subito con sé
        portate, piatti, prezzi e allergeni.
    </p>
</div>
