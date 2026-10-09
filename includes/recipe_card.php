<?php
/**
 * One recipe card. Expects: $recipe (row from RecipeRepository::search), $session.
 * Optional: $removeOnUnfavorite (true on the favorites page).
 */
$isOwner = (int) $recipe['user_id'] === $session->userId();
$isFavorite = (bool) $recipe['is_favorite'];
$favCount = (int) $recipe['favorite_count'];
?>
<article class="recipe-card" data-recipe-card="<?= (int) $recipe['id'] ?>">
    <div class="card-top">
        <a class="tag" href="<?= e(pageUrl('recipes.php', ['category' => (int) $recipe['category_id']])) ?>">
            <?= e($recipe['category_name']) ?>
        </a>
        <button type="button"
                class="fav-btn"
                data-favorite
                data-recipe-id="<?= (int) $recipe['id'] ?>"
                <?= !empty($removeOnUnfavorite) ? 'data-remove-card' : '' ?>
                aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>"
                aria-label="<?= $isFavorite ? 'Remove from favorites' : 'Save to favorites' ?>">
            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 8 3.3 4.5 6.9 4.5c2.1 0 3.5 1.1 5.1 3 1.6-1.9 3-3 5.1-3 3.6 0 5.5 3.5 4.2 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg>
            <span class="fav-count" data-fav-count="<?= (int) $recipe['id'] ?>"><?= $favCount ?></span>
        </button>
    </div>

    <h3 class="card-title">
        <a href="recipe.php?id=<?= (int) $recipe['id'] ?>"><?= e($recipe['title']) ?></a>
    </h3>
    <p class="card-description"><?= e($recipe['description']) ?></p>

    <ul class="card-meta">
        <li>by <?= $isOwner ? '<strong>you</strong>' : e($recipe['author_name']) ?></li>
        <?php if ($recipe['cook_time_minutes']): ?>
            <li><?= e(cookTimeLabel((int) $recipe['cook_time_minutes'])) ?></li>
        <?php endif; ?>
        <li><?= e(plural((int) $recipe['comment_count'], 'comment')) ?></li>
    </ul>

    <div class="card-footer">
        <time datetime="<?= e($recipe['created_at']) ?>"><?= e(formatDate($recipe['created_at'])) ?></time>
        <?= editedBadge($recipe) ?>
        <?php if ($isOwner): ?>
            <span class="card-owner-actions">
                <a href="recipe_edit.php?id=<?= (int) $recipe['id'] ?>">Edit</a>
                <form method="post" action="recipe_delete.php" data-confirm="Delete “<?= e($recipe['title']) ?>”? This also removes its comments.">
                    <?= csrfField($session) ?>
                    <input type="hidden" name="id" value="<?= (int) $recipe['id'] ?>">
                    <button type="submit" class="link-button danger">Delete</button>
                </form>
            </span>
        <?php endif; ?>
    </div>
</article>
