<h1>Export InDesign — <?= e(stagione_label($menu['stagione'])) ?> <?= (int) $menu['anno'] ?></h1>
<p class="help-text">
    File InDesign Tagged Text (.txt), codifica Unicode. Prima di importarli assicurati di avere nel documento
    InDesign gli stili di paragrafo/carattere con i nomi configurati in <a href="/impostazioni">Impostazioni</a>.
</p>

<div class="card">
    <h2 style="margin-top:0; font-size:1.05rem;">Menu principale</h2>
    <a class="btn" href="/menu/<?= (int) $menu['id'] ?>/export/indesign/principale">Scarica .txt</a>
</div>

<div class="card">
    <h2 style="margin-top:0; font-size:1.05rem;">Dolci &amp; drink</h2>
    <a class="btn" href="/menu/<?= (int) $menu['id'] ?>/export/indesign/dolci_drink">Scarica .txt</a>
</div>

<p><a href="/menu/<?= (int) $menu['id'] ?>">← Torna al menu</a></p>
