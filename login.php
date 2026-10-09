<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$session->requireGuest();

$errors = [];
$form = ['login' => ''];

if (isset($_GET['logged_out'])) {
    $session->flash('success', 'You have been logged out. Ingat!');
    redirect('login.php');
}

if (isPost()) {
    $form['login'] = input($_POST, 'login');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!$session->verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please try again.';
    } else {
        $errors = $validator->login(['login' => $form['login'], 'password' => $password]);

        if (!$errors) {
            $user = $users->findByLogin($form['login']);

            if ($user && password_verify($password, $user['password_hash'])) {
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    $users->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
                }
                $session->login($user);
                $session->flash('success', 'Maligayang pagbabalik, ' . $user['display_name'] . '!');
                redirect('recipes.php');
            }

            // Same message whether the account exists or not.
            $errors['form'] = 'Incorrect username/email or password.';
        }
    }
}

$pageTitle = 'Log in';
$activeNav = 'login';
$bodyClass = 'auth-page';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout">
    <div class="auth-intro">
        <p class="eyebrow">Kain tayo!</p>
        <h1>The barangay’s recipe box, all in one place.</h1>
        <p>Log in to browse what your neighbors are cooking, save your favorites and leave feedback.</p>
    </div>

    <form class="card auth-card" method="post" action="login.php" novalidate data-validate>
        <h2>Log in</h2>
        <?= csrfField($session) ?>

        <?php if (isset($errors['form'])): ?>
            <div class="form-alert" role="alert"><?= e($errors['form']) ?></div>
        <?php endif; ?>

        <div class="field">
            <label for="login">Username or email</label>
            <input id="login" name="login" type="text" autocomplete="username" required
                   maxlength="<?= Validator::EMAIL_MAX ?>" data-label="Username or email"
                   value="<?= e($form['login']) ?>"
                   aria-describedby="error-login"<?= invalidAttr($errors, 'login') ?>>
            <?= fieldError($errors, 'login') ?>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required
                   data-label="Password"
                   aria-describedby="error-password"<?= invalidAttr($errors, 'password') ?>>
            <?= fieldError($errors, 'password') ?>
        </div>

        <button type="submit" class="btn btn-block">Log in</button>
        <p class="auth-switch">New here? <a href="register.php">Create an account</a></p>
    </form>
</section>
<?php
$scripts = ['validate.js'];
require __DIR__ . '/includes/footer.php';
