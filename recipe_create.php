<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$session->requireLogin();

$categoryList = $categories->all();
$errors = [];
$form = [
    'title' => '', 'description' => '', 'category_id' => '', 'steps' => '',
    'servings' => '', 'cook_time_minutes' => '', 'ingredients' => [],
];

if (isPost()) {
    $form = recipeInputFromPost($_POST);

    if (!$session->verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please submit the form again.';
    } else {
        $errors = $validator->recipe($form, $categories->ids());

        if (!$errors) {
            $newId = $recipes->create($session->userId(), recipeDataForDb($form), $form['ingredients']);
            $session->flash('success', 'Your recipe is now shared with the barangay. Salamat!');
            redirect('recipe.php?id=' . $newId);
        }
    }
}

$pageTitle = 'Share a recipe';
$activeNav = 'recipe_create';
require __DIR__ . '/includes/header.php';
?>
<header class="page-header">
    <p class="eyebrow">Share a recipe</p>
    <h1>What are you cooking?</h1>
    <p class="lead">Write it the way you’d explain it to a neighbor in your kitchen.</p>
</header>

<?php
$formAction = 'recipe_create.php';
$submitLabel = 'Share recipe';
$cancelUrl = 'recipes.php';
require __DIR__ . '/includes/recipe_form.php';

$scripts = ['validate.js', 'ingredients.js'];
require __DIR__ . '/includes/footer.php';
