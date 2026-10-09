<?php
declare(strict_types=1);

/** Escape anything a user typed before it goes into HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function isPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** For action-only scripts (delete, logout, ...): reject anything but POST. */
function requirePost(): void
{
    if (!isPost()) {
        http_response_code(405);
        header('Allow: POST');
        exit('Method not allowed.');
    }
}

/** Read a trimmed string from $_POST / $_GET. */
function input(array $source, string $key): string
{
    $value = $source[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

/** Read a positive integer id, or null. */
function positiveInt(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

function csrfField(SessionManager $session): string
{
    return '<input type="hidden" name="csrf_token" value="' . e($session->csrfToken()) . '">';
}

/** Error slot under a form field. Always rendered so JS can fill it too. */
function fieldError(array $errors, string $field): string
{
    $message = $errors[$field] ?? '';
    return '<p class="field-error" id="error-' . e($field) . '" aria-live="polite">' . e($message) . '</p>';
}

function invalidAttr(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' aria-invalid="true"' : '';
}

function navLink(string $href, string $label, string $active): string
{
    $current = basename($href, '.php') === $active ? ' aria-current="page"' : '';
    return '<a href="' . e($href) . '"' . $current . '>' . e($label) . '</a>';
}

function formatDate(?string $timestamp): string
{
    return $timestamp ? date('M j, Y', strtotime($timestamp)) : '';
}

function formatDateTime(?string $timestamp): string
{
    return $timestamp ? date('M j, Y · g:i A', strtotime($timestamp)) : '';
}

/** "Edited" label for recipes and comments (updated_at stays NULL until edited). */
function editedBadge(array $row): string
{
    if (empty($row['updated_at'])) {
        return '';
    }
    return '<span class="edited" title="Last edited ' . e(formatDateTime($row['updated_at'])) . '">Edited '
        . e(formatDate($row['updated_at'])) . '</span>';
}

function plural(int $count, string $singular, ?string $pluralForm = null): string
{
    return $count . ' ' . ($count === 1 ? $singular : ($pluralForm ?? $singular . 's'));
}

function cookTimeLabel(?int $minutes): string
{
    if (!$minutes) {
        return '';
    }
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $hours = intdiv($minutes, 60);
    $rest = $minutes % 60;
    return $hours . ' hr' . ($rest ? ' ' . $rest . ' min' : '');
}

/** Build a URL to the current list page keeping the current filters. */
function pageUrl(string $page, array $params): string
{
    $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
    if (($params['page'] ?? null) === 1) {
        unset($params['page']);
    }
    return $page . ($params ? '?' . http_build_query($params) : '');
}

/**
 * Read the recipe form from $_POST into one array.
 * Ingredient rows that are completely blank are dropped; half-filled rows are kept
 * so the validator can report them.
 */
function recipeInputFromPost(array $post): array
{
    $quantities = is_array($post['ingredient_qty'] ?? null) ? $post['ingredient_qty'] : [];
    $names = is_array($post['ingredient_name'] ?? null) ? $post['ingredient_name'] : [];

    $ingredients = [];
    $rows = max(count($quantities), count($names));
    for ($i = 0; $i < $rows; $i++) {
        $qty = is_string($quantities[$i] ?? null) ? trim($quantities[$i]) : '';
        $name = is_string($names[$i] ?? null) ? trim($names[$i]) : '';
        if ($qty === '' && $name === '') {
            continue;
        }
        $ingredients[] = ['quantity' => $qty, 'name' => $name];
    }

    // Normalise line endings and drop blank lines in the steps.
    $steps = str_replace(["\r\n", "\r"], "\n", input($post, 'steps'));
    $steps = implode("\n", array_filter(array_map('trim', explode("\n", $steps)), static fn ($l) => $l !== ''));

    return [
        'title'             => input($post, 'title'),
        'description'       => input($post, 'description'),
        'category_id'       => input($post, 'category_id'),
        'steps'             => $steps,
        'servings'          => input($post, 'servings'),
        'cook_time_minutes' => input($post, 'cook_time_minutes'),
        'ingredients'       => $ingredients,
    ];
}

/** Convert validated form input into the types the repository expects. */
function recipeDataForDb(array $form): array
{
    return [
        'category_id'       => (int) $form['category_id'],
        'title'             => $form['title'],
        'description'       => $form['description'],
        'steps'             => $form['steps'],
        'servings'          => $form['servings'] === '' ? null : (int) $form['servings'],
        'cook_time_minutes' => $form['cook_time_minutes'] === '' ? null : (int) $form['cook_time_minutes'],
    ];
}

/** Render a 404 page and stop. */
function notFound(SessionManager $session, string $message = 'That recipe could not be found.'): never
{
    http_response_code(404);
    $pageTitle = 'Not found';
    $activeNav = '';
    require __DIR__ . '/header.php';
    echo '<section class="empty-state"><h1>Wala dito.</h1><p>' . e($message) . '</p>'
        . '<a class="btn" href="recipes.php">Back to recipes</a></section>';
    require __DIR__ . '/footer.php';
    exit;
}
