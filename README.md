<<<<<<< HEAD
# PHP CRUD App (Core PHP + Custom ORM + PSR-4)

A CRUD web application built with **PHP 8+**, **MySQL/MariaDB** and **PDO**, with **no frameworks**.
It includes a hand-written lightweight ORM (with a fluent query builder) and PSR-4 autoloading.
Composer is used **only** for autoloading.

## Features
- Create, read, update and delete users
- Custom ORM: model mapping, `all`, `find`, `where`, `create`, `update`, `delete`, plus `query()` builder (`orderBy`, `whereAny`, `count`, `paginate`)
- Search, sorting and pagination on the list page
- Server-side validation, CSRF protection, escaped output, friendly error pages
- Responsive, accessible UI with automatic dark mode

## Project structure
```
my-php-crud-app/
├── app/
│   ├── Controllers/UserController.php   # CRUD actions, validation
│   └── Models/User.php                  # maps to the `users` table
├── core/
│   ├── BaseModel.php                    # the ORM
│   ├── QueryBuilder.php                 # fluent query builder used by the ORM
│   ├── Database.php                     # PDO singleton
│   ├── View.php                         # view renderer
│   └── helpers.php                      # e(), csrf_*, flash(), redirect(), asset()
├── config/database.php                  # DB settings
├── public/                              # web root: index.php, .htaccess, assets/
├── views/                               # layout, users/*, errors/*
├── tests/orm_test.php                   # ORM smoke tests
├── docs/ORM_AND_PSR4.md                 # design documentation
├── composer.json
└── schema.sql
```

## Requirements
- PHP 8.0+ with `pdo_mysql` and `mbstring`
- MySQL 5.7+ or MariaDB 10.3+
- Composer

## Setup
1. **Install the autoloader** (creates `vendor/`):
   ```bash
   composer install
   ```
2. **Create the database and table:**
   ```bash
   mysql -u root -p < schema.sql
   ```
3. **Configure the connection** in `config/database.php`, or with environment variables
   `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
   Do not commit real passwords; prefer the environment variables.
4. **Run it** with either option below.

### Option A: PHP built-in server (quickest)
```bash
php -S localhost:8000 -t public public/index.php
```
Open http://localhost:8000 (redirects to `/users`).

### Option B: Apache on Windows (example: `http://localhost:8080`)
In `httpd.conf`:
```apache
Listen 8080
ServerName localhost:8080
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule php_module "C:/php/php8apache2_4.dll"   # Thread Safe PHP build
AddHandler application/x-httpd-php .php
PHPIniDir "C:/php"
DirectoryIndex index.php index.html

DocumentRoot "C:/path/to/my-php-crud-app/public"
<Directory "C:/path/to/my-php-crud-app/public">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```
The `DocumentRoot` must be the **`public`** folder, and `AllowOverride All` is required for `.htaccess`.
Check the config with `httpd.exe -t`, then restart Apache.
(Optional modules `mod_deflate` and `mod_expires` enable compression and caching.)

## Tests
```bash
php tests/orm_test.php
```
Exercises the ORM against your real database and cleans up after itself. Every line should print `[PASS]`.

## Routes
| Method | Path | Action |
|---|---|---|
| GET | /users | List (`?q=`, `?sort=name\|email\|created_at`, `?dir=asc\|desc`, `?page=`) |
| GET | /users/create | New form |
| POST | /users | Create |
| GET | /users/{id}/edit | Edit form |
| POST | /users/{id}/update | Update |
| POST | /users/{id}/delete | Delete |

## Security
- Real PDO prepared statements; ORM validates identifiers and whitelists operators and sort directions
- Mass-assignment protection via `$fillable`
- Output escaped with `e()` (XSS), CSRF token on every POST
- Strict Content-Security-Policy and other security headers; hardened session cookies
- Errors are logged server-side and never shown to visitors

## Accessibility and SEO
Skip link, landmarks, labelled form fields, `aria-invalid`/`aria-describedby` on errors, error summary that receives focus, keyboard-operable menu and dialog, visible focus rings, reduced-motion support, 44px touch targets on touch devices. Pages have titles, meta descriptions and Open Graph tags. The app ships with `noindex` because it is a private tool; remove that meta tag in `views/layout.php` for a public site.

## Troubleshooting
| Symptom | Fix |
|---|---|
| "Dependencies missing" or class not found | Run `composer install` (or `composer dump-autoload`) |
| 500 page | Read the PHP/Apache error log; the real cause is logged there |
| `Access denied for user` | Check the password in `config/database.php` |
| `Unknown database` | Import `schema.sql` |
| Unstyled page on Apache | DocumentRoot is not the `public` folder, or `AllowOverride` is not `All` |

## Requirements coverage (project specification)
| Requirement | Where |
|---|---|
| PHP 8+, MySQL/MariaDB, no frameworks | whole project |
| Composer only for PSR-4 | `composer.json` |
| Create, Read, Update, Delete | `UserController`, `views/users/*` |
| Own lightweight ORM (mapping, find, all, create, update, delete, where) | `core/BaseModel.php`, `core/QueryBuilder.php` |
| PSR-4 namespaces | `App\` -> `app/`, `Core\` -> `core/` |
| Separated config, models, controllers, views, core | folder structure above |
| PDO prepared statements | `core/Database.php`, ORM |
| Input validation and graceful errors | `UserController`, `views/errors` |
| Deliverables: source, SQL schema, README, ORM/PSR-4 docs | all files, `schema.sql`, `README.md`, `docs/` |
=======
# SPELEC2A-WEB-APPLICATION-DEVELOPMENT-CRUD-App
>>>>>>> 391c7a4a0c15f97c73aba85da87c9706bef1f947
