<?php

namespace SwooleAPI\Routing;

class Route
{
    private string $method;
    private string $path;
    private $handler;
    private array $middlewares = [];
    private array $params = [];
    private ?string $name = null;

    public function __construct(string $method, string $path, $handler)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->handler = $handler;
    }

    /**
     * Проверка соответствия маршрута методу и пути
     */
    public function match(string $method, string $path): bool
    {
        // проверка метода
        if ($this->method !== 'ANY' && $this->method !== strtoupper($method)) {
            return false;
        }
        
        // и пути
        $result = $this->matchPath($path);
        
        return $result;
    }

    /**
     * Проверка соответствия пути и извлечение параметров
     */
    private function matchPath(string $path): bool
    {
        // самый простой вариант, например при GET /users/ маршруты будут совпадать буквально 
        if ($this->path === $path) {
            return true;
        }
        
        // парсинг шаблона пути. проверка через регулярное выражение.
        $pattern = $this->pathToPattern($this->path);
        if (preg_match($pattern, $path, $matches)) {
            // забираем параметры маршрута
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $this->params[$key] = $value;
                }
            }
            return true;
        }
        
        return false;
    }

    /**
     * Преобразование пути в регулярное выражение
     */
    private function pathToPattern(string $path): string
    {
        // поддержка именнованных параметров, т.е. если параметры передаются в виде GET /users/id:123
        // /users/{id} → /users/(?<id>)
        // /users/{id:\d+} → /users/(?<id>\d+)
        $pattern = preg_replace('/{([^:}]+)(:([^}]+))?}/', '(?<$1>$3)', $path);
        
        // случай, когда шаблон не указывается
        // /users/(?<id>) → /users/(?<id>[^/]+)
        // (/users/(?<id>\d+) останется без изменений, так как уже содержит шаблон)
        $pattern = preg_replace('/\(\?<([^>]+)>\)/', '(?<$1>[^/]+)', $pattern);
        
        // экранирует слеши, добавляя начало и конец строки
        $pattern = '#^' . str_replace('/', '\/', $pattern) . '$#';
        
        return $pattern;  // возвращаем непосредственно регулярку, которая используется в matchPath()
    }

    /**
     * Установка имени маршрута
     */
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Получение имени маршрута
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Добавление промежуточного ПО (middleware)
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
     * Получение всех middleware
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Получение метода маршрута
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Получение пути маршрута
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Получение обработчика маршрута
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * Получение параметров маршрута
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Установка параметров маршрута
     */
    public function setParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }
}