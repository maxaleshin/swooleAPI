<?php

namespace SwooleAPI\Core;

use SwooleAPI\Http\Request;
use SwooleAPI\Http\Response;
use SwooleAPI\Routing\Router;
use SwooleAPI\Middleware\MiddlewareInterface;

class RequestHandler
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Обработка HTTP-запроса
     */
    public function handle(Request $request, Response $response, Router $router): void
    {
        // Ищем маршрут
        $route = $router->match($request->getMethod(), $request->getUri());

        if ($route === null) {
            $response->setStatusCode(404)->json([
                'error' => 'Not Found',
                'message' => "Route not found: {$request->getMethod()} {$request->getUri()}"
            ]);
            return;
        }

        // Устанавливаем параметры маршрута в запросе
        $request->setRouteParams($route->getParams());

        // Получаем контроллер и метод
        $handler = $route->getHandler();
        
        // Запускаем цепочку middleware
        $this->processMiddlewares($request, $response, $route->getMiddlewares(), function() use ($request, $response, $handler) {
            // Вызываем обработчик маршрута
            $this->callRouteHandler($handler, $request, $response);
        });
    }

    /**
     * Обработка цепочки middleware
     */
    private function processMiddlewares(Request $request, Response $response, array $middlewares, \Closure $final): void
    {
        if (empty($middlewares)) {
            $final();
            return;
        }

        // Берем первый middleware из списка
        $middleware = array_shift($middlewares);
        
        // Определяем его класс
        $middlewareClass = is_string($middleware) ? $middleware : get_class($middleware);
        
        // Получаем экземпляр middleware
        $instance = is_string($middleware) ? $this->container->get($middlewareClass) : $middleware;
        
        if (!$instance instanceof MiddlewareInterface) {
            throw new \RuntimeException("Middleware must implement MiddlewareInterface: {$middlewareClass}");
        }

        // Запускаем middleware с вложенной обработкой оставшихся middleware
        $instance->process($request, $response, function() use ($request, $response, $middlewares, $final) {
            $this->processMiddlewares($request, $response, $middlewares, $final);
        });
    }

    /**
     * Вызов обработчика маршрута (контроллера)
     */
    private function callRouteHandler($handler, Request $request, Response $response): void
    {
        // Определяем тип обработчика
        if (is_callable($handler)) {
            // Если обработчик - замыкание, вызываем его через контейнер
            $result = $this->container->call($handler, [
                'request' => $request,
                'response' => $response
            ]);
            
            $this->handleResult($result, $response);
        } elseif (is_array($handler) && count($handler) === 2) {
            // Если обработчик - массив [класс, метод], вызываем через контейнер
            $controller = is_object($handler[0]) ? $handler[0] : $this->container->get($handler[0]);
            
            $result = $this->container->call([$controller, $handler[1]], [
                'request' => $request,
                'response' => $response
            ]);
            
            $this->handleResult($result, $response);
        } else {
            throw new \RuntimeException("Invalid route handler: " . json_encode($handler));
        }
    }

    /**
     * Обработка результата контроллера
     */
    private function handleResult($result, Response $response): void
    {
        // Если результат не null и ответ еще не отправлен
        if ($result !== null && !$response->isSent()) {
            if (is_array($result) || is_object($result)) {
                // Отправляем JSON ответ для массивов и объектов
                $response->json($result);
            } elseif (is_string($result)) {
                // Отправляем текстовый ответ для строк
                $response->write($result);
            } else {
                // Для других типов преобразуем в строку
                $response->write((string)$result);
            }
        }
    }
}