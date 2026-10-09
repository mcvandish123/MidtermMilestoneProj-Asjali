<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$session->requireLogin();

$id = positiveInt($_GET['id'] ?? null);
$recipe = $id ? $recipes->find($id, $session->userId()) : null;
if (!$recipe) {
    notFound($session);
}

$commentList = $comments->forRecipe($id);
$isOwner = (int) $recipe['user_id'] === $session->userId();
$isFavorite = (bool) $recipe['is_favorite'];
$steps = array_values(array_filter(explode("\n", $recipe['steps']), static fn ($s) => trim($s) !== ''));

// A comment that failed server validation comes back here so it isn't lost.
$commentDraft = $session->pull('comment_draft_' . $id, '');
$commentError = $session->pull('comment_error_' . $id, '');

$pageTitle = $recipe['title'];
$activeNav = 'recipes';
require __DIR__ . '/includes/header.php';
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="recipes.php">Recipes</a>
    <span aria-hidden="true">/</span>
    <a href="<?= e(pageUrl('recipes.php', ['category' => (int) $recipe['category_id']])) ?>"><?= e($recipe['category_name']) ?></a>
</nav>

<article class="recipe-detail">
    <header class="recipe-header">
        <a class="tag" href="<?= e(pageUrl('recipes.php', ['category' => (int) $recipe['category_id']])) ?>"><?= e($recipe['category_name']) ?></a>
        <h1><?= e($recipe['title']) ?></h1>
        <p class="lead"><?= e($recipe['description']) ?></p>

        <p class="byline">
            Shared by <strong><?= $isOwner ? 'you' : e($recipe['author_name']) ?></strong>
            on <time datetime="<?= e($recipe['created_at']) ?>"><?= e(formatDateTime($recipe['created_at'])) ?></time>
            <?= editedBadge($recipe) ?>
        </p>

        <div class="recipe-actions">
            <button type="button" class="fav-btn fav-btn-large" data-favorite data-recipe-id="<?= (int) $recipe['id'] ?>"
                    aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>"
                    aria-label="<?= $isFavorite ? 'Remove from favorites' : 'Save to favorites' ?>">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.2C1.4 8 3.3 4.5 6.9 4.5c2.1 0 3.5 1.1 5.1 3 1.6-1.9 3-3 5.1-3 3.6 0 5.5 3.5 4.2 6.8-1.8 4.6-9.3 9.2-9.3 9.2z"/></svg>
                <span class="fav-text"><?= $isFavorite ? 'Saved' : 'Save' ?></span>
                <span class="fav-count" data-fav-count="<?= (int) $recipe['id'] ?>"><?= (int) $recipe['favorite_count'] ?></span>
            </button>

            <?php if ($isOwner): ?>
                <a class="btn btn-ghost" href="recipe_edit.php?id=<?= (int) $recipe['id'] ?>">Edit recipe</a>
                <form method="post" action="recipe_delete.php" data-confirm="Delete this recipe? Its ingredients, comments and saves will be removed too.">
                    <?= csrfField($session) ?>
                    <input type="hidden" name="id" value="<?= (int) $recipe['id'] ?>">
                    <button type="submit" class="btn btn-danger-ghost">Delete</button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <dl class="recipe-facts">
        <?php if ($recipe['cook_time_minutes']): ?>
            <div><dt>Cooking time</dt><dd><?= e(cookTimeLabel((int) $recipe['cook_time_minutes'])) ?></dd></div>
        <?php endif; ?>
        <?php if ($recipe['servings']): ?>
            <div><dt>Serves</dt><dd><?= (int) $recipe['servings'] ?></dd></div>
        <?php endif; ?>
        <div><dt>Ingredients</dt><dd><?= count($recipe['ingredients']) ?></dd></div>
        <div><dt>Comments</dt><dd><?= count($commentList) ?></dd></div>
    </dl>

    <div class="recipe-body">
        <section class="card ingredients-panel" aria-labelledby="ingredients-heading" data-scaler data-base-servings="<?= (int) ($recipe['servings'] ?? 0) ?>">
            <div class="panel-head">
                <h2 id="ingredients-heading">Ingredients</h2>
                <?php if ($recipe['servings']): ?>
                    <div class="scaler" role="group" aria-label="Adjust servings">
                        <button type="button" class="icon-btn" data-scale-step="-1" aria-label="Fewer servings">&minus;</button>
                        <output data-scale-output aria-live="polite"><?= (int) $recipe['servings'] ?> servings</output>
                        <button type="button" class="icon-btn" data-scale-step="1" aria-label="More servings">+</button>
                    </div>
                <?php endif; ?>
            </div>
            <ul class="ingredient-display">
                <?php foreach ($recipe['ingredients'] as $ingredient): ?>
                    <li>
                        <label class="check">
                            <input type="checkbox">
                            <span>
                                <?php if ($ingredient['quantity'] !== null && $ingredient['quantity'] !== ''): ?>
                                    <span class="qty" data-qty="<?= e($ingredient['quantity']) ?>"><?= e($ingredient['quantity']) ?></span>
                                <?php endif; ?>
                                <?= e($ingredient['name']) ?>
                            </span>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($recipe['servings']): ?>
                <p class="hint">Quantities with numbers are scaled for you. Tick items off as you prep.</p>
            <?php else: ?>
                <p class="hint">Tick items off as you prep.</p>
            <?php endif; ?>
        </section>

        <section class="steps-panel" aria-labelledby="steps-heading">
            <h2 id="steps-heading">How to cook it</h2>
            <ol class="step-list">
                <?php foreach ($steps as $step): ?>
                    <li><?= e($step) ?></li>
                <?php endforeach; ?>
            </ol>
        </section>
    </div>
