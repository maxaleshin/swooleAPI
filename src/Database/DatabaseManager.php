<?php

namespace SwooleAPI\Database;

use SwooleAPI\Core\Config;
use SwooleAPI\Database\Connections\Connection;
use SwooleAPI\Database\Connections\MySqlConnection;
use SwooleAPI\Database\Connections\PgSqlConnection;

class DatabaseManager
{
    private Config $config;
    private array $connections = [];

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Получение подключения к базе данных
     */
    public function connection(string $name = null): Connection
    {
        $name = $name ?: $this->getDefaultConnection();

        // Возвращаем существующее подключение, если оно есть
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        // Создаем новое подключение
        $this->connections[$name] = $this->makeConnection($name);

        return $this->connections[$name];
    }

    /**
     * Создание нового подключения
     */
    protected function makeConnection(string $name): Connection
    {
        $config = $this->getConnectionConfig($name);

        if (!$config) {
            throw new \RuntimeException("Database connection [{$name}] not configured.");
        }

        // Создаем соответствующее подключение в зависимости от драйвера
        return $this->createConnector($config['driver'])->connect($config);
    }

    /**
     * Создание коннектора в зависимости от драйвера
     */
    protected function createConnector(string $driver): Connection
    {
        switch ($driver) {
            case 'mysql':
                return new MySqlConnection();
            case 'pgsql':
                return new PgSqlConnection();
            default:
                throw new \InvalidArgumentException("Unsupported driver [{$driver}].");
        }
    }

    /**
     * Получение конфигурации подключения
     */
    protected function getConnectionConfig(string $name): ?array
    {
        return $this->config->get("database.connections.{$name}");
    }

    /**
     * Получение имени подключения по умолчанию
     */
    protected function getDefaultConnection(): string
    {
        return $this->config->get('database.default', 'mysql');
    }

    /**
     * Установка подключения по умолчанию
     */
    public function setDefaultConnection(string $name): void
    {
        $this->config->set('database.default', $name);
    }

    /**
     * Закрытие всех активных подключений
     */
    public function disconnect(string $name = null): void
    {
        if (is_null($name)) {
            // Закрываем все подключения
            foreach (array_keys($this->connections) as $connectionName) {
                $this->disconnect($connectionName);
            }
        } elseif (isset($this->connections[$name])) {
            // Закрываем указанное подключение
            $this->connections[$name]->disconnect();
            unset($this->connections[$name]);
        }
    }

    /**
     * Получение экземпляра QueryBuilder для указанного подключения
     */
    public function table(string $table, string $connection = null): QueryBuilder
    {
        return $this->connection($connection)->table($table);
    }

    /**
     * Магический метод для быстрого доступа к методам активного подключения
     */
    public function __call(string $method, array $parameters)
    {
        return $this->connection()->$method(...$parameters);
    }
}