# PHP CRUD App (Core PHP + Custom ORM + PSR-4)

CRUD web app built with **PHP 8+**, **MySQL/MariaDB** and **PDO**, with no frameworks.
Composer is used **only** for PSR-4 autoloading.

## Requirements
- PHP 8.0+ with `pdo_mysql`
- MySQL 5.7+ / MariaDB 10.3+
- Composer

## Setup
```bash
# 1. Generate the autoloader (creates vendor/autoload.php)
composer install

# 2. Create the database and table
mysql -u root -p < schema.sql

# 3. Set credentials: edit config/database.php, or use env vars
export DB_HOST=127.0.0.1 DB_NAME=php_crud_app DB_USER=root DB_PASS=secret

# 4. Run with PHP's built-in server (document root = public/)
php -S localhost:8000 -t public public/index.php
```
Open http://localhost:8000

**Apache/XAMPP:** point the DocumentRoot to `public/` and enable `mod_rewrite` (the included `.htaccess` handles routing).

## Routes
| Method | Path | Action |
|---|---|---|
| GET | /users | List |
| GET | /users/create | New form |
| POST | /users | Create |
| GET | /users/{id}/edit | Edit form |
| POST | /users/{id}/update | Update |
| POST | /users/{id}/delete | Delete |

## Security
Real PDO prepared statements, ORM identifier/operator whitelisting, output escaping via `e()`, CSRF tokens on every POST, server-side validation, and errors logged instead of displayed.

Design notes: see `docs/ORM_AND_PSR4.md`.
