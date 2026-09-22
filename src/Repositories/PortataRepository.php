<?php

namespace App\Repositories;

use App\Db;

class PortataRepository
{
    /**
     * Portate di default per un menu creato da zero (non duplicato). Basato sulla struttura
     * osservata nell'IDML "Menu A4 - autunno 2026": Dolci non compare nel documento principale,
     * quindi va di default nel menu dolci&drink. "suffisso" è testo aggiunto solo nell'export
     * InDesign dopo il nome (qui: "**" per il rimando alla nota piatti senza glutine, "e contorni"
     * dopo Secondi), preso anch'esso dallo stesso documento.
     * @return array<int, array{nome:string, gruppo:string, suffisso:?string}>
     */
    public static function elencoDiDefault(): array
    {
        return [
            ['nome' => 'Antipasti', 'gruppo' => 'principale', 'suffisso' => '**'],
            ['nome' => 'Primi', 'gruppo' => 'principale', 'suffisso' => '**'],
            ['nome' => 'Secondi', 'gruppo' => 'principale', 'suffisso' => ' e contorni'],
            ['nome' => 'Per i più piccoli', 'gruppo' => 'principale', 'suffisso' => null],
            ['nome' => 'Drink & Gin', 'gruppo' => 'principale', 'suffisso' => null],
            ['nome' => 'Il Caffè', 'gruppo' => 'principale', 'suffisso' => null],
            ['nome' => 'Dolci', 'gruppo' => 'dolci_drink', 'suffisso' => null],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function forMenu(int $menuId): array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM portate WHERE menu_id = ? ORDER BY ordine, id');
        $stmt->execute([$menuId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM portate WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $menuId, string $nome, int $ordine, string $gruppo = 'principale', ?string $suffisso = null): int
    {
        $stmt = Db::conn()->prepare(
            'INSERT INTO portate (menu_id, nome, ordine, gruppo_impaginato, suffisso_export) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$menuId, $nome, $ordine, $gruppo, $suffisso !== '' ? $suffisso : null]);
        return (int) Db::conn()->lastInsertId();
    }

    public function update(int $id, string $nome, string $gruppo, ?string $suffisso = null): void
    {
        $stmt = Db::conn()->prepare('UPDATE portate SET nome = ?, gruppo_impaginato = ?, suffisso_export = ? WHERE id = ?');
        $stmt->execute([$nome, $gruppo, $suffisso !== '' ? $suffisso : null, $id]);
    }

    public function updateOrdine(int $id, int $ordine): void
    {
        $stmt = Db::conn()->prepare('UPDATE portate SET ordine = ? WHERE id = ?');
        $stmt->execute([$ordine, $id]);
    }

    public function delete(int $id): void
    {
        $stmt = Db::conn()->prepare('DELETE FROM portate WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function prossimoOrdine(int $menuId): int
    {
        $stmt = Db::conn()->prepare('SELECT COALESCE(MAX(ordine), 0) + 1 FROM portate WHERE menu_id = ?');
        $stmt->execute([$menuId]);
        return (int) $stmt->fetchColumn();
    }
}
