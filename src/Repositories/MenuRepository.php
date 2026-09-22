<?php

namespace App\Repositories;

use App\Db;

class MenuRepository
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $sql = "SELECT * FROM menus ORDER BY anno DESC,
            FIELD(stagione, 'inverno', 'autunno', 'estate', 'primavera')";
        return Db::conn()->query($sql)->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function archiviati(): array
    {
        $stmt = Db::conn()->prepare(
            "SELECT * FROM menus WHERE stato = 'archiviato' ORDER BY anno DESC,
             FIELD(stagione, 'inverno', 'autunno', 'estate', 'primavera')"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function attivi(): array
    {
        $stmt = Db::conn()->prepare(
            "SELECT * FROM menus WHERE stato != 'archiviato' ORDER BY anno DESC,
             FIELD(stagione, 'inverno', 'autunno', 'estate', 'primavera')"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM menus WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByStagioneAnno(string $stagione, int $anno): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM menus WHERE stagione = ? AND anno = ?');
        $stmt->execute([$stagione, $anno]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Il menu più recente esistente (qualsiasi stato), usato come base di default
     * per la duplicazione automatica della stagione successiva.
     */
    public function ultimoEsistente(): ?array
    {
        $stmt = Db::conn()->query(
            "SELECT * FROM menus ORDER BY anno DESC,
             FIELD(stagione, 'inverno', 'autunno', 'estate', 'primavera') LIMIT 1"
        );
        return $stmt->fetch() ?: null;
    }

    public function create(string $stagione, int $anno, string $stato = 'bozza', ?int $duplicatoDa = null): int
    {
        $stmt = Db::conn()->prepare(
            'INSERT INTO menus (stagione, anno, stato, duplicato_da_menu_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$stagione, $anno, $stato, $duplicatoDa]);
        return (int) Db::conn()->lastInsertId();
    }

    public function updateStato(int $id, string $stato): void
    {
        $pubblicataIl = $stato === 'pubblicato' ? ', pubblicato_il = COALESCE(pubblicato_il, NOW())' : '';
        $stmt = Db::conn()->prepare("UPDATE menus SET stato = ?{$pubblicataIl} WHERE id = ?");
        $stmt->execute([$stato, $id]);
    }
}
