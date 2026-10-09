<?php
/**
 * Included at the top of every page. Loads the DB connection file, the classes
 * and helpers, starts the session, and creates the shared objects.
 */
declare(strict_types=1);

const APP_NAME = 'Kusina ng Barangay';

date_default_timezone_set('Asia/Manila');
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

spl_autoload_register(static function (string $class): void {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

$session = new SessionManager();
$session->start();

try {
    $pdo = Database::connection();
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('<h1>Database connection failed</h1><p>Check the settings in <code>config/database.php</code> and make sure MySQL is running and <code>recipe_db</code> is imported.</p>');
}

$validator  = new Validator();
$users      = new UserRepository($pdo);
$categories = new CategoryRepository($pdo);
$recipes    = new RecipeRepository($pdo);
$comments   = new CommentRepository($pdo);
$favorites  = new FavoriteRepository($pdo);
