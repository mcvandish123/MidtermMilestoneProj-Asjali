<?php
declare(strict_types=1);

/**
 * Server-side validation rules. The same limits are written as HTML attributes
 * (required, minlength, maxlength, pattern, min, max) so assets/js/validate.js
 * checks the same rules in the browser.
 *
 * Every method returns an array of errors keyed by field name (empty = valid).
 */
final class Validator
{
    public const USERNAME_MIN = 3;
    public const USERNAME_MAX = 30;
    public const USERNAME_PATTERN = '^[A-Za-z0-9_]+$';
    public const DISPLAY_MIN = 2;
    public const DISPLAY_MAX = 60;
    public const EMAIL_MAX = 255;
    public const PASSWORD_MIN = 8;
    public const PASSWORD_MAX = 72; // bcrypt only uses the first 72 bytes

    public const TITLE_MIN = 3;
    public const TITLE_MAX = 120;
    public const DESCRIPTION_MIN = 10;
    public const DESCRIPTION_MAX = 500;
    public const STEPS_MIN = 10;
    public const STEPS_MAX = 5000;
    public const INGREDIENTS_MAX = 50;
    public const INGREDIENT_NAME_MAX = 150;
    public const INGREDIENT_QTY_MAX = 50;
    public const SERVINGS_MAX = 50;
    public const COOK_TIME_MAX = 1440;

    public const COMMENT_MIN = 2;
    public const COMMENT_MAX = 1000;
    public const SEARCH_MAX = 100;

    /** @var array<string, string> */
    private array $errors = [];

    /** @param array<string, string> $data */
    public function registration(array $data): array
    {
        $this->errors = [];

        $this->text('username', $data['username'], 'Username', self::USERNAME_MIN, self::USERNAME_MAX);
        if (!isset($this->errors['username'])
            && !preg_match('/' . self::USERNAME_PATTERN . '/', $data['username'])) {
            $this->add('username', 'Use only letters, numbers and underscores.');
        }

        $this->text('display_name', $data['display_name'], 'Display name', self::DISPLAY_MIN, self::DISPLAY_MAX);

        $this->text('email', $data['email'], 'Email', 1, self::EMAIL_MAX);
        if (!isset($this->errors['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $this->add('email', 'Enter a valid email address.');
        }

        $this->password('password', $data['password']);

        if ($data['password_confirm'] === '') {
            $this->add('password_confirm', 'Please confirm your password.');
        } elseif ($data['password_confirm'] !== $data['password']) {
            $this->add('password_confirm', 'Passwords do not match.');
        }

        return $this->errors;
    }

    /** @param array<string, string> $data */
    public function login(array $data): array
    {
        $this->errors = [];
        if ($data['login'] === '') {
            $this->add('login', 'Username or email is required.');
        }
        if ($data['password'] === '') {
            $this->add('password', 'Password is required.');
        }
        return $this->errors;
    }

    /**
     * @param array{title:string, description:string, category_id:string, steps:string,
     *              servings:string, cook_time_minutes:string,
     *              ingredients:list<array{quantity:string, name:string}>} $data
     * @param list<int> $categoryIds
     */
    public function recipe(array $data, array $categoryIds): array
    {
        $this->errors = [];

        $this->text('title', $data['title'], 'Title', self::TITLE_MIN, self::TITLE_MAX);
        $this->text('description', $data['description'], 'Description', self::DESCRIPTION_MIN, self::DESCRIPTION_MAX);
        $this->text('steps', $data['steps'], 'Cooking steps', self::STEPS_MIN, self::STEPS_MAX);

        if ($data['category_id'] === '') {
            $this->add('category_id', 'Choose a category.');
        } elseif (!ctype_digit($data['category_id']) || !in_array((int) $data['category_id'], $categoryIds, true)) {
            $this->add('category_id', 'Choose a category from the list.');
        }

        $this->optionalWholeNumber('servings', $data['servings'], 'Servings', 1, self::SERVINGS_MAX);
        $this->optionalWholeNumber('cook_time_minutes', $data['cook_time_minutes'], 'Cooking time', 1, self::COOK_TIME_MAX);

        $ingredients = $data['ingredients'];
        if (count($ingredients) === 0) {
            $this->add('ingredients', 'Add at least one ingredient.');
        } elseif (count($ingredients) > self::INGREDIENTS_MAX) {
            $this->add('ingredients', 'A recipe can have at most ' . self::INGREDIENTS_MAX . ' ingredients.');
        } else {
            foreach ($ingredients as $i => $row) {
                $number = $i + 1;
                if ($row['name'] === '') {
                    $this->add('ingredients', "Ingredient #{$number} needs a name.");
                } elseif (mb_strlen($row['name']) > self::INGREDIENT_NAME_MAX) {
                    $this->add('ingredients', "Ingredient #{$number} name is too long (max " . self::INGREDIENT_NAME_MAX . ' characters).');
                } elseif (mb_strlen($row['quantity']) > self::INGREDIENT_QTY_MAX) {
                    $this->add('ingredients', "Ingredient #{$number} quantity is too long (max " . self::INGREDIENT_QTY_MAX . ' characters).');
                }
            }
        }

        return $this->errors;
    }

    public function comment(string $body): array
    {
        $this->errors = [];
        $this->text('body', $body, 'Comment', self::COMMENT_MIN, self::COMMENT_MAX);
        return $this->errors;
    }

    // ------------------------------------------------------------- helpers

    private function text(string $field, string $value, string $label, int $min, int $max): void
    {
        $length = mb_strlen($value);
        if ($length === 0) {
            $this->add($field, "{$label} is required.");
        } elseif ($length < $min) {
            $this->add($field, "{$label} must be at least {$min} characters.");
        } elseif ($length > $max) {
            $this->add($field, "{$label} must be {$max} characters or fewer.");
        }
    }

    private function password(string $field, string $value): void
    {
        if ($value === '') {
            $this->add($field, 'Password is required.');
        } elseif (strlen($value) < self::PASSWORD_MIN) {
            $this->add($field, 'Password must be at least ' . self::PASSWORD_MIN . ' characters.');
        } elseif (strlen($value) > self::PASSWORD_MAX) {
            $this->add($field, 'Password must be ' . self::PASSWORD_MAX . ' characters or fewer.');
        } elseif (!preg_match('/[A-Za-z]/', $value) || !preg_match('/\d/', $value)) {
            $this->add($field, 'Password needs at least one letter and one number.');
        }
    }

    private function optionalWholeNumber(string $field, string $value, string $label, int $min, int $max): void
    {
        if ($value === '') {
            return;
        }
        if (!ctype_digit($value)) {
            $this->add($field, "{$label} must be a whole number.");
        } elseif ((int) $value < $min || (int) $value > $max) {
            $this->add($field, "{$label} must be between {$min} and {$max}.");
        }
    }

    /** Keep only the first error per field. */
    private function add(string $field, string $message): void
    {
        $this->errors[$field] ??= $message;
    }
}
