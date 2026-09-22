<?php

namespace App\Repositories;

use App\Db;
use PDO;

class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM users WHERE email = ? AND attivo = 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmailQualunqueStato(string $email): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = Db::conn()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return Db::conn()->query('SELECT * FROM users ORDER BY nome')->fetchAll();
    }

    public function create(string $nome, string $email, string $password, string $ruolo): int
    {
        $stmt = Db::conn()->prepare(
            'INSERT INTO users (nome, email, password_hash, ruolo) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$nome, $email, password_hash($password, PASSWORD_DEFAULT), $ruolo]);
        return (int) Db::conn()->lastInsertId();
    }

    public function updateRuolo(int $id, string $ruolo): void
    {
        $stmt = Db::conn()->prepare('UPDATE users SET ruolo = ? WHERE id = ?');
        $stmt->execute([$ruolo, $id]);
    }

    public function setAttivo(int $id, bool $attivo): void
    {
        $stmt = Db::conn()->prepare('UPDATE users SET attivo = ? WHERE id = ?');
        $stmt->execute([$attivo ? 1 : 0, $id]);
    }

    public function updatePassword(int $id, string $password): void
    {
        $stmt = Db::conn()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public function emailEsiste(string $email): bool
    {
        $stmt = Db::conn()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return (bool) $stmt->fetchColumn();
    }
}
