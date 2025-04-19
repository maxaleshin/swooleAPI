<?php

namespace SwooleAPI\Database\Connections;

class PgSqlConnection extends Connection
{
    /**
     * Подключение к базе данных PostgreSQL
     */
    public function connect(array $config): self
    {
        $this->config = $config;

        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        if (isset($config['sslmode'])) {
            $dsn .= ";sslmode={$config['sslmode']}";
        }
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        // установка соединения
        $this->pdo = new \PDO($dsn, $config['username'], $config['password'], $options);
        
        // установка схемы поиска, если задана
        if (isset($config['schema'])) {
            $this->execute("SET search_path TO {$config['schema']}");
        }
        
        // установка кодировки
        if (isset($config['charset'])) {
            $this->execute("SET client_encoding TO '{$config['charset']}'");
        }
        
        return $this;
    }
}