<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$session->requireLogin();

$q = mb_substr(input($_GET, 'q'), 0, Validator::SEARCH_MAX);
$categoryId = positiveInt($_GET['category'] ?? null);
$page = positiveInt($_GET['page'] ?? null) ?? 1;

$categoryList = $categories->all();
$activeCategory = null;
foreach ($categoryList as $category) {
    if ((int) $category['id'] === $categoryId) {
        $activeCategory = $category;
    }
}
if ($categoryId !== null && $activeCategory === null) {
    $categoryId = null; // unknown category id in the URL: show everything
}

$result = $recipes->search($session->userId(), ['q' => $q, 'category' => $categoryId], $page);

$listPage = 'recipes.php';
$listParams = ['q' => $q, 'category' => $categoryId];

$pageTitle = 'Recipes';
$activeNav = 'recipes';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div>
        <p class="eyebrow">Ano’ng ulam natin?</p>
        <h1>Fresh from the barangay kitchen</h1>
        <p class="lead">The latest recipes from your neighbors, newest first. Search by dish, description or ingredient.</p>
    </div>

    <form class="search-bar" method="get" action="recipes.php" role="search">
        <label class="sr-only" for="q">Search recipes</label>
        <input id="q" name="q" type="search" value="<?= e($q) ?>" maxlength="<?= Validator::SEARCH_MAX ?>"
               placeholder="Try “adobo”, “gata” or “kangkong”">
        <label class="sr-only" for="category">Category</label>
        <select id="category" name="category" data-autosubmit>
            <option value="">All categories</option>
            <?php foreach ($categoryList as $category): ?>
                <option value="<?= (int) $category['id'] ?>" <?= (int) $category['id'] === $categoryId ? 'selected' : '' ?>>
                    <?= e($category['name']) ?> (<?= (int) $category['recipe_count'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn">Search</button>
    </form>
</section>

<nav class="chips" aria-label="Categories">
    <a class="chip" href="<?= e(pageUrl('recipes.php', ['q' => $q])) ?>" <?= $categoryId === null ? 'aria-current="page"' : '' ?>>All</a>
    <?php foreach ($categoryList as $category): ?>
        <a class="chip"
           href="<?= e(pageUrl('recipes.php', ['q' => $q, 'category' => (int) $category['id']])) ?>"
           <?= (int) $category['id'] === $categoryId ? 'aria-current="page"' : '' ?>>
            <?= e($category['name']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<div class="results-bar">
    <p>
        <strong><?= e(plural($result['total'], 'recipe')) ?></strong>
        <?php if ($q !== ''): ?> matching “<?= e($q) ?>”<?php endif; ?>
        <?php if ($activeCategory): ?> in <?= e($activeCategory['name']) ?><?php endif; ?>
    </p>
    <?php if ($q !== '' || $categoryId !== null): ?>
        <a href="recipes.php">Clear filters</a>
    <?php endif; ?>
</div>

<?php if (!$result['items']): ?>
    <section class="empty-state">
        <h2>Walang nahanap.</h2>
        <p>No recipes match yet. Try another word, or be the first to share one.</p>
        <a class="btn" href="recipe_create.php">Share a recipe</a>
    </section>
<?php else: ?>
    <div class="recipe-grid">
        <?php foreach ($result['items'] as $recipe): ?>
            <?php require __DIR__ . '/includes/recipe_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php require __DIR__ . '/includes/pagination.php'; ?>
<?php endif; ?>
<?php
$scripts = ['favorites.js'];
require __DIR__ . '/includes/footer.php';
