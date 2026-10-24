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

### Fluent query builder (`core/QueryBuilder.php`)
`Model::query()` returns a chainable builder, so models can run richer queries without raw SQL:
```php
User::query()
    ->whereAny(['name', 'email'], 'LIKE', '%maria%')   // (name LIKE ? OR email LIKE ?)
    ->where('created_at', '>=', '2026-01-01')
    ->orderBy('name')
    ->paginate(10, 2);   // items, total, per_page, current_page, last_page
```
| Builder method | Purpose |
|---|---|
| `where / orWhere` | Add a condition (operator whitelist) |
| `whereAny([cols], op, value)` | Parenthesised OR group, used for search |
| `orderBy(col, dir)` | Sort (identifier validated, direction whitelisted) |
| `limit / offset` | Paging primitives |
| `get / first / count` | Execute the query |
| `paginate(perPage, page)` | Count plus slice, with the page clamped to the valid range |

`Model::all()`, `where()`, `firstWhere()` and `count()` are thin wrappers over this builder.

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

## Testing
`php tests/orm_test.php` exercises create, find, where, query builder, pagination, update, delete, mass-assignment protection and injection guards against the real database, then cleans up after itself.

## Request flow
`public/index.php` -> route table -> `UserController` action -> validation -> `User` model (ORM) -> `View::render()` inside `views/layout.php`.

## Security notes
- **SQL injection:** values are always bound; identifiers are validated by regex and backtick-quoted; operators and directions are whitelisted. `tests/orm_test.php` includes injection checks.
- **Mass assignment:** only `$fillable` columns can be set from request data.
- **XSS:** every dynamic value in a view goes through `e()`. A strict Content-Security-Policy forbids inline scripts.
- **CSRF:** all state-changing routes are POST-only and require a session token compared with `hash_equals`.
- **Errors:** exceptions are logged; visitors only see generic messages.
