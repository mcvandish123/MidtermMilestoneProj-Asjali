<?php
/**
 * JSON endpoint used by assets/js/favorites.js to save / unsave a recipe
 * without reloading the page.
 *
 * POST recipe_id=<id>&action=add|remove   (header X-CSRF-Token: <token>)
 * → {"ok": true, "favorited": true, "count": 3}
 */
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (!isPost()) {
    respond(405, ['ok' => false, 'error' => 'Use POST.']);
}
if (!$session->isLoggedIn()) {
    respond(401, ['ok' => false, 'error' => 'Please log in again.']);
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
if (!$session->verifyCsrf(is_string($token) ? $token : null)) {
    respond(403, ['ok' => false, 'error' => 'Your session expired. Refresh the page and try again.']);
}

$recipeId = positiveInt($_POST['recipe_id'] ?? null);
$action = input($_POST, 'action');

if (!$recipeId || !in_array($action, ['add', 'remove'], true)) {
    respond(422, ['ok' => false, 'error' => 'Invalid request.']);
}
if (!$recipes->exists($recipeId)) {
    respond(404, ['ok' => false, 'error' => 'That recipe no longer exists.']);
}

$userId = $session->userId();
if ($action === 'add') {
    $favorites->add($userId, $recipeId);
} else {
    $favorites->remove($userId, $recipeId);
}

respond(200, [
    'ok'        => true,
    'favorited' => $favorites->isFavorite($userId, $recipeId),
    'count'     => $favorites->countFor($recipeId),
]);
