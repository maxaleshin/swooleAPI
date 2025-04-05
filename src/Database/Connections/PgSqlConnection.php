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

        // Формируем DSN
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        
        // Добавляем параметры в DSN, если они заданы
        if (isset($config['sslmode'])) {
            $dsn .= ";sslmode={$config['sslmode']}";
        }
        
        // Опции PDO
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        // Устанавливаем соединение
        $this->pdo = new \PDO($dsn, $config['username'], $config['password'], $options);
        
        // Устанавливаем схему поиска, если задана
        if (isset($config['schema'])) {
            $this->execute("SET search_path TO {$config['schema']}");
        }
        
        // Устанавливаем кодировку
        if (isset($config['charset'])) {
            $this->execute("SET client_encoding TO '{$config['charset']}'");
        }
        
        return $this;
    }
}