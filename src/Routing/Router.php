<?php

namespace SwooleAPI\Routing;

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private string $prefix = '';
    private array $groups = [];

    /**
     * Добавление GET-маршрута
     */
    public function get(string $path, $handler, array $middlewares = []): Route
    {
        return $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Добавление POST-маршрута
     */
    public function post(string $path, $handler, array $middlewares = []): Route
    {
        return $this->addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Добавление PUT-маршрута
     */
    public function put(string $path, $handler, array $middlewares = []): Route
    {
        return $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    /**
     * Добавление DELETE-маршрута
     */
    public function delete(string $path, $handler, array $middlewares = []): Route
    {
        return $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    /**
     * Добавление PATCH-маршрута
     */
    public function patch(string $path, $handler, array $middlewares = []): Route
    {
        return $this->addRoute('PATCH', $path, $handler, $middlewares);
    }

    /**
     * Добавление OPTIONS-маршрута
     */
    public function options(string $path, $handler, array $middlewares = []): Route
    {
        return $this->addRoute('OPTIONS', $path, $handler, $middlewares);
    }

    /**
     * Добавление маршрута для любого HTTP-метода
     */
    public function any(string $path, $handler, array $middlewares = []): Route
    {
        return $this->addRoute('ANY', $path, $handler, $middlewares);
    }

    /**
     * Добавление маршрута для указанных HTTP-методов
     */
    public function map(array $methods, string $path, $handler, array $middlewares = []): Route
    {
        $route = null;
        foreach ($methods as $method) {
            $route = $this->addRoute($method, $path, $handler, $middlewares);
        }
        return $route;
    }

    /**
     * Добавление маршрута
     */
    private function addRoute(string $method, string $path, $handler, array $middlewares = []): Route
    {
        // Применяем префикс пути, если есть
        $path = $this->prefix . $path;
        
        // Создаем объект маршрута
        $route = new Route($method, $path, $handler);
        
        // Добавляем промежуточное ПО группы и маршрута
        $route->middleware(array_merge($this->middlewares, $middlewares));
        
        // Добавляем маршрут в коллекцию
        $this->routes[] = $route;
        
        return $route;
    }

    /**
     * Создание группы маршрутов
     */
    public function group(array $attributes, callable $callback): self
    {
        // Сохраняем текущее состояние
        $previousPrefix = $this->prefix;
        $previousMiddlewares = $this->middlewares;
        
        // Применяем атрибуты группы
        if (isset($attributes['prefix'])) {
            $this->prefix .= '/' . trim($attributes['prefix'], '/');
        }
        
        if (isset($attributes['middleware'])) {
            $middlewares = is_array($attributes['middleware']) ? $attributes['middleware'] : [$attributes['middleware']];
            $this->middlewares = array_merge($this->middlewares, $middlewares);
        }
        
        // Вызываем коллбэк для определения маршрутов в группе
        $callback($this);
        
        // Восстанавливаем предыдущее состояние
        $this->prefix = $previousPrefix;
        $this->middlewares = $previousMiddlewares;
        
        return $this;
    }

    /**
     * Поиск маршрута по методу и пути
     */
    public function match(string $method, string $path): ?Route
    {
        // Нормализуем путь
        $path = '/' . trim($path, '/');
        if ($path === '//' || $path === '') {
            $path = '/';
        }
        
        // Ищем маршрут
        foreach ($this->routes as $route) {
            if ($route->match($method, $path)) {
                return $route;
            }
        }
        
        return null;
    }

    /**
     * Получение всех маршрутов
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Добавление глобального middleware
     */
    public function middleware($middleware): self
    {
        if (is_array($middleware)) {
            $this->middlewares = array_merge($this->middlewares, $middleware);
        } else {
            $this->middlewares[] = $middleware;
        }
        
        return $this;
    }

    /**
     * Получение текущего префикса
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Получение текущих middlewares
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}