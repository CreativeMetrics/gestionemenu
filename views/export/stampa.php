<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Anteprima stampa — <?= e(stagione_label($menu['stagione'])) ?> <?= (int) $menu['anno'] ?></title>
<style>
    body { font-family: Georgia, serif; max-width: 700px; margin: 2rem auto; padding: 0 1rem; color: #222; }
    h1 { text-align: center; }
    h2 { border-bottom: 1px solid #ccc; padding-bottom: 0.3rem; margin-top: 2rem; }
    .piatto { margin-bottom: 1rem; }
    .piatto .riga-nome { display: flex; justify-content: space-between; gap: 1rem; font-weight: bold; }
    .piatto .desc { font-style: italic; font-size: 0.9rem; white-space: pre-line; }
    .allergeni { font-size: 0.8rem; color: #666; }
    .no-print { text-align: center; margin-bottom: 1.5rem; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
<div class="no-print"><button onclick="window.print()">Stampa</button></div>
<h1><?= e(stagione_label($menu['stagione'])) ?> <?= (int) $menu['anno'] ?></h1>

<?php foreach ($portate as $portata): ?>
    <?php if (empty($piattiPerPortata[$portata['id']])) { continue; } ?>
    <h2><?= e($portata['nome']) ?></h2>
    <?php foreach ($piattiPerPortata[$portata['id']] as $piatto): ?>
        <div class="piatto">
            <div class="riga-nome"><span><?= e($piatto['nome']) ?></span><span><?= e($piatto['prezzo_testo']) ?></span></div>
            <?php if (!empty($piatto['descrizione'])): ?><div class="desc"><?= e($piatto['descrizione']) ?></div><?php endif; ?>
            <?php if ($piatto['allergeni']): ?>
                <div class="allergeni">Allergeni: <?= e(implode(', ', array_map(fn ($a) => $a['nome'], $piatto['allergeni']))) ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endforeach; ?>
</body>
</html>
