<?php

namespace SwooleAPI\Database\Connections;

class MySqlConnection extends Connection
{
    /**
     * Подключение к базе данных MySQL
     */
    public function connect(array $config): self
    {
        $this->config = $config;

        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"
        ];
        
        // установока соединения
        $this->pdo = new \PDO($dsn, $config['username'], $config['password'], $options);
        
        return $this;
    }
}