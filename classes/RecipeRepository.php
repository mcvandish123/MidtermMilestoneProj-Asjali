<?php
declare(strict_types=1);

/**
 * All SQL for `recipes` and their `ingredients`.
 * Every write that changes a recipe AND its ingredients runs inside a transaction.
 */
final class RecipeRepository
{
    /** Columns every recipe card needs. The ? is the viewer's id (for is_favorite). */
    private const CARD_COLUMNS = '
        r.id, r.user_id, r.category_id, r.title, r.description,
        r.servings, r.cook_time_minutes, r.created_at, r.updated_at,
        c.name AS category_name,
        u.display_name AS author_name, u.username AS author_username,
        (SELECT COUNT(*) FROM comments cm  WHERE cm.recipe_id = r.id) AS comment_count,
        (SELECT COUNT(*) FROM favorites fc WHERE fc.recipe_id = r.id) AS favorite_count,
        EXISTS (SELECT 1 FROM favorites fv WHERE fv.recipe_id = r.id AND fv.user_id = ?) AS is_favorite';

    private const FROM_JOINS = '
        FROM recipes r
        JOIN users u      ON u.id = r.user_id
        JOIN categories c ON c.id = r.category_id';

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Newest-first list with optional keyword, category, author and favorites filters.
     *
     * @param array{q?:string, category?:?int, author?:?int, favorites_of?:?int} $filters
     * @return array{items:list<array>, total:int, pages:int, page:int}
     */
    public function search(int $viewerId, array $filters, int $page = 1, int $perPage = 9): array
    {
        $where = [];
        $params = [];

        $q = trim($filters['q'] ?? '');
        if ($q !== '') {
            // Escape LIKE wildcards so "%" or "_" typed by a member are matched literally.
            $like = '%' . addcslashes($q, '\\%_') . '%';
            $where[] = '(r.title LIKE ? OR r.description LIKE ?
                         OR EXISTS (SELECT 1 FROM ingredients i WHERE i.recipe_id = r.id AND i.name LIKE ?))';
            array_push($params, $like, $like, $like);
        }
        if (!empty($filters['category'])) {
            $where[] = 'r.category_id = ?';
            $params[] = (int) $filters['category'];
        }
        if (!empty($filters['author'])) {
            $where[] = 'r.user_id = ?';
            $params[] = (int) $filters['author'];
        }
        if (!empty($filters['favorites_of'])) {
            $where[] = 'EXISTS (SELECT 1 FROM favorites fo WHERE fo.recipe_id = r.id AND fo.user_id = ?)';
            $params[] = (int) $filters['favorites_of'];
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $count = $this->pdo->prepare('SELECT COUNT(*)' . self::FROM_JOINS . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare(
            'SELECT ' . self::CARD_COLUMNS . self::FROM_JOINS . $whereSql .
            ' ORDER BY r.created_at DESC, r.id DESC LIMIT ? OFFSET ?'
        );

        $position = 1;
        $stmt->bindValue($position++, $viewerId, PDO::PARAM_INT);
        foreach ($params as $value) {
            $stmt->bindValue($position++, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue($position++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($position, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'pages' => $pages,
            'page'  => $page,
        ];
    }

    /** One recipe with everything the detail page needs. */
    public function find(int $id, int $viewerId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::CARD_COLUMNS . ', r.steps' . self::FROM_JOINS . ' WHERE r.id = ?'
        );
        $stmt->execute([$viewerId, $id]);
        $recipe = $stmt->fetch();

        if (!$recipe) {
            return null;
        }

        $recipe['ingredients'] = $this->ingredients($id);
        return $recipe;
    }

    /** The recipe only if it belongs to $userId (used by the edit page). */
    public function findOwned(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM recipes WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $recipe = $stmt->fetch();

        if (!$recipe) {
            return null;
        }

        $recipe['ingredients'] = $this->ingredients($id);
        return $recipe;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM recipes WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }

    /** @return list<array{id:int, position:int, quantity:?string, name:string}> */
    public function ingredients(int $recipeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, position, quantity, name FROM ingredients WHERE recipe_id = ? ORDER BY position'
        );
        $stmt->execute([$recipeId]);
        return $stmt->fetchAll();
    }

    /**
     * Insert a recipe and its ingredients together (all or nothing).
     *
     * @param array{category_id:int, title:string, description:string, steps:string,
     *              servings:?int, cook_time_minutes:?int} $data
     * @param list<array{quantity:string, name:string}> $ingredients
     */
    public function create(int $userId, array $data, array $ingredients): int
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO recipes (user_id, category_id, title, description, steps, servings, cook_time_minutes)
                 VALUES (:user_id, :category_id, :title, :description, :steps, :servings, :cook_time)'
            );
            $this->bindRecipe($stmt, $data);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $recipeId = (int) $this->pdo->lastInsertId();
            $this->insertIngredients($recipeId, $ingredients);

            $this->pdo->commit();
            return $recipeId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update a recipe the member owns and replace its ingredient list.
     * Returns false if the recipe doesn't exist or belongs to someone else.
     */
    public function update(int $id, int $userId, array $data, array $ingredients): bool
    {
        $this->pdo->beginTransaction();
        try {
            // Lock the row and confirm ownership in the same step.
            $check = $this->pdo->prepare('SELECT id FROM recipes WHERE id = ? AND user_id = ? FOR UPDATE');
            $check->execute([$id, $userId]);
            if (!$check->fetchColumn()) {
                $this->pdo->rollBack();
                return false;
            }

            // updated_at is set explicitly so the "edited" mark also appears
            // when only the ingredients changed.
            $stmt = $this->pdo->prepare(
                'UPDATE recipes
                    SET category_id = :category_id, title = :title, description = :description,
                        steps = :steps, servings = :servings, cook_time_minutes = :cook_time,
                        updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id AND user_id = :user_id'
            );
            $this->bindRecipe($stmt, $data);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $delete = $this->pdo->prepare('DELETE FROM ingredients WHERE recipe_id = ?');
            $delete->execute([$id]);
            $this->insertIngredients($id, $ingredients);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** Ingredients, comments and favorites are removed by ON DELETE CASCADE. */
    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM recipes WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    // ------------------------------------------------------------- helpers

    private function bindRecipe(PDOStatement $stmt, array $data): void
    {
        $stmt->bindValue(':category_id', $data['category_id'], PDO::PARAM_INT);
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':description', $data['description']);
        $stmt->bindValue(':steps', $data['steps']);
        $stmt->bindValue(':servings', $data['servings'], $data['servings'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':cook_time', $data['cook_time_minutes'], $data['cook_time_minutes'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    }

    /** @param list<array{quantity:string, name:string}> $ingredients */
    private function insertIngredients(int $recipeId, array $ingredients): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ingredients (recipe_id, position, quantity, name) VALUES (?, ?, ?, ?)'
        );
        foreach (array_values($ingredients) as $index => $row) {
            $stmt->execute([
                $recipeId,
                $index + 1, // position restarts at 1 for every recipe
                $row['quantity'] === '' ? null : $row['quantity'],
                $row['name'],
            ]);
        }
    }
}
