<?php

namespace App\Repositories;

use App\Db;

class PortataRepository
{
    /**
     * Portate di default per un menu creato da zero (non duplicato). Basato sulla struttura
     * osservata nell'IDML "Menu A4 - autunno 2026": Dolci non compare nel documento principale,
     * quindi va di default nel menu dolci&drink.
     * @return array<int, array{nome:string, gruppo:string}>
     */
    public static function elencoDiDefault(): array
    {
        return [
            ['nome' => 'Antipasti', 'gruppo' => 'principale'],
            ['nome' => 'Primi', 'gruppo' => 'principale'],
            ['nome' => 'Secondi', 'gruppo' => 'principale'],
            ['nome' => 'Per i più piccoli', 'gruppo' => 'principale'],
            ['nome' => 'Drink & Gin', 'gruppo' => 'principale'],
            ['nome' => 'Il Caffè', 'gruppo' => 'principale'],
            ['nome' => 'Dolci', 'gruppo' => 'dolci_drink'],
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

    public function create(int $menuId, string $nome, int $ordine, string $gruppo = 'principale'): int
    {
        $stmt = Db::conn()->prepare(
            'INSERT INTO portate (menu_id, nome, ordine, gruppo_impaginato) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$menuId, $nome, $ordine, $gruppo]);
        return (int) Db::conn()->lastInsertId();
    }

    public function update(int $id, string $nome, string $gruppo): void
    {
        $stmt = Db::conn()->prepare('UPDATE portate SET nome = ?, gruppo_impaginato = ? WHERE id = ?');
        $stmt->execute([$nome, $gruppo, $id]);
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
