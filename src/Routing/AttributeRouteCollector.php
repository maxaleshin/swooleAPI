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

        // забираем все маршруты, которые прописали в папке Attributes
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->processFile($file->getPathname()); // запускаем обработку ФАЙЛА
            }
        }
    }

    /**
     * Обработка файла контроллера
     */
    private function processFile(string $file): void
    {
        // получаем содержимое файла
        $content = file_get_contents($file);
        
        // ищем объявление класса с помощью регулярного выражжения
        if (preg_match('/namespace\s+([^;]+)/i', $content, $matches)) {
            $namespace = $matches[1];
            
            // забираем имя класса
            if (preg_match('/class\s+(\w+)/i', $content, $matches)) {
                $className = $matches[1];
                $fullClassName = $namespace . '\\' . $className;
                
                $this->processClass($fullClassName); // запускаем обработку КЛАССА
            }
        }
    }

    /**
     * Обработка класса контроллера
     */
    private function processClass(string $className): void
    {
        // так проверяем определен ли класс
        if (!class_exists($className)) {
            return;
        }

        // забираем рефлексию класса
        $reflectionClass = new ReflectionClass($className);
        
        // выбираем публичные методы
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() !== $className) { /// проверяем, является ли метод унаследованным
                continue; // если метод наследуемый, то пропускаем, в итоге останутся только ненаследуемые
            }
            
            // запускаем обработку МЕТОДА
            $this->processMethodAttributes($method, $className);
        }
    }

    /**
     * Обработка атрибутов метода
     */
    private function processMethodAttributes(ReflectionMethod $method, string $className): void
    {
        // получаем атрибуты
        $attributes = $method->getAttributes();
        
        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            
            // проверка на то что аттрибут является маршрутом
            if ($this->isRouteAttribute($attributeName)) {
                // берем экземпляр
                $routeAttribute = $attribute->newInstance();
                
                // забираем Http-метод из аттрибута
                $httpMethod = $this->getHttpMethodFromAttribute($attributeName);
                $path = $routeAttribute->path;
                
                // создаем хендлер
                $handler = [$className, $method->getName()];
                
                // берем middleware из атрибута, если они есть
                $middlewares = property_exists($routeAttribute, 'middlewares') ? $routeAttribute->middlewares : [];
                
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
            'SwooleAPI\Routing\Attributes\Async' => 'get' // по умолчанию Async будет использовать GET
        ];
        
        return $methodMap[$attributeName] ?? 'any';
    }
}