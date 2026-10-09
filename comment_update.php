<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$session->requireLogin();
requirePost();

$id = positiveInt($_POST['id'] ?? null);
$comment = $id ? $comments->findOwned($id, $session->userId()) : null;

if (!$comment) {
    $session->flash('error', 'You can only edit your own comments.');
    redirect('recipes.php');
}

$back = 'recipe.php?id=' . (int) $comment['recipe_id'] . '#comment-' . $id;

if (!$session->verifyCsrf($_POST['csrf_token'] ?? null)) {
    $session->flash('error', 'Your session expired. Please try again.');
    redirect($back);
}

$body = str_replace(["\r\n", "\r"], "\n", input($_POST, 'body'));
$errors = $validator->comment($body);

if ($errors) {
    $session->flash('error', 'Comment not saved: ' . $errors['body']);
} elseif ($body === $comment['body']) {
    $session->flash('info', 'No changes to save.');
} else {
    $comments->update($id, $session->userId(), $body);
    $session->flash('success', 'Comment updated.');
}

redirect($back);
