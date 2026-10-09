<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$session->requireLogin();

$id = positiveInt($_GET['id'] ?? null);
if (!$id) {
    notFound($session);
}

// Only the owner gets the recipe back; anyone else is turned away.
$existing = $recipes->findOwned($id, $session->userId());
if (!$existing) {
    if ($recipes->exists($id)) {
        $session->flash('error', 'You can only edit recipes you posted.');
        redirect('recipe.php?id=' . $id);
    }
    notFound($session);
}

$categoryList = $categories->all();
$errors = [];
$form = [
    'title'             => $existing['title'],
    'description'       => $existing['description'],
    'category_id'       => (string) $existing['category_id'],
    'steps'             => $existing['steps'],
    'servings'          => (string) ($existing['servings'] ?? ''),
    'cook_time_minutes' => (string) ($existing['cook_time_minutes'] ?? ''),
    'ingredients'       => array_map(
        static fn (array $i) => ['quantity' => (string) ($i['quantity'] ?? ''), 'name' => $i['name']],
        $existing['ingredients']
    ),
];

if (isPost()) {
    $form = recipeInputFromPost($_POST);

    if (!$session->verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please submit the form again.';
    } else {
        $errors = $validator->recipe($form, $categories->ids());

        if (!$errors) {
            if ($recipes->update($id, $session->userId(), recipeDataForDb($form), $form['ingredients'])) {
                $session->flash('success', 'Recipe updated. Members will see that it was edited.');
            } else {
                $session->flash('error', 'You can only edit recipes you posted.');
            }
            redirect('recipe.php?id=' . $id);
        }
    }
}

$pageTitle = 'Edit ' . $existing['title'];
$activeNav = 'my_recipes';
require __DIR__ . '/includes/header.php';
?>
<header class="page-header">
    <p class="eyebrow">Edit recipe</p>
    <h1><?= e($existing['title']) ?></h1>
    <p class="lead">Posted <?= e(formatDateTime($existing['created_at'])) ?>. Saving will mark it as edited.</p>
</header>

<?php
$formAction = 'recipe_edit.php?id=' . $id;
$submitLabel = 'Save changes';
$cancelUrl = 'recipe.php?id=' . $id;
require __DIR__ . '/includes/recipe_form.php';

$scripts = ['validate.js', 'ingredients.js'];
require __DIR__ . '/includes/footer.php';
