<?php

namespace SwooleAPI\Core;

class Container
{
    private array $bindings = [];
    private array $instances = [];

    /**
     * Связывание интерфейса с реализацией или фабрикой
     */
    public function bind(string $abstract, $concrete = null): void
    {
        // Если конкретная реализация не указана, используем абстракцию
        if ($concrete === null) {
            $concrete = $abstract;
        }

        // Сохраняем привязку
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Связывание интерфейса с реализацией в режиме singleton
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete);
        
        // Помечаем как синглтон
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => true
        ];
    }

    /**
     * Получение экземпляра
     * 
     * @throws \Exception
     */
    public function get(string $abstract)
    {
        // Если экземпляр уже создан, возвращаем его
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Если привязки нет, пробуем создать экземпляр напрямую
        if (!isset($this->bindings[$abstract])) {
            return $this->resolve($abstract);
        }

        $concrete = $this->bindings[$abstract];
        $isSingleton = false;

        // Проверяем, является ли привязка массивом с информацией о синглтоне
        if (is_array($concrete) && isset($concrete['concrete'])) {
            $isSingleton = $concrete['singleton'] ?? false;
            $concrete = $concrete['concrete'];
        }

        // Создаем экземпляр
        $instance = $this->resolve($concrete);

        // Если синглтон, сохраняем экземпляр
        if ($isSingleton) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Создание экземпляра
     * 
     * @throws \Exception
     */
    private function resolve($concrete)
    {
        // Если concrete - это замыкание, вызываем его
        if ($concrete instanceof \Closure) {
            return $concrete($this);
        }

        // Если это строка (имя класса), создаем экземпляр через рефлексию
        if (is_string($concrete)) {
            return $this->build($concrete);
        }

        // Иначе возвращаем как есть
        return $concrete;
    }

    /**
     * Создание экземпляра класса с автоматическим внедрением зависимостей
     * 
     * @throws \Exception
     */
    private function build(string $concrete)
    {
        // Получаем информацию о классе через рефлексию
        try {
            $reflector = new \ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new \Exception("Class {$concrete} does not exist");
        }

        // Проверяем, можно ли создать экземпляр
        if (!$reflector->isInstantiable()) {
            throw new \Exception("Class {$concrete} is not instantiable");
        }

        // Получаем конструктор
        $constructor = $reflector->getConstructor();

        // Если конструктора нет, создаем экземпляр напрямую
        if ($constructor === null) {
            return new $concrete();
        }

        // Получаем параметры конструктора
        $parameters = $constructor->getParameters();

        // Если параметров нет, создаем экземпляр напрямую
        if (count($parameters) === 0) {
            return new $concrete();
        }

        // Разрешаем зависимости для каждого параметра
        $dependencies = [];

        foreach ($parameters as $parameter) {
            // Получаем тип параметра
            $type = $parameter->getType();

            // Если тип отсутствует или это встроенный тип, и параметр имеет значение по умолчанию
            if ($type === null || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception("Cannot resolve parameter {$parameter->getName()}");
                }
            } else {
                // Если тип - это класс или интерфейс, рекурсивно получаем экземпляр
                $dependencies[] = $this->get($type->getName());
            }
        }

        // Создаем экземпляр с зависимостями
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Проверка, имеется ли привязка
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Вызов метода с автоматическим внедрением зависимостей
     */
    public function call($callable, array $params = [])
    {
        // Получаем рефлексию метода
        if (is_array($callable) && count($callable) === 2) {
            $reflectionMethod = new \ReflectionMethod($callable[0], $callable[1]);
        } elseif (is_string($callable) && strpos($callable, '::') !== false) {
            $parts = explode('::', $callable);
            $reflectionMethod = new \ReflectionMethod($parts[0], $parts[1]);
        } elseif (is_object($callable) && method_exists($callable, '__invoke')) {
            $reflectionMethod = new \ReflectionMethod($callable, '__invoke');
        } else {
            $reflectionMethod = new \ReflectionFunction($callable);
        }

        // Получаем параметры метода
        $dependencies = [];
        $parameters = $reflectionMethod->getParameters();

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            
            // Если параметр передан явно, используем его
            if (array_key_exists($name, $params)) {
                $dependencies[] = $params[$name];
                continue;
            }
            
            // Пытаемся получить тип параметра
            $type = $parameter->getType();
            
            // Если тип отсутствует или это встроенный тип, и параметр имеет значение по умолчанию
            if ($type === null || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception("Cannot resolve parameter {$name}");
                }
            } else {
                // Если тип - это класс или интерфейс, получаем экземпляр
                $dependencies[] = $this->get($type->getName());
            }
        }

        // Вызываем метод с зависимостями
        if (is_array($callable) && count($callable) === 2) {
            return $reflectionMethod->invokeArgs(is_string($callable[0]) ? $this->get($callable[0]) : $callable[0], $dependencies);
        } elseif (is_string($callable) && strpos($callable, '::') !== false) {
            $parts = explode('::', $callable);
            return $reflectionMethod->invokeArgs(null, $dependencies);
        } elseif (is_object($callable) && method_exists($callable, '__invoke')) {
            return $reflectionMethod->invokeArgs($callable, $dependencies);
        } else {
            return $reflectionMethod->invokeArgs(null, $dependencies);
        }
    }
}