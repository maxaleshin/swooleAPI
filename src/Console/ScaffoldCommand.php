<?php

namespace SwooleAPI\Console;

class ScaffoldCommand
{
    private string $basePath;
    private array $options;
    private array $createdDirectories = [];
    private array $createdFiles = [];

    public function __construct(string $basePath, array $options = [])
    {
        $this->basePath = rtrim($basePath, '/');
        $this->options = array_merge([
            'database' => 'pgsql',
            'db_host' => 'localhost',
            'db_port' => '5432',
            'db_name' => 'swoole_app',
            'db_user' => 'postgres',
            'db_password' => 'postgres',
            'app_name' => 'SwooleAPI Application',
            'app_debug' => true,
            'server_host' => 'localhost',
            'server_port' => 8080,
            'server_workers' => 4
        ], $options);
    }

    /**
     * Запуск генерации структуры проекта
     */
    public function generate(): array
    {
        $this->createDirectories();
        $this->createConfigFiles();
        $this->createPublicFiles();
        $this->createControllerFiles();
        $this->createModelFiles();

        return [
            'directories' => $this->createdDirectories,
            'files' => $this->createdFiles
        ];
    }

    /**
     * Создание директорий проекта
     */
    private function createDirectories(): void
    {
        $directories = [
            'app/Controllers',
            'app/Models',
            'app/Middleware',
            'config',
            'public',
            'resources/views',
            'storage/logs',
            'storage/uploads',
            'tests'
        ];

        foreach ($directories as $dir) {
            $path = $this->basePath . '/' . $dir;
            if (!is_dir($path) && mkdir($path, 0755, true)) {
                $this->createdDirectories[] = $path;
            }
        }
    }

    /**
     * Создание конфигурационных файлов
     */
    private function createConfigFiles(): void
    {
        // основной файл конфигурации
        $appConfig = $this->generateAppConfig();
        $this->writeFile('config/app.php', $appConfig);

        // файл конфигурации базы данных
        $dbConfig = $this->generateDatabaseConfig();
        $this->writeFile('config/database.php', $dbConfig);

        // файл конфигурации сервера
        $serverConfig = $this->generateServerConfig();
        $this->writeFile('config/server.php', $serverConfig);
    }

    /**
     * Создание файлов общего доступа
     */
    private function createPublicFiles(): void
    {
        // входной файл для веб-сервера
        $indexFile = $this->generateIndexFile();
        $this->writeFile('public/index.php', $indexFile);

        // файл .htaccess для Apache
        $htaccessFile = $this->generateHtaccessFile();
        $this->writeFile('public/.htaccess', $htaccessFile);
    }

    /**
     * Создание файлов контроллеров
     */
    private function createControllerFiles(): void
    {
        // пример контроллера
        $homeController = $this->generateHomeController();
        $this->writeFile('app/Controllers/HomeController.php', $homeController);
    }

    /**
     * Создание файлов моделей
     */
    private function createModelFiles(): void
    {
        // пример модели
        $baseModel = $this->generateExampleModel();
        $this->writeFile('app/Models/ExampleModel.php', $baseModel);
    }

    /**
     * Генерация основного конфигурационного файла
     */
    private function generateAppConfig(): string
    {
        return '<?php

return [
    // Базовые настройки приложения
    \'app\' => [
        \'name\' => \'' . $this->options['app_name'] . '\',
        \'debug\' => ' . ($this->options['app_debug'] ? 'true' : 'false') . ',
        \'url\' => \'http://' . $this->options['server_host'] . ':' . $this->options['server_port'] . '\',
        \'base_path\' => dirname(__DIR__),
        \'controllers_path\' => [
            dirname(__DIR__) . \'/app/Controllers\'
        ]
    ],
    
