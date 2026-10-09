<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$session->requireLogin();
requirePost();

$recipeId = positiveInt($_POST['recipe_id'] ?? null);
if (!$recipeId || !$recipes->exists($recipeId)) {
    notFound($session);
}

$back = 'recipe.php?id=' . $recipeId;
$body = str_replace(["\r\n", "\r"], "\n", input($_POST, 'body'));

if (!$session->verifyCsrf($_POST['csrf_token'] ?? null)) {
    $session->put('comment_draft_' . $recipeId, $body);
    $session->flash('error', 'Your session expired. Please post your comment again.');
    redirect($back . '#comment-form');
}

$errors = $validator->comment($body);
if ($errors) {
    // Keep what they typed so they can fix it.
    $session->put('comment_draft_' . $recipeId, $body);
    $session->put('comment_error_' . $recipeId, $errors['body']);
    redirect($back . '#comment-form');
}

$commentId = $comments->create($recipeId, $session->userId(), $body);
$session->flash('success', 'Comment posted.');
redirect($back . '#comment-' . $commentId);
