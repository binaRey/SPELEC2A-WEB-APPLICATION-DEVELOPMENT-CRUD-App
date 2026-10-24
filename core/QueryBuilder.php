<?php
declare(strict_types=1);

namespace Core;

use Closure;
use InvalidArgumentException;
use PDO;

/**
 * Fluent, injection-safe SELECT builder used by BaseModel::query().
 *
 *   User::query()
 *       ->where('email', 'LIKE', '%@example.com')
 *       ->orderBy('name')
 *       ->paginate(10, 2);
 *
 * Values are always bound as named PDO parameters. Column names are validated
 * and backtick-quoted, operators and sort directions come from whitelists.
 *
 * Note: conditions combine left-to-right. Use whereAny() when you need
 * a parenthesised group like (name LIKE ? OR email LIKE ?).
 */
final class QueryBuilder
{
    private const OPERATORS = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE'];

    /** @var array<int, array{0:string,1:string}> [boolean, sql fragment] */
    private array $wheres = [];
    /** @var array<string, mixed> placeholder => value */
    private array $params = [];
    /** @var string[] */
    private array $orders = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private int $counter = 0;

    /**
     * @param string  $table    already-quoted table name
     * @param Closure $hydrator turns a DB row into a model instance
     */
    public function __construct(
        private string $table,
        private PDO $pdo,
        private Closure $hydrator
    ) {}

    /** Validate an identifier and wrap it in backticks. */
    public static function quote(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Invalid identifier: {$name}");
        }
        return '`' . $name . '`';
    }

    /* ------------------------------ conditions ------------------------------ */

    public function where(string $column, string $operator, mixed $value): static
    {
        $this->wheres[] = ['AND', $this->condition($column, $operator, $value)];
        return $this;
    }

    public function orWhere(string $column, string $operator, mixed $value): static
    {
        $this->wheres[] = ['OR', $this->condition($column, $operator, $value)];
        return $this;
    }

    /** AND ( col1 OP ? OR col2 OP ? ... ) : handy for search boxes. */
    public function whereAny(array $columns, string $operator, mixed $value): static
    {
        if ($columns === []) {
            throw new InvalidArgumentException('whereAny() needs at least one column.');
        }

        $parts = array_map(fn(string $c) => $this->condition($c, $operator, $value), $columns);
        $this->wheres[] = ['AND', '(' . implode(' OR ', $parts) . ')'];
        return $this;
    }

    /* ------------------------- ordering and limiting ------------------------- */

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = self::quote($column) . ' ' . $dir;
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = max(0, $limit);
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = max(0, $offset);
        return $this;
    }

    /* ------------------------------- executing ------------------------------- */

    /** @return array<int, object> hydrated models */
    public function get(): array
    {
        $sql = 'SELECT * FROM ' . $this->table
             . $this->compileWhere()
             . $this->compileOrder()
             . $this->compileLimit();

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);

        return array_map($this->hydrator, $stmt->fetchAll());
    }

    public function first(): ?object
    {
        $query = clone $this;
        $query->limit = 1;
        return $query->get()[0] ?? null;
    }

    public function count(): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->table . $this->compileWhere();

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{items: array, total: int, per_page: int, current_page: int, last_page: int}
     */
    public function paginate(int $perPage = 10, int $page = 1): array
    {
        $perPage = max(1, $perPage);
        $total   = $this->count();
        $last    = max(1, (int) ceil($total / $perPage));
        $page    = min(max(1, $page), $last);

        $query = clone $this;
        $query->limit($perPage)->offset(($page - 1) * $perPage);

        return [
            'items'        => $query->get(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => $last,
        ];
    }

    /* -------------------------------- internals ------------------------------- */

    /** Build "`col` OP :wN" and register the bound value. */
    private function condition(string $column, string $operator, mixed $value): string
    {
        $operator = strtoupper(trim($operator));
        if (!in_array($operator, self::OPERATORS, true)) {
            throw new InvalidArgumentException("Operator not allowed: {$operator}");
        }

        $placeholder = ':w' . $this->counter++;
        $this->params[$placeholder] = $value;

        return self::quote($column) . ' ' . $operator . ' ' . $placeholder;
    }

    private function compileWhere(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        $sql = '';
        foreach ($this->wheres as $i => [$boolean, $fragment]) {
            $sql .= ($i === 0 ? '' : " {$boolean} ") . $fragment;
        }
        return ' WHERE ' . $sql;
    }

    private function compileOrder(): string
    {
        return $this->orders === [] ? '' : ' ORDER BY ' . implode(', ', $this->orders);
    }

    private function compileLimit(): string
    {
        $sql = '';
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        return $sql;
    }
}
