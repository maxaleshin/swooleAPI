<?php

namespace SwooleAPI\Routing;

use ReflectionClass;
use ReflectionMethod;

class AttributeRouteCollector
{
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Сбор маршрутов из атрибутов контроллеров
     */
    public function collect(array $controllersPaths): void
    {
        foreach ($controllersPaths as $path) {
            $this->collectFromPath($path);
        }
    }

    /**
     * Сбор маршрутов из папки с контроллерами
     */
    private function collectFromPath(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        // Рекурсивно ищем файлы PHP
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->processFile($file->getPathname());
            }
        }
    }

    /**
     * Обработка файла контроллера
     */
    private function processFile(string $file): void
    {
        // Получаем содержимое файла
        $content = file_get_contents($file);
        
        // Ищем объявление класса
        if (preg_match('/namespace\s+([^;]+)/i', $content, $matches)) {
            $namespace = $matches[1];
            
            // Ищем имя класса
            if (preg_match('/class\s+(\w+)/i', $content, $matches)) {
                $className = $matches[1];
                $fullClassName = $namespace . '\\' . $className;
                
                // Обрабатываем класс и его методы
                $this->processClass($fullClassName);
            }
        }
    }

    /**
     * Обработка класса контроллера
     */
    private function processClass(string $className): void
    {
        // Проверяем существование класса
        if (!class_exists($className)) {
            return;
        }

        // Получаем рефлексию класса
        $reflectionClass = new ReflectionClass($className);
        
        // Получаем публичные методы
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            // Пропускаем наследуемые методы
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }
            
            // Обрабатываем атрибуты метода
            $this->processMethodAttributes($method, $className);
        }
    }

    /**
     * Обработка атрибутов метода
     */
    private function processMethodAttributes(ReflectionMethod $method, string $className): void
    {
        // Получаем атрибуты
        $attributes = $method->getAttributes();
        
        foreach ($attributes as $attribute) {
            // Получаем имя атрибута
            $attributeName = $attribute->getName();
            
            // Проверяем, является ли атрибут маршрутом
            if ($this->isRouteAttribute($attributeName)) {
                // Получаем экземпляр атрибута
                $routeAttribute = $attribute->newInstance();
                
                // Получаем метод HTTP и путь из атрибута
                $httpMethod = $this->getHttpMethodFromAttribute($attributeName);
                $path = $routeAttribute->path;
                
                // Создаем обработчик
                $handler = [$className, $method->getName()];
                
                // Получаем middleware из атрибута, если они есть
                $middlewares = property_exists($routeAttribute, 'middlewares') ? $routeAttribute->middlewares : [];
                
                // Добавляем маршрут в маршрутизатор
                $this->router->$httpMethod($path, $handler, $middlewares);
            }
        }
    }

    /**
     * Проверка, является ли атрибут маршрутом
     */
    private function isRouteAttribute(string $attributeName): bool
    {
        $routeAttributeClasses = [
            'SwooleAPI\Routing\Attributes\Route',
            'SwooleAPI\Routing\Attributes\Get',
            'SwooleAPI\Routing\Attributes\Post',
            'SwooleAPI\Routing\Attributes\Put',
            'SwooleAPI\Routing\Attributes\Delete',
            'SwooleAPI\Routing\Attributes\Async'
        ];
        
        return in_array($attributeName, $routeAttributeClasses);
    }

    /**
     * Получение HTTP метода из атрибута
     */
    private function getHttpMethodFromAttribute(string $attributeName): string
    {
        $methodMap = [
            'SwooleAPI\Routing\Attributes\Get' => 'get',
            'SwooleAPI\Routing\Attributes\Post' => 'post',
            'SwooleAPI\Routing\Attributes\Put' => 'put',
            'SwooleAPI\Routing\Attributes\Delete' => 'delete',
            'SwooleAPI\Routing\Attributes\Route' => 'any',
            'SwooleAPI\Routing\Attributes\Async' => 'get' // По умолчанию Async использует GET
        ];
        
        return $methodMap[$attributeName] ?? 'any';
    }
}