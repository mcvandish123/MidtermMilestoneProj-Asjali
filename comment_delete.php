<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$session->requireLogin();
requirePost();

$id = positiveInt($_POST['id'] ?? null);
$comment = $id ? $comments->findOwned($id, $session->userId()) : null;

if (!$comment) {
    $session->flash('error', 'You can only delete your own comments.');
    redirect('recipes.php');
}

$back = 'recipe.php?id=' . (int) $comment['recipe_id'] . '#comments';

if (!$session->verifyCsrf($_POST['csrf_token'] ?? null)) {
    $session->flash('error', 'Your session expired. Please try again.');
    redirect($back);
}

$comments->delete($id, $session->userId());
$session->flash('success', 'Comment deleted.');
redirect($back);
