<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$session->requireGuest();

$errors = [];
$form = ['username' => '', 'display_name' => '', 'email' => ''];

if (isPost()) {
    $form = [
        'username'     => input($_POST, 'username'),
        'display_name' => input($_POST, 'display_name'),
        'email'        => mb_strtolower(input($_POST, 'email')),
    ];
    // Passwords are not trimmed: spaces are allowed and count.
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm  = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';

    if (!$session->verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'Your session expired. Please submit the form again.';
    } else {
        $errors = $validator->registration($form + ['password' => $password, 'password_confirm' => $confirm]);

        if (!isset($errors['username']) && $users->usernameExists($form['username'])) {
            $errors['username'] = 'That username is already taken.';
        }
        if (!isset($errors['email']) && $users->emailExists($form['email'])) {
            $errors['email'] = 'An account with that email already exists.';
        }

        if (!$errors) {
            $userId = $users->create(
                $form['username'],
                $form['email'],
                password_hash($password, PASSWORD_DEFAULT), // never store the plain password
                $form['display_name']
            );

            $session->login($users->findById($userId));
            $session->flash('success', 'Welcome to the kusina, ' . $form['display_name'] . '!');
            redirect('recipes.php');
        }
    }
}

$pageTitle = 'Join';
$activeNav = 'register';
$bodyClass = 'auth-page';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-layout">
    <div class="auth-intro">
        <p class="eyebrow">Para sa mga kapitbahay</p>
        <h1>Share the dishes your family grew up on.</h1>
        <p>Post your recipes, save your neighbors’ best ones, and tell them how it turned out.</p>
    </div>

    <form class="card auth-card" method="post" action="register.php" novalidate data-validate>
        <h2>Create your account</h2>
        <?= csrfField($session) ?>

        <?php if (isset($errors['form'])): ?>
            <div class="form-alert" role="alert"><?= e($errors['form']) ?></div>
        <?php endif; ?>

        <div class="field">
            <label for="display_name">Display name</label>
            <input id="display_name" name="display_name" type="text" autocomplete="name" required
                   minlength="<?= Validator::DISPLAY_MIN ?>" maxlength="<?= Validator::DISPLAY_MAX ?>"
                   data-label="Display name" value="<?= e($form['display_name']) ?>" placeholder="Aling Nena"
                   aria-describedby="error-display_name"<?= invalidAttr($errors, 'display_name') ?>>
            <?= fieldError($errors, 'display_name') ?>
        </div>

        <div class="field">
            <label for="username">Username</label>
            <input id="username" name="username" type="text" autocomplete="username" required
                   minlength="<?= Validator::USERNAME_MIN ?>" maxlength="<?= Validator::USERNAME_MAX ?>"
                   pattern="<?= e(Validator::USERNAME_PATTERN) ?>"
                   data-pattern-message="Use only letters, numbers and underscores."
                   data-label="Username" value="<?= e($form['username']) ?>" placeholder="aling_nena"
                   aria-describedby="error-username"<?= invalidAttr($errors, 'username') ?>>
            <?= fieldError($errors, 'username') ?>
        </div>

        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" required
                   maxlength="<?= Validator::EMAIL_MAX ?>" data-label="Email"
                   value="<?= e($form['email']) ?>" placeholder="you@example.com"
                   aria-describedby="error-email"<?= invalidAttr($errors, 'email') ?>>
            <?= fieldError($errors, 'email') ?>
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required
                   minlength="<?= Validator::PASSWORD_MIN ?>" maxlength="<?= Validator::PASSWORD_MAX ?>"
                   data-label="Password" data-rule="password"
                   aria-describedby="password-hint error-password"<?= invalidAttr($errors, 'password') ?>>
            <p class="hint" id="password-hint">At least <?= Validator::PASSWORD_MIN ?> characters, with a letter and a number.</p>
            <?= fieldError($errors, 'password') ?>
        </div>

        <div class="field">
            <label for="password_confirm">Confirm password</label>
            <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required
                   data-label="Password confirmation" data-match="password"
                   aria-describedby="error-password_confirm"<?= invalidAttr($errors, 'password_confirm') ?>>
            <?= fieldError($errors, 'password_confirm') ?>
        </div>

        <button type="submit" class="btn btn-block">Create account</button>
        <p class="auth-switch">Already a member? <a href="login.php">Log in</a></p>
    </form>
</section>
<?php
$scripts = ['validate.js'];
require __DIR__ . '/includes/footer.php';
