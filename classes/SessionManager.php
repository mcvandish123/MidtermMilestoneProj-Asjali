<?php
declare(strict_types=1);

/**
 * Owns everything stored in $_SESSION: login state, flash messages,
 * one-time values (like a comment draft) and the CSRF token.
 */
final class SessionManager
{
    private const USER_ID      = 'user_id';
    private const DISPLAY_NAME = 'display_name';
    private const USERNAME     = 'username';
    private const FLASHES      = 'flashes';
    private const CSRF         = 'csrf_token';

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        session_name('kusina_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,     // JavaScript cannot read the session cookie
            'secure'   => $isHttps, // only over HTTPS when the site uses HTTPS
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();
    }

    // ---------------------------------------------------------------- auth

    /** @param array{id:int|string, username:string, display_name:string} $user */
    public function login(array $user): void
    {
        session_regenerate_id(true); // new session id after login (prevents session fixation)
        $_SESSION[self::USER_ID]      = (int) $user['id'];
        $_SESSION[self::USERNAME]     = $user['username'];
        $_SESSION[self::DISPLAY_NAME] = $user['display_name'];
        unset($_SESSION[self::CSRF]);
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION[self::USER_ID]);
    }

    public function userId(): ?int
    {
        return $this->isLoggedIn() ? (int) $_SESSION[self::USER_ID] : null;
    }

    public function displayName(): string
    {
        return (string) ($_SESSION[self::DISPLAY_NAME] ?? '');
    }

    public function username(): string
    {
        return (string) ($_SESSION[self::USERNAME] ?? '');
    }

    /** Guests are sent to the login page. */
    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            $this->flash('info', 'Please log in or create an account first.');
            redirect('login.php');
        }
    }

    /** Logged-in members don't need the login/register pages. */
    public function requireGuest(): void
    {
        if ($this->isLoggedIn()) {
            redirect('recipes.php');
        }
    }

    // ------------------------------------------------------------- flashes

    public function flash(string $type, string $message): void
    {
        $_SESSION[self::FLASHES][] = ['type' => $type, 'message' => $message];
    }

    /** @return list<array{type:string, message:string}> */
    public function pullFlashes(): array
    {
        $flashes = $_SESSION[self::FLASHES] ?? [];
        unset($_SESSION[self::FLASHES]);
        return $flashes;
    }

    /** Store a value for the next request only. */
    public function put(string $key, mixed $value): void
    {
        $_SESSION['once'][$key] = $value;
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['once'][$key] ?? $default;
        unset($_SESSION['once'][$key]);
        return $value;
    }

    // ---------------------------------------------------------------- CSRF

    public function csrfToken(): string
    {
        if (empty($_SESSION[self::CSRF])) {
            $_SESSION[self::CSRF] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::CSRF];
    }

    public function verifyCsrf(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION[self::CSRF])
            && hash_equals($_SESSION[self::CSRF], $token);
    }
}
