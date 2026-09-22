<?php

namespace App\Repositories;

use App\Db;

class ImpostazioniRepository
{
    /** @return array<string, string> */
    public function tutte(): array
    {
        $out = [];
        $stmt = Db::conn()->query('SELECT chiave, valore FROM impostazioni');
        foreach ($stmt->fetchAll() as $row) {
            $out[$row['chiave']] = $row['valore'];
        }
        return $out;
    }

    public function get(string $chiave, string $default = ''): string
    {
        $stmt = Db::conn()->prepare('SELECT valore FROM impostazioni WHERE chiave = ?');
        $stmt->execute([$chiave]);
        $valore = $stmt->fetchColumn();
        return $valore !== false ? $valore : $default;
    }

    public function set(string $chiave, string $valore): void
    {
        $stmt = Db::conn()->prepare(
            'INSERT INTO impostazioni (chiave, valore) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valore = VALUES(valore)'
        );
        $stmt->execute([$chiave, $valore]);
    }

    /** @param array<string, string> $valori */
    public function setMany(array $valori): void
    {
        foreach ($valori as $chiave => $valore) {
            $this->set($chiave, $valore);
        }
    }
}
