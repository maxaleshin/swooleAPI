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
        // поиск маршрута в роутере
        $route = $router->match($request->getMethod(), $request->getUri());

        // ошибка несопоставления
        if ($route === null) {
            $response->setStatusCode(404)->json([
                'error' => 'Not Found',
                'message' => "Route not found: {$request->getMethod()} {$request->getUri()}"
            ]);
            return;
        }

        // установка параметров маршрута в запросе
        $request->setRouteParams($route->getParams());

        // забираем контроллер и метод
        $handler = $route->getHandler();
        
        // запуск цепочки middleware
        // TODO: изменить порядок? надо  не надо я не знаю
        $this->processMiddlewares($request, $response, $route->getMiddlewares(), function() use ($request, $response, $handler) {
            // обработчик маршрута
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

        // возьмем первый middleware-обработчик и определим его класс
        $middleware = array_shift($middlewares);
        $middlewareClass = is_string($middleware) ? $middleware : get_class($middleware);
        
        // возмьме его экземпляр
        $instance = is_string($middleware) ? $this->container->get($middlewareClass) : $middleware;
        if (!$instance instanceof MiddlewareInterface) {
            throw new \RuntimeException("Middleware must implement MiddlewareInterface: {$middlewareClass}");
        }

        // запускаем работу middleware
        $instance->process($request, $response, function() use ($request, $response, $middlewares, $final) {
            $this->processMiddlewares($request, $response, $middlewares, $final); // А теперь вложенно продолжаем идти по оставшимся мидлварам
        });
    }

    /**
     * Вызов обработчика маршрута (контроллера)
     */
    private function callRouteHandler($handler, Request $request, Response $response): void
    {
        // определяем тип обработчика
        if (is_callable($handler)) {
            // если обработчик - замыкание, вызываем его через контейнер
            $result = $this->container->call($handler, [
                'request' => $request,
                'response' => $response
            ]);
            
            $this->handleResult($result, $response);
        } elseif (is_array($handler) && count($handler) === 2) {
            // если обработчик - массив [класс, метод], то тоже вызываем через контейнер
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
        // если результат не null и еще не отправлен
        // (такое возможно если промежуточный обработчик выше решит отправить, проще проверить здесь что не отправлен, чем проверять там)
        if ($result !== null && !$response->isSent()) {
            if (is_array($result) || is_object($result)) {
                //  JSON для массивов и объектов
                $response->json($result);
            } elseif (is_string($result)) {
                // текстовый ответ для строк
                $response->write($result);
            } else {
                // для остальных насильно преобразуем в строку
                $response->write((string)$result);
            }
        }
    }
}