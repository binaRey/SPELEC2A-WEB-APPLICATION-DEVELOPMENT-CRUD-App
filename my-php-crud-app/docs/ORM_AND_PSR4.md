# ORM Design & PSR-4 Implementation

## ORM design (`core/BaseModel.php`)
An **Active Record** style mapper: each model class maps to one table, each instance to one row.

```php
final class User extends BaseModel {
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';
    protected static array  $fillable = ['name', 'email', 'phone'];
}
```
`$fillable` is a mass-assignment whitelist, so unexpected POST fields never reach SQL.

| Method | Purpose |
|---|---|
| `Model::all($orderBy, $dir)` | SELECT all rows, returns array of models |
| `Model::find($id)` | SELECT by primary key, returns model or `null` |
| `Model::where($col, $op, $val)` | Basic filtered query (operator whitelist) |
| `Model::firstWhere(...)` | First match or `null` |
| `Model::create($data)` | INSERT, then reload row (gets id and timestamps) |
| `$model->update($data)` / `->save()` | UPDATE (INSERT if new) |
| `$model->delete()` | DELETE by primary key |

**Safety:** values are bound through PDO prepared statements (`ATTR_EMULATE_PREPARES = false`). Table and column names cannot be bound, so they are validated against `^[A-Za-z_][A-Za-z0-9_]*$` and backtick-quoted. Operators and sort direction come from whitelists.

**Connection:** `core/Database.php` is a singleton PDO provider (exception mode, associative fetch). Failures are logged and replaced with a generic message.

## PSR-4 implementation
`composer.json`:
```json
"autoload": {
  "psr-4": { "App\\": "app/", "Core\\": "core/" },
  "files": ["core/helpers.php"]
}
```
| Namespace | Directory | Examples |
|---|---|---|
| `App\Controllers` | `app/Controllers` | `UserController.php` |
| `App\Models` | `app/Models` | `User.php` |
| `Core` | `core` | `Database.php`, `BaseModel.php`, `View.php` |

Namespace plus class name maps to the file path (`App\Models\User` becomes `app/Models/User.php`). `composer install` or `composer dump-autoload` generates `vendor/autoload.php`, which `public/index.php` requires once. `helpers.php` (plain functions) loads through Composer's `files` autoload.

## Request flow
`public/index.php` -> route table -> `UserController` action -> validation -> `User` model (ORM) -> `View::render()` inside `views/layout.php`.
