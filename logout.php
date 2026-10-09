<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

requirePost();

if (!$session->verifyCsrf($_POST['csrf_token'] ?? null)) {
    $session->flash('error', 'Your session expired. Please try logging out again.');
    redirect('recipes.php');
}

$session->logout();
redirect('login.php?logged_out=1');
