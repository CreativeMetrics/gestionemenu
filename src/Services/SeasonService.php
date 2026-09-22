<?php

namespace App\Services;

use App\Db;
use App\Repositories\MenuRepository;
use App\Repositories\PiattoRepository;
use App\Repositories\PortataRepository;
use DateInterval;
use DateTimeImmutable;

class SeasonService
{
    /** @var array<int, string> ordine ciclico delle stagioni */
    public const ORDINE = ['primavera', 'estate', 'autunno', 'inverno'];

    /** Mese/giorno di inizio di ciascuna stagione. */
    private const INIZIO = [
        'primavera' => [3, 20],
        'estate' => [6, 21],
        'autunno' => [9, 23],
        'inverno' => [12, 21],
    ];

    public function __construct(
        private MenuRepository $menuRepo = new MenuRepository(),
        private PortataRepository $portataRepo = new PortataRepository(),
        private PiattoRepository $piattoRepo = new PiattoRepository(),
    ) {
    }

    public function inizioStagione(string $stagione, int $anno): DateTimeImmutable
    {
        [$mese, $giorno] = self::INIZIO[$stagione];
        return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $anno, $mese, $giorno));
    }

    public function dataCreazioneAutomatica(string $stagione, int $anno): DateTimeImmutable
    {
        return $this->inizioStagione($stagione, $anno)->sub(new DateInterval('P30D'));
    }

    /** @return array{0:string,1:int} */
    public function prossima(string $stagione, int $anno): array
    {
        $idx = array_search($stagione, self::ORDINE, true);
        $prossimoIdx = ($idx + 1) % 4;
        $prossimoAnno = $prossimoIdx === 0 ? $anno + 1 : $anno;
        return [self::ORDINE[$prossimoIdx], $prossimoAnno];
    }

    /**
     * Duplica un menu esistente (portate, piatti, prezzi, allergeni, ordine, foto) in una nuova
     * stagione/anno. Restituisce l'id del nuovo menu. Se esiste già un menu per quella stagione/anno
     * non fa nulla e restituisce il suo id (idempotenza).
     */
    public function duplicaMenu(int $sourceMenuId, string $stagioneDestinazione, int $annoDestinazione): int
    {
        $esistente = $this->menuRepo->findByStagioneAnno($stagioneDestinazione, $annoDestinazione);
        if ($esistente !== null) {
            return (int) $esistente['id'];
        }

        $db = Db::conn();
        $db->beginTransaction();
        try {
            $nuovoMenuId = $this->menuRepo->create($stagioneDestinazione, $annoDestinazione, 'bozza', $sourceMenuId);

            foreach ($this->portataRepo->forMenu($sourceMenuId) as $portata) {
                $nuovaPortataId = $this->portataRepo->create(
                    $nuovoMenuId,
                    $portata['nome'],
                    (int) $portata['ordine'],
                    $portata['gruppo_impaginato']
                );

                foreach ($this->piattoRepo->forPortata((int) $portata['id']) as $piatto) {
                    $nuovoPiattoId = $this->piattoRepo->create([
                        'portata_id' => $nuovaPortataId,
                        'nome' => $piatto['nome'],
                        'descrizione' => $piatto['descrizione'],
                        'prezzo_testo' => $piatto['prezzo_testo'],
                        'prezzo_numero' => $piatto['prezzo_numero'],
                        'tracce_di' => $piatto['tracce_di'],
                        'note_interne' => $piatto['note_interne'],
                        'ordine' => $piatto['ordine'],
                    ]);
                    if (!empty($piatto['foto_path'])) {
                        $this->piattoRepo->setFoto($nuovoPiattoId, $piatto['foto_path']);
                    } elseif (!empty($piatto['foto_esterna'])) {
                        $this->piattoRepo->setFotoEsterna($nuovoPiattoId, true);
                    }
                    $allergeni = $this->piattoRepo->allergeniIds((int) $piatto['id']);
                    $this->piattoRepo->setAllergeni($nuovoPiattoId, $allergeni);
                }
            }

            $db->commit();
            return $nuovoMenuId;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Crea il menu della stagione successiva rispetto all'ultimo menu esistente, duplicandolo.
     * Usato sia dal pulsante "crea prossima stagione" sia dal cron. Idempotente.
     */
    public function creaProssimaStagione(): ?int
    {
        $ultimo = $this->menuRepo->ultimoEsistente();
        if ($ultimo === null) {
            return null;
        }
        [$stagione, $anno] = $this->prossima($ultimo['stagione'], (int) $ultimo['anno']);
        return $this->duplicaMenu((int) $ultimo['id'], $stagione, $anno);
    }

    /**
     * Esegue la creazione automatica se oggi è il giorno (o successivo) in cui va creato il menu
     * della prossima stagione (30gg prima del suo inizio) e quel menu non esiste ancora.
     * Pensato per essere chiamato una volta al giorno dal cron. Idempotente.
     */
    public function eseguiCreazioneSeDovuta(?DateTimeImmutable $oggi = null): ?int
    {
        $oggi ??= new DateTimeImmutable('today');
        $ultimo = $this->menuRepo->ultimoEsistente();
        if ($ultimo === null) {
            return null;
        }
        [$stagione, $anno] = $this->prossima($ultimo['stagione'], (int) $ultimo['anno']);

        if ($this->menuRepo->findByStagioneAnno($stagione, $anno) !== null) {
            return null; // già esiste, nulla da fare
        }

        $dataCreazione = $this->dataCreazioneAutomatica($stagione, $anno);
        if ($oggi < $dataCreazione) {
            return null; // non è ancora il momento
        }

        return $this->duplicaMenu((int) $ultimo['id'], $stagione, $anno);
    }
}
