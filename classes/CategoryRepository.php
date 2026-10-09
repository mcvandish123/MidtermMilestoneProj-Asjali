<?php
declare(strict_types=1);

/** All SQL for the `categories` table. */
final class CategoryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** Every category with how many recipes it has. */
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT c.id, c.name, c.description, COUNT(r.id) AS recipe_count
               FROM categories c
               LEFT JOIN recipes r ON r.category_id = c.id
              GROUP BY c.id, c.name, c.description
              ORDER BY c.id'
        )->fetchAll();
    }

    /** @return list<int> */
    public function ids(): array
    {
        return array_map('intval', $this->pdo->query('SELECT id FROM categories')->fetchAll(PDO::FETCH_COLUMN));
    }
}
