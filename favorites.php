<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$session->requireLogin();

$page = positiveInt($_GET['page'] ?? null) ?? 1;
$result = $recipes->search($session->userId(), ['favorites_of' => $session->userId()], $page, 12);

$listPage = 'favorites.php';
$listParams = [];

$pageTitle = 'Favorites';
$activeNav = 'favorites';
require __DIR__ . '/includes/header.php';
?>
<header class="page-header">
    <p class="eyebrow">Your recipe box</p>
    <h1>Favorites</h1>
    <p class="lead">Recipes you saved. Tap the heart to remove one — no reload needed.</p>
</header>

<section class="empty-state" data-favorites-empty <?= $result['items'] ? 'hidden' : '' ?>>
    <h2>Wala pang naka-save.</h2>
    <p>Tap the heart on any recipe to keep it here for later.</p>
    <a class="btn" href="recipes.php">Browse recipes</a>
</section>

<?php if ($result['items']): ?>
    <div class="recipe-grid" data-favorites-grid>
        <?php $removeOnUnfavorite = true; ?>
        <?php foreach ($result['items'] as $recipe): ?>
            <?php require __DIR__ . '/includes/recipe_card.php'; ?>
        <?php endforeach; ?>
    </div>
    <?php require __DIR__ . '/includes/pagination.php'; ?>
<?php endif; ?>
<?php
$scripts = ['favorites.js'];
require __DIR__ . '/includes/footer.php';
