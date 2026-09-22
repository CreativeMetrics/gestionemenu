<?php

namespace App\Controllers;

use App\Auth;
use App\Csrf;
use App\Repositories\AllergeneRepository;
use App\Repositories\ImpostazioniRepository;
use App\Support\View;

class SettingsController
{
    private ImpostazioniRepository $impostazioniRepo;
    private AllergeneRepository $allergeneRepo;

    public function __construct()
    {
        $this->impostazioniRepo = new ImpostazioniRepository();
        $this->allergeneRepo = new AllergeneRepository();
    }

    public function index(): void
    {
        Auth::requireAdmin();
        View::render('settings/index', [
            'impostazioni' => $this->impostazioniRepo->tutte(),
            'allergeni' => $this->allergeneRepo->all(),
        ]);
    }

    public function salvaStili(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();
        $campi = [
            'indesign_encoding', 'indesign_stile_portata', 'indesign_stile_piatto',
            'indesign_stile_descrizione', 'indesign_stile_prezzo', 'indesign_stile_carattere_allergeni',
            'indesign_font_allergeni', 'indesign_fontstyle_allergeni',
        ];
        $valori = [];
        foreach ($campi as $c) {
            $valori[$c] = trim((string) ($_POST[$c] ?? ''));
        }
        $this->impostazioniRepo->setMany($valori);
        flash('ok', 'Impostazioni di export salvate.');
        redirect('/impostazioni');
    }

    public function salvaGlifi(): void
    {
        Auth::requireAdmin();
        Csrf::verifyOrFail();
        foreach ($_POST['glifo'] ?? [] as $allergeneId => $glifo) {
            $this->allergeneRepo->updateGlifo((int) $allergeneId, trim((string) $glifo));
        }
        flash('ok', 'Mappa allergene → glifo aggiornata.');
        redirect('/impostazioni');
    }
}
