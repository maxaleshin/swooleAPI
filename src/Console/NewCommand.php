<?php

namespace SwooleAPI\Console;

class NewCommand extends Command
{
    protected string $name = 'new';
    protected string $description = 'Создание нового приложения SwooleAPI';
    
    protected array $arguments = [
        'path' => 'Путь для создания приложения (по умолчанию текущая директория)'
    ];
    
    protected array $options = [
        '--db' => 'Тип базы данных (pgsql или mysql)',
        '--db-name' => 'Имя базы данных',
        '--db-user' => 'Имя пользователя базы данных',
        '--db-pass' => 'Пароль пользователя базы данных',
        '--db-host' => 'Хост базы данных',
        '--port' => 'Порт для веб-сервера',
        '--force' => 'Принудительное создание, даже если директория не пуста'
    ];

    /**
     * Обработка команды
     */
    public function handle(array $args = [], array $options = []): int
    {
        $appPath = !empty($args[0]) ? $args[0] : '.';
        if ($appPath !== '.') {
            $appPath = getcwd() . '/' . ltrim($appPath, '/');
        } else {
            $appPath = getcwd();
        }
        
        $appName = basename($appPath);
        
        if (is_dir($appPath) && count(scandir($appPath)) > 2 && !isset($options['force'])) { // > 2 потому что "." и ".." всегда есть
            if (!$this->confirm("Директория '{$appPath}' не пуста. Продолжить и возможно перезаписать существующие файлы?", false)) {
                $this->info('Операция отменена.');
                return 0;
            }
        }
        $dbType = $options['db'] ?? $this->choice(
            'Выберите тип базы данных:', 
            ['pgsql' => 'PostgreSQL', 'mysql' => 'MySQL'], 
            'pgsql'
        );

        $dbName = $options['db-name'] ?? $this->ask('Имя базы данных:', strtolower(str_replace(' ', '_', $appName)));
        $dbUser = $options['db-user'] ?? $this->ask('Имя пользователя БД:', $dbType === 'pgsql' ? 'postgres' : 'root');
        $dbPass = $options['db-pass'] ?? $this->ask('Пароль пользователя БД:', '');
        $dbHost = $options['db-host'] ?? $this->ask('Хост базы данных:', 'localhost');
        $dbPort = $dbType === 'pgsql' ? '5432' : '3306';
        
        $serverPort = $options['port'] ?? $this->ask('Порт для веб-сервера:', '8080');
        $serverWorkers = $this->ask('Количество рабочих процессов:', (string)swoole_cpu_num());
        
        $appDisplayName = ucwords(str_replace(['-', '_'], ' ', $appName));

        // Настройки для генератора
        $scaffoldOptions = [
            'database' => $dbType,
            'db_host' => $dbHost,
            'db_port' => $dbPort,
            'db_name' => $dbName,
            'db_user' => $dbUser,
            'db_password' => $dbPass,
            'app_name' => $appDisplayName,
            'app_debug' => true,
            'server_port' => $serverPort,
            'server_workers' => (int)$serverWorkers
        ];

        $this->info('Создание структуры приложения...');

        $scaffold = new ScaffoldCommand($appPath, $scaffoldOptions);
        $result = $scaffold->generate();

        $this->success("Приложение '{$appDisplayName}' успешно создано!");
        $this->info("Создано директорий: " . count($result['directories']));
        $this->info("Создано файлов: " . count($result['files']));
        
        if ($appPath === getcwd()) {
            $this->info("\nДля запуска приложения выполните:");
            $this->write("composer install");
            $this->write("php public/index.php");
        } else {
            $this->info("\nДля запуска приложения выполните:");
            $this->write("cd " . basename($appPath));
            $this->write("composer install");
            $this->write("php public/index.php");
        }
        
        $this->info("\nПриложение будет доступно по адресу: http://localhost:{$serverPort}");

        return 0;
    }
}