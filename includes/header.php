<?php
/**
 * Page header. Expects: $session, $pageTitle, $activeNav ('recipes', 'recipe_create', ...).
 * Optional: $bodyClass.
 */
$pageTitle = $pageTitle ?? 'Recipes';
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e($session->csrfToken()) ?>">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= $session->isLoggedIn() ? 'recipes.php' : 'login.php' ?>">
            <span class="brand-mark" aria-hidden="true">K</span>
            <span class="brand-text">Kusina <span>ng Barangay</span></span>
        </a>

        <?php if ($session->isLoggedIn()): ?>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
                <span class="nav-toggle-bars" aria-hidden="true"></span>
                <span class="sr-only">Menu</span>
            </button>
            <nav id="site-nav" class="site-nav" aria-label="Main">
                <?= navLink('recipes.php', 'Recipes', $activeNav) ?>
                <?= navLink('recipe_create.php', 'Share a recipe', $activeNav) ?>
                <?= navLink('favorites.php', 'Favorites', $activeNav) ?>
                <?= navLink('my_recipes.php', 'My recipes', $activeNav) ?>
                <div class="nav-user">
                    <span class="nav-user-name">Hi, <?= e($session->displayName()) ?></span>
                    <form method="post" action="logout.php">
                        <?= csrfField($session) ?>
                        <button type="submit" class="link-button">Log out</button>
                    </form>
                </div>
            </nav>
        <?php else: ?>
            <nav class="site-nav site-nav-guest" aria-label="Account">
                <?= navLink('login.php', 'Log in', $activeNav) ?>
                <a class="btn btn-small" href="register.php">Join</a>
            </nav>
        <?php endif; ?>
    </div>
    <div class="banig" aria-hidden="true"></div>
</header>

<main id="main" class="container page">
    <?php foreach ($session->pullFlashes() as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>" role="status">
            <span><?= e($flash['message']) ?></span>
            <button type="button" class="flash-close" aria-label="Dismiss">&times;</button>
        </div>
    <?php endforeach; ?>
