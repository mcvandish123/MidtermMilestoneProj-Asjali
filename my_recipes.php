<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$session->requireLogin();

$page = positiveInt($_GET['page'] ?? null) ?? 1;
$result = $recipes->search($session->userId(), ['author' => $session->userId()], $page, 12);

$listPage = 'my_recipes.php';
$listParams = [];

$pageTitle = 'My recipes';
$activeNav = 'my_recipes';
require __DIR__ . '/includes/header.php';
?>
<header class="page-header page-header-row">
    <div>
        <p class="eyebrow">Posted by you</p>
        <h1>My recipes</h1>
        <p class="lead">Edit or remove anything you’ve shared. Only you can change these.</p>
    </div>
    <a class="btn" href="recipe_create.php">+ Share a recipe</a>
</header>

<?php if (!$result['items']): ?>
    <section class="empty-state">
        <h2>You haven’t shared anything yet.</h2>
        <p>Your family’s sinigang, your tita’s leche flan — the barangay wants to know.</p>
        <a class="btn" href="recipe_create.php">Share your first recipe</a>
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