    // Подключение конфигурационных файлов
    \'database\' => __DIR__ . \'/database.php\',
    \'server\' => __DIR__ . \'/server.php\'
    
];
';
    }

    /**
     * Генерация конфигурационного файла для базы данных
     */
    private function generateDatabaseConfig(): string
    {
        return '<?php

return [
    // Настройки базы данных
    \'database\' => [
        \'default\' => \'' . $this->options['database'] . '\',
        \'connections\' => [
            \'pgsql\' => [
                \'driver\' => \'pgsql\',
                \'host\' => \'' . $this->options['db_host'] . '\',
                \'port\' => \'' . $this->options['db_port'] . '\',
                \'database\' => \'' . $this->options['db_name'] . '\',
                \'username\' => \'' . $this->options['db_user'] . '\',
                \'password\' => \'' . $this->options['db_password'] . '\',
                \'charset\' => \'utf8\',
                \'schema\' => \'public\'
            ],
            \'mysql\' => [
                \'driver\' => \'mysql\',
                \'host\' => \'' . $this->options['db_host'] . '\',
                \'port\' => \'3306\',
                \'database\' => \'' . $this->options['db_name'] . '\',
                \'username\' => \'root\',
                \'password\' => \'\',
                \'charset\' => \'utf8mb4\'
            ]
        ]
    ]
];
';
    }

    /**
     * Генерация конфигурационного файла для сервера
     */
    private function generateServerConfig(): string
    {
        return '<?php

return [
    // Настройки Swoole сервера
    \'server\' => [
        \'host\' => ' . $this->options['server_host'] . ',
        \'port\' => ' . $this->options['server_port'] . ',
        \'workers\' => ' . $this->options['server_workers'] . ',
        \'max_requests\' => 8000,
        \'enable_coroutine\' => true,
        \'log_level\' => SWOOLE_LOG_INFO,
        \'daemonize\' => false,
        \'pid_file\' => dirname(__DIR__) . \'/storage/swoole.pid\'
    ]
];
';
    }

    /**
     * Генерация входного файла для веб-сервера
     */
    private function generateIndexFile(): string
    {
        return '<?php

// Загрузка автозагрузчика Composer
require_once dirname(__DIR__) . \'/vendor/autoload.php\';

// Загружаем конфигурацию
$config = require_once dirname(__DIR__) . \'/config/app.php\';
$app = new SwooleAPI\Core\Application($config);
$app->register(SwooleAPI\Providers\DatabaseServiceProvider::class);

// Запускаем приложение
$app->run();
';
    }

    /**
     * Генерация файла .htaccess для Apache
     */
    private function generateHtaccessFile(): string
    {
        return '<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Перенаправление с www на без www
    # RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
    # RewriteRule ^(.*)$ http://%1/$1 [R=301,L]

    # Обработка Authorization header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Перенаправление всех запросов на index.php
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
';
    }

    /**
     * Генерация примера контроллера
     */
    private function generateHomeController(): string
    {
        return '<?php

namespace App\Controllers;

use SwooleAPI\Http\Request;
use SwooleAPI\Http\Response;
use SwooleAPI\Routing\Attributes\Get;

class HomeController
{
    /**
     * Главная страница
     */
    #[Get(\'/\')]
    public function index(Request $request, Response $response): array
    {
        return [
            \'message\' => \'Добро пожаловать в ' . $this->options['app_name'] . '!\',
            \'swoole_version\' => swoole_version(),
            \'php_version\' => PHP_VERSION,
            \'time\' => date(\'Y-m-d H:i:s\')
        ];
    }

    /**
     * Проверка статуса сервера
     */
    #[Get(\'/ping\')]
    public function ping(): array
    {
        return [
            \'status\' => \'ok\',
            \'message\' => \'pong\',
            \'timestamp\' => time()
        ];
    }
}
';
    }

    /**
     * Генерация примера модели
     */
    private function generateExampleModel(): string
    {
        return '<?php

namespace App\Models;

use SwooleAPI\Database\Model;

class ExampleModel extends Model
{
    /**
     * Имя таблицы в базе данных
     */
    protected string $table = \'examples\';

    /**
     * Атрибуты, которые можно заполнять
     */
    protected array $fillable = [
        \'name\',
        \'description\',
        \'status\'
    ];
    
    // По умолчанию метки времени отключаем
    protected bool $timestamps = false;
}
';
    }

    /**
     * Запись файла с созданием директории при необходимости
     */
    private function writeFile(string $path, string $content): bool
    {
        $fullPath = $this->basePath . '/' . $path;
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            $this->createdDirectories[] = $dir;
        }

        if (file_put_contents($fullPath, $content)) {
            $this->createdFiles[] = $fullPath;
            return true;
        }

        return false;
    }
}