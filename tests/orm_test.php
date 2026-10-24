<?php
/**
 * ORM smoke test. Run from the project root:   php tests/orm_test.php
 * Uses the real database from config/database.php, creates temporary rows
 * tagged with a random prefix and removes them at the end.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Models\User;

$passed = 0;
$failed = 0;

function check(string $label, bool $ok): void
{
    global $passed, $failed;
    $ok ? $passed++ : $failed++;
    echo ($ok ? '  [PASS] ' : '  [FAIL] ') . $label . PHP_EOL;
}

function throws(callable $fn, string $class): bool
{
    try {
        $fn();
    } catch (Throwable $e) {
        return $e instanceof $class;
    }
    return false;
}

$tag = 'ormtest' . bin2hex(random_bytes(3));
echo "ORM smoke test (tag: {$tag})" . PHP_EOL;

try {
    // CREATE
    $a = User::create(['name' => 'Test A', 'email' => "{$tag}-a@example.com", 'phone' => '']);
    $b = User::create(['name' => 'Test B', 'email' => "{$tag}-b@example.com", 'phone' => '']);
    check('create() assigns an id and timestamps', $a->id !== null && $a->created_at !== null);

    // READ
    check('find() returns the model', User::find($a->id)?->email === "{$tag}-a@example.com");
    check('find() returns null for a missing id', User::find(0) === null);
    check('where() equality', count(User::where('email', '=', "{$tag}-a@example.com")) === 1);
    check('firstWhere() returns a model', User::firstWhere('email', '=', "{$tag}-b@example.com")?->name === 'Test B');
    check('firstWhere() returns null when nothing matches', User::firstWhere('email', '=', "{$tag}-none@example.com") === null);

    // Query builder
    $like  = "{$tag}%";
    $rows  = User::query()->where('email', 'LIKE', $like)->orderBy('name', 'DESC')->get();
    check('query(): where + orderBy DESC', count($rows) === 2 && $rows[0]->name === 'Test B');
    check('query(): count()', User::query()->where('email', 'LIKE', $like)->count() === 2);
    check('query(): whereAny() group', User::query()->whereAny(['name', 'email'], 'LIKE', "%{$tag}%")->count() === 2);
    check('query(): first()', User::query()->where('email', 'LIKE', $like)->orderBy('name')->first()?->name === 'Test A');

    $page = User::query()->where('email', 'LIKE', $like)->orderBy('name')->paginate(1, 2);
    check('paginate(): items/total/last_page', count($page['items']) === 1 && $page['total'] === 2 && $page['last_page'] === 2);
    check('paginate(): page is clamped to last page', User::query()->where('email', 'LIKE', $like)->paginate(1, 99)['current_page'] === 2);
    check('count() on the model', User::count() >= 2);

    // UPDATE
    $a->update(['name' => 'Renamed']);
    check('update() persists changes', User::find($a->id)?->name === 'Renamed');

    // Mass assignment protection
    $guard = new User(['id' => 999, 'name' => 'x']);
    check('fillable blocks mass assignment of id', $guard->id === null);

    // Injection guards
    check('invalid operator is rejected', throws(fn() => User::where('email', '; DROP TABLE users', 'x'), InvalidArgumentException::class));
    check('invalid column name is rejected', throws(fn() => User::query()->orderBy('name; DROP TABLE users'), InvalidArgumentException::class));
    check('SQL in a value is treated as data', User::where('email', '=', "x' OR '1'='1") === []);

    // DELETE
    $id = $a->id;
    check('delete() returns true', $a->delete() === true);
    check('deleted row is gone', User::find($id) === null);
} finally {
    // Cleanup any leftovers from this run.
    foreach (User::query()->where('email', 'LIKE', "{$tag}%")->get() as $leftover) {
        $leftover->delete();
    }
}

echo PHP_EOL . "Result: {$passed} passed, {$failed} failed" . PHP_EOL;
exit($failed > 0 ? 1 : 0);
