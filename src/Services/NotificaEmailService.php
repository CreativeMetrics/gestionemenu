<?php

namespace App\Services;

use App\Repositories\StoricoRepository;
use App\Repositories\UserRepository;

/**
 * Digest email agli admin sulle modifiche fatte dagli editor: pensato per essere chiamato dal
 * cron (cron/notifica_modifiche.php), non da una richiesta web. Raggruppa le righe di storico
 * non ancora notificate per editor e per piatto, per non mandare una email per ogni singolo
 * campo cambiato.
 */
class NotificaEmailService
{
    public function __construct(
        private StoricoRepository $storicoRepo = new StoricoRepository(),
        private UserRepository $userRepo = new UserRepository(),
    ) {
    }

    /**
     * Costruisce ed eventualmente invia il digest. Restituisce il numero di righe di storico
     * processate (0 se non c'era nulla da notificare). Le righe vengono sempre segnate come
     * notificate, anche se nessun admin è iscritto: altrimenti, appena un admin attivasse le
     * notifiche, si ritroverebbe un digest con settimane di modifiche arretrate.
     */
    public function inviaSeCiSonoModifiche(): int
    {
        $righe = $this->storicoRepo->nonNotificatePerEditor();
        if ($righe === []) {
            return 0;
        }

        $destinatari = $this->userRepo->adminsDaNotificare();
        if ($destinatari !== []) {
            $corpo = $this->costruisciCorpo($righe);
            $oggetto = $this->costruisciOggetto($righe);
            foreach ($destinatari as $admin) {
                $this->spedisci($admin['email'], $oggetto, $corpo);
            }
        }

        $this->storicoRepo->segnaNotificate(array_map(fn (array $r) => (int) $r['id'], $righe));
        return count($righe);
    }

    private function spedisci(string $email, string $oggetto, string $corpo): void
    {
        $headers = "Content-Type: text/plain; charset=utf-8\r\n"
            . 'From: ' . config_get('app', [])['nome_locale'] . ' <noreply@' . $this->dominioDaBaseUrl() . ">\r\n";
        mail($email, $oggetto, $corpo, $headers);
    }

    private function dominioDaBaseUrl(): string
    {
        $host = parse_url(rtrim(config_get('app', [])['base_url'] ?? '', '/'), PHP_URL_HOST);
        return $host ?: 'localhost';
    }

    /** @param array<int, array<string, mixed>> $righe */
    private function costruisciOggetto(array $righe): string
    {
        $editor = array_unique(array_column($righe, 'user_nome'));
        $piatti = array_unique(array_column($righe, 'nome_piatto_snapshot'));
        $nomeLocale = config_get('app', [])['nome_locale'] ?? 'gestionemenu';

        if (count($editor) === 1) {
            return sprintf('[%s] %s ha modificato %d piatt%s', $nomeLocale, $editor[0], count($piatti), count($piatti) === 1 ? 'o' : 'i');
        }
        return sprintf('[%s] Modifiche menu da %d utenti (%d piatti)', $nomeLocale, count($editor), count($piatti));
    }

    /** @param array<int, array<string, mixed>> $righe */
    private function costruisciCorpo(array $righe): string
    {
        // Raggruppa per editor, poi per piatto (piatto_id se ancora esistente, altrimenti nome
        // snapshot: dopo un'eliminazione piatto_id è NULL).
        $perEditor = [];
        foreach ($righe as $riga) {
            $perEditor[$riga['user_nome']][$this->chiavePiatto($riga)][] = $riga;
        }

        $righeCorpo = [];
        foreach ($perEditor as $nomeEditor => $perPiatto) {
            $righeCorpo[] = "== {$nomeEditor} ==";
            foreach ($perPiatto as $modifiche) {
                $righeCorpo[] = $this->descriviPiatto($modifiche);
            }
            $righeCorpo[] = '';
        }

        $righeCorpo[] = '--';
        $righeCorpo[] = 'Email automatica di gestionemenu, generata da un controllo periodico.';

        return implode("\n", $righeCorpo);
    }

    /** @param array<string, mixed> $riga */
    private function chiavePiatto(array $riga): string
    {
        return $riga['piatto_id'] !== null ? 'id:' . $riga['piatto_id'] : 'nome:' . $riga['nome_piatto_snapshot'];
    }

    /** @param array<int, array<string, mixed>> $modifiche righe di storico per lo stesso piatto */
    private function descriviPiatto(array $modifiche): string
    {
        $primo = $modifiche[0];
        $nome = $primo['nome_piatto_snapshot'];
        $link = $primo['piatto_id'] !== null ? ' — ' . base_url('/piatti/' . $primo['piatto_id']) : ' (eliminato, non più raggiungibile)';

        $dettagli = [];
        foreach ($modifiche as $riga) {
            if ($riga['campo'] === 'creazione') {
                $dettagli[] = '  - creato';
                continue;
            }
            if ($riga['campo'] === 'eliminazione') {
                $dettagli[] = '  - eliminato';
                continue;
            }
            $vecchio = $this->tronca((string) ($riga['valore_precedente'] ?? ''));
            $nuovo = $this->tronca((string) ($riga['valore_nuovo'] ?? ''));
            $dettagli[] = sprintf('  - %s: "%s" -> "%s"', campo_label($riga['campo']), $vecchio, $nuovo);
        }

        return "* {$nome}{$link}\n" . implode("\n", $dettagli);
    }

    private function tronca(string $testo, int $lunghezza = 80): string
    {
        $testo = trim($testo);
        return mb_strlen($testo) > $lunghezza ? mb_substr($testo, 0, $lunghezza) . '…' : $testo;
    }
}
