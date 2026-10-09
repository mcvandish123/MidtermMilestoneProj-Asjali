<?php
declare(strict_types=1);

/** All SQL for the `comments` table. Updates and deletes always check ownership. */
final class CommentRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Oldest first, so the conversation reads top to bottom. */
    public function forRecipe(int $recipeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cm.id, cm.recipe_id, cm.user_id, cm.body, cm.created_at, cm.updated_at,
                    u.display_name AS author_name, u.username AS author_username
               FROM comments cm
               JOIN users u ON u.id = cm.user_id
              WHERE cm.recipe_id = ?
              ORDER BY cm.created_at ASC, cm.id ASC'
        );
        $stmt->execute([$recipeId]);
        return $stmt->fetchAll();
    }

    public function create(int $recipeId, int $userId, string $body): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO comments (recipe_id, user_id, body) VALUES (?, ?, ?)');
        $stmt->execute([$recipeId, $userId, $body]);
        return (int) $this->pdo->lastInsertId();
    }

    /** The comment only if $userId wrote it. */
    public function findOwned(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM comments WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }

    public function update(int $id, int $userId, string $body): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE comments SET body = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$body, $id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM comments WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }
}
