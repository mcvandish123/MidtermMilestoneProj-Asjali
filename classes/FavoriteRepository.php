<?php
declare(strict_types=1);

/** All SQL for the `favorites` pivot table. */
final class FavoriteRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * INSERT IGNORE + the composite primary key make saving twice harmless.
     * `name` stores the recipe's title at the time it was saved, for readability in phpMyAdmin.
     */
    public function add(int $userId, int $recipeId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO favorites (user_id, recipe_id, name)
             SELECT ?, id, title FROM recipes WHERE id = ?'
        );
        $stmt->execute([$userId, $recipeId]);
    }

    public function remove(int $userId, int $recipeId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM favorites WHERE user_id = ? AND recipe_id = ?');
        $stmt->execute([$userId, $recipeId]);
    }

    public function isFavorite(int $userId, int $recipeId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND recipe_id = ?');
        $stmt->execute([$userId, $recipeId]);
        return (bool) $stmt->fetchColumn();
    }

    public function countFor(int $recipeId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM favorites WHERE recipe_id = ?');
        $stmt->execute([$recipeId]);
        return (int) $stmt->fetchColumn();
    }
}
