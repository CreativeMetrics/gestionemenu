<?php

namespace App\Repositories;

use App\Db;

class StoricoRepository
{
    public function log(int $piattoId, string $nomeSnapshot, ?int $userId, string $campo, ?string $vecchio, ?string $nuovo): void
    {
        if ($vecchio === $nuovo) {
            return;
        }
        $stmt = Db::conn()->prepare(
            'INSERT INTO piatto_storico (piatto_id, nome_piatto_snapshot, user_id, campo, valore_precedente, valore_nuovo)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$piattoId, $nomeSnapshot, $userId, $campo, $vecchio, $nuovo]);
    }

    /** @return array<int, array<string, mixed>> */
    public function forPiatto(int $piattoId): array
    {
        $sql = 'SELECT s.*, u.nome AS user_nome FROM piatto_storico s
                LEFT JOIN users u ON u.id = s.user_id
                WHERE s.piatto_id = ? ORDER BY s.creato_il DESC, s.id DESC';
        $stmt = Db::conn()->prepare($sql);
        $stmt->execute([$piattoId]);
        return $stmt->fetchAll();
    }

    /**
     * Registra l'eliminazione di un piatto con "dove si trovava" (menu/gruppo impaginato)
     * salvato a parte: piatto_id resta valorizzato finché il DELETE dei piatti non lo azzera
     * (ON DELETE SET NULL), ma da quel momento in poi è menu_id_snapshot/gruppo_impaginato_snapshot
     * l'unico modo per sapere a quale export apparteneva questa riga.
     */
    public function logEliminazione(int $piattoId, string $nomeSnapshot, ?int $userId, int $menuId, string $gruppoImpaginato): void
    {
        $stmt = Db::conn()->prepare(
            'INSERT INTO piatto_storico (piatto_id, nome_piatto_snapshot, user_id, campo, valore_precedente, valore_nuovo, menu_id_snapshot, gruppo_impaginato_snapshot)
             VALUES (?, ?, ?, ?, ?, NULL, ?, ?)'
        );
        $stmt->execute([$piattoId, $nomeSnapshot, $userId, 'eliminazione', $nomeSnapshot, $menuId, $gruppoImpaginato]);
    }

    /**
     * Piatti eliminati dopo $dal che appartenevano a quella parte di quel menu, per l'elenco
     * "modifiche dall'ultimo export".
     * @return array<int, array<string, mixed>>
     */
    public function eliminatiPerMenuGruppo(int $menuId, string $gruppoImpaginato, string $dal): array
    {
        $sql = "SELECT * FROM piatto_storico
                WHERE campo = 'eliminazione' AND menu_id_snapshot = ? AND gruppo_impaginato_snapshot = ? AND creato_il > ?
                ORDER BY creato_il DESC";
        $stmt = Db::conn()->prepare($sql);
        $stmt->execute([$menuId, $gruppoImpaginato, $dal]);
        return $stmt->fetchAll();
    }

    /**
     * Modifiche di campo (non l'eliminazione) registrate per un piatto dopo $dal, per l'elenco
     * "modifiche dall'ultimo export".
     * @return array<int, array<string, mixed>>
     */
    public function modificheDalPerPiatto(int $piattoId, string $dal): array
    {
        $sql = "SELECT * FROM piatto_storico
                WHERE piatto_id = ? AND campo != 'eliminazione' AND campo != 'creazione' AND creato_il > ?
                ORDER BY creato_il ASC";
        $stmt = Db::conn()->prepare($sql);
        $stmt->execute([$piattoId, $dal]);
        return $stmt->fetchAll();
    }

    /**
     * Modifiche fatte da editor (non admin) non ancora incluse in un digest email, per
     * cron/notifica_modifiche.php. @return array<int, array<string, mixed>>
     */
    public function nonNotificatePerEditor(): array
    {
        $sql = "SELECT s.*, u.nome AS user_nome
                FROM piatto_storico s
                JOIN users u ON u.id = s.user_id
                WHERE s.notificato_il IS NULL AND u.ruolo = 'editor'
                ORDER BY s.user_id, s.creato_il ASC";
        return Db::conn()->query($sql)->fetchAll();
    }

    /** @param array<int, int> $ids */
    public function segnaNotificate(array $ids): void
    {
        if ($ids === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Db::conn()->prepare("UPDATE piatto_storico SET notificato_il = NOW() WHERE id IN ($placeholders)");
        $stmt->execute($ids);
    }
}
