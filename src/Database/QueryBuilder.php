<?php

namespace SwooleAPI\Database;

use SwooleAPI\Database\Connections\Connection;

class QueryBuilder
{
    protected Connection $connection;
    protected string $table;
    protected array $columns = ['*'];
    protected array $wheres = [];
    protected array $bindings = [];
    protected ?string $orderBy = null;
    protected ?string $groupBy = null;
    protected ?string $having = null;
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $joins = [];

    public function __construct(Connection $connection, string $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Выбор колонок
     */
    public function select($columns = ['*']): self
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Добавление условия WHERE
     */
    public function where(string $column, $operator = null, $value = null): self
    {
        // Обработка случая, когда передано только два аргумента
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'AND'
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Добавление условия WHERE с оператором OR
     */
    public function orWhere(string $column, $operator = null, $value = null): self
    {
        // Обработка случая, когда передано только два аргумента
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'OR'
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Добавление условия WHERE IN
     */
    public function whereIn(string $column, array $values): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Добавление условия WHERE NULL
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND'
        ];

        return $this;
    }

    /**
     * Сортировка результатов
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy = "{$column} " . strtoupper($direction);
        return $this;
    }

    /**
     * Группировка результатов
     */
    public function groupBy(string $column): self
    {
        $this->groupBy = $column;
        return $this;
    }

    /**
     * Добавление условия HAVING
     */
    public function having(string $column, $operator, $value): self
    {
        $this->having = "{$column} {$operator} ?";
        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Ограничение количества результатов
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Пропуск указанного количества результатов
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Добавление JOIN
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = [
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'type' => $type
        ];

        return $this;
    }

    /**
     * Добавление LEFT JOIN
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Добавление RIGHT JOIN
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Получение SQL-запроса для SELECT
     */
    protected function buildSelectQuery(): string
    {
        // Формируем базовый запрос
        $query = "SELECT " . $this->buildColumns() . " FROM {$this->table}";

        // Добавляем JOIN, если есть
        if (!empty($this->joins)) {
            $query .= ' ' . $this->buildJoins();
        }

        // Добавляем WHERE, если есть
        if (!empty($this->wheres)) {
            $query .= ' ' . $this->buildWheres();
        }

        // Добавляем GROUP BY, если задан
        if ($this->groupBy !== null) {
            $query .= " GROUP BY {$this->groupBy}";
        }

        // Добавляем HAVING, если задан
        if ($this->having !== null) {
            $query .= " HAVING {$this->having}";
        }

        // Добавляем ORDER BY, если задан
        if ($this->orderBy !== null) {
            $query .= " ORDER BY {$this->orderBy}";
        }

        // Добавляем LIMIT, если задан
        if ($this->limit !== null) {
            $query .= " LIMIT {$this->limit}";
        }

        // Добавляем OFFSET, если задан
        if ($this->offset !== null) {
            $query .= " OFFSET {$this->offset}";
        }

        return $query;
    }

    /**
     * Формирование строки колонок
     */
    protected function buildColumns(): string
    {
        return implode(', ', $this->columns);
    }

    /**
     * Формирование строки JOIN
     */
    protected function buildJoins(): string
    {
        $sql = '';

        foreach ($this->joins as $join) {
            $sql .= "{$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']} ";
        }

        return rtrim($sql);
    }

    /**
     * Формирование строки WHERE
     */
    protected function buildWheres(): string
    {
        $sql = 'WHERE ';
        $first = true;

        foreach ($this->wheres as $where) {
            // Пропускаем оператор для первого условия
            if (!$first) {
                $sql .= " {$where['boolean']} ";
            }

            // Формируем условие в зависимости от типа
            switch ($where['type']) {
                case 'basic':
                    $sql .= "{$where['column']} {$where['operator']} ?";
                    break;
                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $sql .= "{$where['column']} IN ({$placeholders})";
                    break;
                case 'null':
                    $sql .= "{$where['column']} IS NULL";
                    break;
            }

            $first = false;
        }

        return $sql;
    }

    /**
     * Получение всех результатов
     */
    public function get(): array
    {
        $query = $this->buildSelectQuery();
        return $this->connection->select($query, $this->bindings);
    }

    /**
     * Получение первого результата
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        
        return $results[0] ?? null;
    }

    /**
     * Подсчет количества результатов
     */
    public function count(string $column = '*'): int
    {
        $this->columns = ["COUNT({$column}) as count"];
        $result = $this->first();
        
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Вставка данных
     */
    public function insert(array $values): bool
    {
        return $this->connection->insert($this->table, $values);
    }

    /**
     * Обновление данных
     */
    public function update(array $values): bool
    {
        $whereClause = $this->buildWheres();
        return $this->connection->update($this->table, $values, $whereClause, $this->bindings);
    }

    /**
     * Удаление данных
     */
    public function delete(): bool
    {
        $whereClause = $this->buildWheres();
        return $this->connection->delete($this->table, $whereClause, $this->bindings);
    }
}