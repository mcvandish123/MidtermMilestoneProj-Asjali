<?php
declare(strict_types=1);

/** All SQL for the `users` table. */
final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(string $username, string $email, string $passwordHash, string $displayName): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash, display_name)
             VALUES (:username, :email, :password_hash, :display_name)'
        );
        $stmt->execute([
            ':username'      => $username,
            ':email'         => $email,
            ':password_hash' => $passwordHash,
            ':display_name'  => $displayName,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, email, display_name, created_at FROM users WHERE id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Find a member by username OR email (used by the login form). Includes the hash. */
    public function findByLogin(string $login): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, email, password_hash, display_name
               FROM users
              WHERE username = ? OR email = ?
              LIMIT 1'
        );
        $stmt->execute([$login, $login]);
        return $stmt->fetch() ?: null;
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE username = ?');
        $stmt->execute([$username]);
        return (bool) $stmt->fetchColumn();
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return (bool) $stmt->fetchColumn();
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $id]);
    }
}
