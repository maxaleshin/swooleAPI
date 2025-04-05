<?php

namespace SwooleAPI\Middleware;

use SwooleAPI\Http\Request;
use SwooleAPI\Http\Response;

interface MiddlewareInterface
{
    /**
     * Обработка запроса middleware
     * 
     * @param Request $request Объект запроса
     * @param Response $response Объект ответа
     * @param callable $next Следующий обработчик в цепочке
     * @return void
     */
    public function process(Request $request, Response $response, callable $next): void;
}