<?php
declare(strict_types=1);

namespace Core;

use InvalidArgumentException;
use PDO;

/**
 * Lightweight Active-Record style ORM.
 *
 * A child model maps itself to a table by declaring:
 *   protected static string $table      = 'users';
 *   protected static string $primaryKey = 'id';          (optional)
 *   protected static array  $fillable   = ['name', ...]; (mass-assignable columns)
 *
 * Reading:  all, find, where, firstWhere, count, query() (fluent builder)
 * Writing:  create, save, update, delete
 *
 * Every value goes through PDO prepared statements; identifiers are validated.
 */
abstract class BaseModel
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    protected static array $fillable = [];

    /** Column values of this record. */
    protected array $attributes = [];

    /** True when the record exists in the database. */
    protected bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /* ------------------------------------------------------------------ *
     |  Attribute handling
     * ------------------------------------------------------------------ */

    /** Mass-assign values (only $fillable columns are accepted). */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, static::$fillable, true)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        if (in_array($name, static::$fillable, true)) {
            $this->attributes[$name] = $value;
        }
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    /* ------------------------------------------------------------------ *
     |  Internals
     * ------------------------------------------------------------------ */

    protected static function pdo(): PDO
    {
        return Database::getConnection();
    }

    /** Validate a table/column name and wrap it in backticks. */
    protected static function quoteIdentifier(string $name): string
    {
        return QueryBuilder::quote($name);
    }

    protected static function table(): string
    {
        if (static::$table === '') {
            throw new InvalidArgumentException(static::class . ' must define a $table.');
        }
        return static::quoteIdentifier(static::$table);
    }

    /** Build a model instance from a DB row. */
    protected static function hydrate(array $row): static
    {
        $model = new static();
        $model->attributes = $row;
        $model->exists = true;
        return $model;
    }

    /** Keep only fillable keys. */
    protected static function onlyFillable(array $data): array
    {
        return array_intersect_key($data, array_flip(static::$fillable));
    }

    /* ------------------------------------------------------------------ *
     |  READ
     * ------------------------------------------------------------------ */

    /** Start a fluent query: Model::query()->where(...)->orderBy(...)->get() */
    public static function query(): QueryBuilder
    {
        return new QueryBuilder(
            static::table(),
            static::pdo(),
            static fn(array $row) => static::hydrate($row)
        );
    }

    /** @return static[] */
    public static function all(string $orderBy = '', string $direction = 'ASC'): array
    {
        $query = static::query();
        if ($orderBy !== '') {
            $query->orderBy($orderBy, $direction);
        }
        return $query->get();
    }

    public static function find(int|string $id): ?static
    {
        $sql = 'SELECT * FROM ' . static::table()
             . ' WHERE ' . static::quoteIdentifier(static::$primaryKey) . ' = :id LIMIT 1';

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? static::hydrate($row) : null;
    }

    /**
     * Basic where query: User::where('email', '=', 'a@b.com')
     * @return static[]
     */
    public static function where(string $column, string $operator, mixed $value): array
    {
        return static::query()->where($column, $operator, $value)->get();
    }

    public static function firstWhere(string $column, string $operator, mixed $value): ?static
    {
        return static::query()->where($column, $operator, $value)->first();
    }

    /** Number of rows in the table. */
    public static function count(): int
    {
        return static::query()->count();
    }

    /* ------------------------------------------------------------------ *
     |  CREATE
     * ------------------------------------------------------------------ */

    public static function create(array $data): static
    {
        $model = new static($data);
        $model->save();
        return $model;
    }

    /** INSERT when new, UPDATE when it already exists. */
    public function save(): bool
    {
        return $this->exists ? $this->performUpdate() : $this->performInsert();
    }

    private function performInsert(): bool
    {
        $data = static::onlyFillable($this->attributes);
        if ($data === []) {
            throw new InvalidArgumentException('No fillable attributes to insert.');
        }

        $columns      = array_map(static fn(string $c) => static::quoteIdentifier($c), array_keys($data));
        $placeholders = array_map(static fn(string $c) => ':' . $c, array_keys($data));

        $sql = 'INSERT INTO ' . static::table()
             . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute($data);

        $id = static::pdo()->lastInsertId();
        // Reload so DB defaults (id, timestamps) are present.
        $fresh = static::find($id);
        if ($fresh) {
            $this->attributes = $fresh->attributes;
        }
        $this->exists = true;

        return true;
    }

    /* ------------------------------------------------------------------ *
     |  UPDATE
     * ------------------------------------------------------------------ */

    /** Fill with new data and persist: $user->update(['name' => 'X']) */
    public function update(array $data): bool
    {
        $this->fill($data);
        return $this->save();
    }

    private function performUpdate(): bool
    {
        $data = static::onlyFillable($this->attributes);
        if ($data === []) {
            return true;
        }

        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = static::quoteIdentifier($column) . ' = :' . $column;
        }

        $sql = 'UPDATE ' . static::table() . ' SET ' . implode(', ', $sets)
             . ' WHERE ' . static::quoteIdentifier(static::$primaryKey) . ' = :pk_value';

        $params = $data + ['pk_value' => $this->attributes[static::$primaryKey]];

        return static::pdo()->prepare($sql)->execute($params);
    }

    /* ------------------------------------------------------------------ *
     |  DELETE
     * ------------------------------------------------------------------ */

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $sql = 'DELETE FROM ' . static::table()
             . ' WHERE ' . static::quoteIdentifier(static::$primaryKey) . ' = :id';

        $ok = static::pdo()->prepare($sql)->execute(['id' => $this->attributes[static::$primaryKey]]);
        $this->exists = !$ok;

        return $ok;
    }
}
