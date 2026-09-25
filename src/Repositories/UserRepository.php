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

    public function count(): int
    {
        return (int) Db::conn()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function create(string $nome, string $email, string $password, string $ruolo): int
    {
        // I nuovi admin partono con le notifiche email già attive (possono disattivarle da
        // Impostazioni → Utenti); per gli editor il flag non ha effetto, non le ricevono comunque.
        $stmt = Db::conn()->prepare(
            'INSERT INTO users (nome, email, password_hash, ruolo, notifiche_email) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nome, $email, password_hash($password, PASSWORD_DEFAULT), $ruolo, $ruolo === 'admin' ? 1 : 0]);
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

    public function setNotificheEmail(int $id, bool $attivo): void
    {
        $stmt = Db::conn()->prepare('UPDATE users SET notifiche_email = ? WHERE id = ?');
        $stmt->execute([$attivo ? 1 : 0, $id]);
    }

    /** Admin attivi che hanno scelto di ricevere il digest email delle modifiche. @return array<int, array<string, mixed>> */
    public function adminsDaNotificare(): array
    {
        return Db::conn()
            ->query("SELECT * FROM users WHERE ruolo = 'admin' AND attivo = 1 AND notifiche_email = 1")
            ->fetchAll();
    }
}
