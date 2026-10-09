# Kusina ng Barangay — Recipe Sharing Site

A members-only recipe site for the barangay’s home cooks, with a Filipino home-cooking theme (*lutong bahay*).
Built with plain PHP 8 (sessions, OOP, PDO), MySQL, and HTML/CSS/JavaScript. No frameworks or libraries.

## Setup

1. **Database.** Import `database/recipe_db.sql` in phpMyAdmin (Import tab), or run:
   `mysql -u root -p < database/recipe_db.sql`
   This creates `recipe_db` with the six tables, the starting categories and demo data.
2. **Connection.** Open `config/database.php` and set `HOST`, `PORT`, `USER` and `PASS` to match your MySQL.
   - Herd / XAMPP / Laragon default: user `root`, empty password.
3. **Serve the folder** through a local web server. Don't open the files directly (`file://` won't run PHP).
   - **Laravel Herd:** put the folder in `~/Herd/kusina`, then open `http://kusina.test`.
   - **XAMPP:** put it in `htdocs/kusina`, then open `http://localhost/kusina`.
   - **PHP built-in server:** run `php -S localhost:8000` inside the folder.
4. **Log in** with a demo account, or register a new one.

| Username | Email | Password |
|---|---|---|
| `aling_nena` | nena@barangay.test | `Lutong@2026` |
| `mang_tonyo` | tonyo@barangay.test | `Lutong@2026` |

Requirements: PHP 8.1+ with `pdo_mysql`, and MySQL 8.0.16+ or MariaDB 10.4+.

## Features

| Requirement | Where |
|---|---|
| Register, log in, log out; guests can’t reach member pages | `register.php`, `login.php`, `logout.php`; every member page calls `$session->requireLogin()` |
| Share a recipe (title, description, category, ingredients, steps) with “add more ingredient” fields | `recipe_create.php`, `includes/recipe_form.php`, `assets/js/ingredients.js` |
| Latest first, keyword search, category filter | `recipes.php` → `RecipeRepository::search()`. Search covers the title, description and ingredient names. |
| Recipe page with everything plus comments | `recipe.php` |
| Comment on recipes | `comment_store.php` |
| Save / remove favorites without reloading | `assets/js/favorites.js` → `api/favorite.php` (Fetch + JSON); list in `favorites.php` |
| Edit / delete only your own recipes and comments | `recipe_edit.php`, `recipe_delete.php`, `comment_update.php`, `comment_delete.php`. Every query includes `WHERE id = ? AND user_id = ?`. |
| Show that something was edited | `updated_at` is `NULL` until edited; recipes and comments then show an **Edited** badge |
| **Own feature: servings scaler** | On a recipe with a servings count, − / + rescales every numeric quantity (“1 1/2 cups” → “3 cups”). Text quantities like “to taste” stay as they are. `assets/js/servings.js` |

Extras: ingredient checklist on the recipe page, “My recipes” page, pagination, character counters, mobile menu.

## Non-negotiables

- **Hashed passwords.** Passwords are stored with `password_hash()` and checked with `password_verify()`. They are never stored as plain text.
- **One connection file.** `config/database.php` holds the only connection code. Pages include it through `includes/bootstrap.php`.
- **SQL injection.** Every query that uses input is a PDO prepared statement with bound values. Emulated prepares are off.
  - LIKE wildcards in searches are escaped.
- **Safe output.** Every value a user typed is printed through `e()` (`htmlspecialchars`). JavaScript only uses `textContent`.
- **Phone and desktop.** The CSS is mobile-first, with breakpoints at 600px and 860px. The nav collapses into a menu on phones, and ingredient rows stack.

Additional security:
- CSRF tokens on every form and on the favorites API.
- `session_regenerate_id()` on login.
- The session cookie is HttpOnly and SameSite=Lax.
- Logout only accepts POST.
- Login errors use the same message whether or not the account exists.

## Classes (`classes/`)

| Class | Responsibility |
|---|---|
| `Database` (`config/database.php`) | Creates the single shared PDO connection |
| `SessionManager` | Session start, login/logout, guards, flash messages, CSRF tokens |
| `Validator` | Server-side rules for registration, login, recipes and comments |
| `UserRepository` | SQL for `users` |
| `CategoryRepository` | SQL for `categories` |
| `RecipeRepository` | SQL for `recipes` and `ingredients`. Create and update run in a transaction. |
| `CommentRepository` | SQL for `comments`, with ownership checks |
| `FavoriteRepository` | SQL for `favorites` |

## Validation rules

The same rules run in the browser (`assets/js/validate.js`, read from the HTML attributes) and on the server (`Validator`).

| Field | Rule |
|---|---|
| Username | 3–30 characters; letters, numbers, `_`; unique |
| Display name | 2–60 characters |
| Email | Valid format; unique |
| Password | 8–72 characters, at least one letter and one number; must match the confirmation |
| Title | 3–120 characters |
| Description | 10–500 characters |
| Category | Must be one of the listed categories |
| Ingredients | 1–50 rows; every row needs a name (≤150 characters); quantity optional (≤50 characters) |
| Steps | 10–5000 characters; one step per line |
| Servings / cooking time | Optional whole numbers, 1–50 / 1–1440 minutes |
| Comment | 2–1000 characters |

## Folder structure

```
kusina/
├── config/database.php        ← the one DB connection file
├── classes/                   ← OOP classes (autoloaded)
├── includes/                  ← bootstrap, helpers, header/footer, shared partials
├── api/favorite.php           ← JSON endpoint for favorites
├── assets/css/style.css
├── assets/js/                 ← ui, validate, ingredients, favorites, servings
├── database/recipe_db.sql
└── *.php                      ← pages
```
