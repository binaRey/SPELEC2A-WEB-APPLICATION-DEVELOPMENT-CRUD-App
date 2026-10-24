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
 * Supported: all, find, where, firstWhere, create, save, update, delete.
 * Every value goes through PDO prepared statements; identifiers are validated.
 */
abstract class BaseModel
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    protected static array $fillable = [];

    /** Allowed comparison operators for where(). */
    private const OPERATORS = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE'];

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
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Invalid identifier: {$name}");
        }
        return '`' . $name . '`';
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

    /** Hydrate many rows. */
    protected static function hydrateMany(array $rows): array
    {
        return array_map(static fn(array $row) => static::hydrate($row), $rows);
    }

    /** Keep only fillable keys. */
    protected static function onlyFillable(array $data): array
    {
        return array_intersect_key($data, array_flip(static::$fillable));
    }

    /* ------------------------------------------------------------------ *
     |  READ
     * ------------------------------------------------------------------ */

    /** @return static[] */
    public static function all(string $orderBy = '', string $direction = 'ASC'): array
    {
        $sql = 'SELECT * FROM ' . static::table();

        if ($orderBy !== '') {
            $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= ' ORDER BY ' . static::quoteIdentifier($orderBy) . ' ' . $dir;
        }

        return static::hydrateMany(static::pdo()->query($sql)->fetchAll());
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
        $operator = strtoupper($operator);
        if (!in_array($operator, self::OPERATORS, true)) {
            throw new InvalidArgumentException("Operator not allowed: {$operator}");
        }

        $sql = 'SELECT * FROM ' . static::table()
             . ' WHERE ' . static::quoteIdentifier($column) . " {$operator} :value";

        $stmt = static::pdo()->prepare($sql);
        $stmt->execute(['value' => $value]);

        return static::hydrateMany($stmt->fetchAll());
    }

    public static function firstWhere(string $column, string $operator, mixed $value): ?static
    {
        return static::where($column, $operator, $value)[0] ?? null;
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
