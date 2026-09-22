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
}