</article>

<section class="comments" id="comments" aria-labelledby="comments-heading">
    <h2 id="comments-heading">What the neighbors said <span class="count"><?= count($commentList) ?></span></h2>

    <?php if (!$commentList): ?>
        <p class="muted">No comments yet. Cooked this? Tell everyone how it went.</p>
    <?php endif; ?>

    <ol class="comment-list">
        <?php foreach ($commentList as $comment): ?>
            <?php $ownComment = (int) $comment['user_id'] === $session->userId(); ?>
            <li class="comment" id="comment-<?= (int) $comment['id'] ?>">
                <div class="comment-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($comment['author_name'], 0, 1))) ?></div>
                <div class="comment-main">
                    <p class="comment-meta">
                        <strong><?= $ownComment ? 'You' : e($comment['author_name']) ?></strong>
                        <time datetime="<?= e($comment['created_at']) ?>"><?= e(formatDateTime($comment['created_at'])) ?></time>
                        <?= editedBadge($comment) ?>
                    </p>
                    <p class="comment-body"><?= nl2br(e($comment['body'])) ?></p>

                    <?php if ($ownComment): ?>
                        <div class="comment-actions">
                            <button type="button" class="link-button" data-edit-toggle="edit-comment-<?= (int) $comment['id'] ?>">Edit</button>
                            <form method="post" action="comment_delete.php" data-confirm="Delete your comment?">
                                <?= csrfField($session) ?>
                                <input type="hidden" name="id" value="<?= (int) $comment['id'] ?>">
                                <button type="submit" class="link-button danger">Delete</button>
                            </form>
                        </div>

                        <form class="comment-edit" id="edit-comment-<?= (int) $comment['id'] ?>" method="post"
                              action="comment_update.php" novalidate data-validate hidden>
                            <?= csrfField($session) ?>
                            <input type="hidden" name="id" value="<?= (int) $comment['id'] ?>">
                            <div class="field">
                                <label class="sr-only" for="body-<?= (int) $comment['id'] ?>">Edit your comment</label>
                                <textarea id="body-<?= (int) $comment['id'] ?>" name="body" rows="3" required
                                          minlength="<?= Validator::COMMENT_MIN ?>" maxlength="<?= Validator::COMMENT_MAX ?>"
                                          data-label="Comment" data-counter data-error-id="error-body-<?= (int) $comment['id'] ?>"
                                          aria-describedby="error-body-<?= (int) $comment['id'] ?>"><?= e($comment['body']) ?></textarea>
                                <p class="field-error" id="error-body-<?= (int) $comment['id'] ?>" aria-live="polite"></p>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-small">Save changes</button>
                                <button type="button" class="btn btn-small btn-ghost" data-edit-toggle="edit-comment-<?= (int) $comment['id'] ?>">Cancel</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>

    <form class="card comment-form" id="comment-form" method="post" action="comment_store.php" novalidate data-validate>
        <?= csrfField($session) ?>
        <input type="hidden" name="recipe_id" value="<?= (int) $recipe['id'] ?>">
        <div class="field">
            <label for="body">Leave a comment</label>
            <textarea id="body" name="body" rows="3" required
                      minlength="<?= Validator::COMMENT_MIN ?>" maxlength="<?= Validator::COMMENT_MAX ?>"
                      data-label="Comment" data-counter
                      placeholder="Did you try it? Any tips or changes?"
                      aria-describedby="error-body"<?= $commentError ? ' aria-invalid="true"' : '' ?>><?= e($commentDraft) ?></textarea>
            <?= fieldError($commentError ? ['body' => $commentError] : [], 'body') ?>
        </div>
        <button type="submit" class="btn">Post comment</button>
    </form>
</section>
<?php
$scripts = ['validate.js', 'favorites.js', 'servings.js'];
require __DIR__ . '/includes/footer.php';
