<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$session->requireLogin();
requirePost();

if (!$session->verifyCsrf($_POST['csrf_token'] ?? null)) {
    $session->flash('error', 'Your session expired. Please try again.');
    redirect('my_recipes.php');
}

$id = positiveInt($_POST['id'] ?? null);

// The DELETE itself checks user_id, so nobody can remove someone else's recipe.
if ($id && $recipes->delete($id, $session->userId())) {
    $session->flash('success', 'Recipe deleted.');
} else {
    $session->flash('error', 'That recipe could not be deleted. You can only delete recipes you posted.');
}

redirect('my_recipes.php');
