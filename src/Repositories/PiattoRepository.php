<?php

namespace App\Repositories;

use App\Db;

class PiattoRepository
{
    /** @return array<int, array<string, mixed>> */
    public function forPortata(int $portataId): array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM piatti WHERE portata_id = ? ORDER BY ordine, id');
        $stmt->execute([$portataId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM piatti WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Piatto con il menu_id e nome portata, per verifiche di appartenenza. */
    public function findConMenu(int $id): ?array
    {
        $sql = 'SELECT p.*, po.menu_id, po.nome AS portata_nome
                FROM piatti p JOIN portate po ON po.id = p.portata_id
                WHERE p.id = ?';
        $stmt = Db::conn()->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** @param array<string, mixed> $dati */
    public function create(array $dati): int
    {
        $stmt = Db::conn()->prepare(
            'INSERT INTO piatti
                (portata_id, nome, descrizione, prezzo_testo, prezzo_numero, tracce_di, note_interne, ordine, creato_da, aggiornato_da)
             VALUES (:portata_id, :nome, :descrizione, :prezzo_testo, :prezzo_numero, :tracce_di, :note_interne, :ordine, :creato_da, :aggiornato_da)'
        );
        $stmt->execute([
            'portata_id' => $dati['portata_id'],
            'nome' => $dati['nome'],
            'descrizione' => $dati['descrizione'] ?? null,
            'prezzo_testo' => $dati['prezzo_testo'] ?? '',
            'prezzo_numero' => $dati['prezzo_numero'] ?? null,
            'tracce_di' => $dati['tracce_di'] ?? null,
            'note_interne' => $dati['note_interne'] ?? null,
            'ordine' => $dati['ordine'] ?? 0,
            'creato_da' => $dati['user_id'] ?? null,
            'aggiornato_da' => $dati['user_id'] ?? null,
        ]);
        return (int) Db::conn()->lastInsertId();
    }

    /** @param array<string, mixed> $dati */
    public function update(int $id, array $dati): void
    {
        $stmt = Db::conn()->prepare(
            'UPDATE piatti SET nome = :nome, descrizione = :descrizione, prezzo_testo = :prezzo_testo,
                prezzo_numero = :prezzo_numero, tracce_di = :tracce_di, note_interne = :note_interne,
                aggiornato_da = :user_id
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'nome' => $dati['nome'],
            'descrizione' => $dati['descrizione'] ?? null,
            'prezzo_testo' => $dati['prezzo_testo'] ?? '',
            'prezzo_numero' => $dati['prezzo_numero'] ?? null,
            'tracce_di' => $dati['tracce_di'] ?? null,
            'note_interne' => $dati['note_interne'] ?? null,
            'user_id' => $dati['user_id'] ?? null,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = Db::conn()->prepare('DELETE FROM piatti WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function spostaOrdina(int $id, int $portataId, int $ordine): void
    {
        $stmt = Db::conn()->prepare('UPDATE piatti SET portata_id = ?, ordine = ? WHERE id = ?');
        $stmt->execute([$portataId, $ordine, $id]);
    }

    public function setFoto(int $id, ?string $percorso): void
    {
        $stmt = Db::conn()->prepare('UPDATE piatti SET foto_path = ? WHERE id = ?');
        $stmt->execute([$percorso, $id]);
    }

    public function prossimoOrdine(int $portataId): int
    {
        $stmt = Db::conn()->prepare('SELECT COALESCE(MAX(ordine), 0) + 1 FROM piatti WHERE portata_id = ?');
        $stmt->execute([$portataId]);
        return (int) $stmt->fetchColumn();
    }

    // --- Allergeni ---

    /** @return array<int, int> id allergeni */
    public function allergeniIds(int $piattoId): array
    {
        $stmt = Db::conn()->prepare('SELECT allergene_id FROM piatto_allergeni WHERE piatto_id = ?');
        $stmt->execute([$piattoId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** @param array<int, int> $allergeneIds */
    public function setAllergeni(int $piattoId, array $allergeneIds): void
    {
        $db = Db::conn();
        $db->prepare('DELETE FROM piatto_allergeni WHERE piatto_id = ?')->execute([$piattoId]);
        if ($allergeneIds === []) {
            return;
        }
        $stmt = $db->prepare('INSERT INTO piatto_allergeni (piatto_id, allergene_id) VALUES (?, ?)');
        foreach (array_unique($allergeneIds) as $aId) {
            $stmt->execute([$piattoId, $aId]);
        }
    }

    /** @return array<int, array<string, mixed>> allergeni completi (join) per piatto */
    public function allergeniPerPiatto(int $piattoId): array
    {
        $sql = 'SELECT a.* FROM allergeni a
                JOIN piatto_allergeni pa ON pa.allergene_id = a.id
                WHERE pa.piatto_id = ? ORDER BY a.ordine';
        $stmt = Db::conn()->prepare($sql);
        $stmt->execute([$piattoId]);
        return $stmt->fetchAll();
    }

    /** Foto mancanti per un menu (tutti i piatti senza foto_path). @return array<int, array<string, mixed>> */
    public function senzaFotoPerMenu(int $menuId): array
    {
        $sql = 'SELECT p.*, po.nome AS portata_nome FROM piatti p
                JOIN portate po ON po.id = p.portata_id
                WHERE po.menu_id = ? AND (p.foto_path IS NULL OR p.foto_path = "")
                ORDER BY po.ordine, p.ordine';
        $stmt = Db::conn()->prepare($sql);
        $stmt->execute([$menuId]);
        return $stmt->fetchAll();
    }
}
