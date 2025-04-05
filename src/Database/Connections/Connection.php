<?php

namespace SwooleAPI\Database\Connections;

use SwooleAPI\Database\QueryBuilder;

abstract class Connection
{
    protected \PDO $pdo;
    protected array $config;

    /**
     * Подключение к базе данных
     */
    abstract public function connect(array $config): self;

    /**
     * Отключение от базы данных
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }

    /**
     * Получение экземпляра PDO
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Получение конфигурации подключения
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Запуск SQL-запроса и получение результата
     */
    public function query(string $sql, array $bindings = []): \PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($bindings);
        
        return $statement;
    }

    /**
     * Выполнение SQL-запроса без получения результата
     */
    public function execute(string $sql, array $bindings = []): bool
    {
        return $this->pdo->prepare($sql)->execute($bindings);
    }

    /**
     * Начало транзакции
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Подтверждение транзакции
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Откат транзакции
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Проверка, находится ли соединение в транзакции
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Получение последнего вставленного ID
     */
    public function lastInsertId(string $name = null): string
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * Получение строк из базы данных
     */
    public function select(string $query, array $bindings = []): array
    {
        $statement = $this->query($query, $bindings);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Получение одной строки из базы данных
     */
    public function selectOne(string $query, array $bindings = []): ?array
    {
        $records = $this->select($query, $bindings);
        return $records[0] ?? null;
    }

    /**
     * Вставка данных в таблицу
     */
    public function insert(string $table, array $values): bool
    {
        // Формируем SQL-запрос для вставки
        $columns = implode(', ', array_keys($values));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        return $this->execute($sql, array_values($values));
    }

    /**
     * Обновление данных в таблице
     */
    public function update(string $table, array $values, string $condition, array $bindings = []): bool
    {
        // Формируем SQL-запрос для обновления
        $setParts = [];
        foreach (array_keys($values) as $column) {
            $setParts[] = "{$column} = ?";
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE {$condition}";
        
        // Объединяем параметры для SET и WHERE
        $bindings = array_merge(array_values($values), $bindings);
        
        return $this->execute($sql, $bindings);
    }

    /**
     * Удаление данных из таблицы
     */
    public function delete(string $table, string $condition, array $bindings = []): bool
    {
        $sql = "DELETE FROM {$table} WHERE {$condition}";
        
        return $this->execute($sql, $bindings);
    }

    /**
     * Создание построителя запросов для указанной таблицы
     */
    public function table(string $table): QueryBuilder
    {
        return new QueryBuilder($this, $table);
    }
}