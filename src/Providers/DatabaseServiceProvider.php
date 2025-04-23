<?php

namespace SwooleAPI\Providers;

use SwooleAPI\Core\ServiceProvider;
use SwooleAPI\Database\DatabaseManager;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Регистрация сервисов
     */
    public function register(): void
    {
        // регистрируем менеджер баз данных как синглтон
        $this->getContainer()->singleton(DatabaseManager::class, function() {
            $config = $this->app->getContainer()->get('SwooleAPI\Core\Config');
            return new DatabaseManager($config);
        });

        // 
        $this->getContainer()->singleton('db', function() {
            return $this->getContainer()->get(DatabaseManager::class);
        });
    }
}