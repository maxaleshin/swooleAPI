<?php

namespace SwooleAPI\Core;

abstract class ServiceProvider
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Регистрация сервисов
     * 
     * @return void
     */
    abstract public function register(): void;

    /**
     * Получение контейнера зависимостей
     * 
     * @return Container
     */
    protected function getContainer(): Container
    {
        return $this->app->getContainer();
    }
}