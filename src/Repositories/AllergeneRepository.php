<?php

namespace App\Repositories;

use App\Db;

class AllergeneRepository
{
    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return Db::conn()->query('SELECT * FROM allergeni ORDER BY ordine, id')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM allergeni WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function updateGlifo(int $id, ?string $glifo): void
    {
        $stmt = Db::conn()->prepare('UPDATE allergeni SET glifo_unicode = ? WHERE id = ?');
        $stmt->execute([$glifo !== '' ? $glifo : null, $id]);
    }

    /** @return array<int, array<string, mixed>> indicizzato per id */
    public function tuttiIndicizzati(): array
    {
        $out = [];
        foreach ($this->all() as $a) {
            $out[(int) $a['id']] = $a;
        }
        return $out;
    }

    public function trovaIdPerNome(string $nome): ?int
    {
        $stmt = Db::conn()->prepare('SELECT id FROM allergeni WHERE LOWER(nome) = LOWER(?)');
        $stmt->execute([trim($nome)]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }
}
