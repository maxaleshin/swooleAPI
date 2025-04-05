<?php

namespace SwooleAPI\Middleware;

use SwooleAPI\Http\Request;
use SwooleAPI\Http\Response;

class Cors implements MiddlewareInterface
{
    private array $options;

    public function __construct(array $options = [])
    {
        // Опции по умолчанию
        $defaultOptions = [
            'allowedOrigins' => ['*'],
            'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowedHeaders' => ['Content-Type', 'X-Requested-With', 'Authorization'],
            'exposedHeaders' => [],
            'maxAge' => 0,
            'allowCredentials' => false,
        ];

        $this->options = array_merge($defaultOptions, $options);
    }

    /**
     * Обработка запроса
     */
    public function process(Request $request, Response $response, callable $next): void
    {
        // Проверяем наличие заголовка Origin
        $origin = $request->getHeader('origin');

        if ($origin) {
            // Проверяем, разрешен ли данный источник
            $allowedOrigin = $this->isOriginAllowed($origin) ? $origin : '';

            // Устанавливаем заголовки CORS
            $response->setHeader('Access-Control-Allow-Origin', $allowedOrigin);

            if ($this->options['allowCredentials']) {
                $response->setHeader('Access-Control-Allow-Credentials', 'true');
            }

            if (!empty($this->options['exposedHeaders'])) {
                $response->setHeader('Access-Control-Expose-Headers', implode(', ', $this->options['exposedHeaders']));
            }

            // Для префлайт-запросов
            if ($request->getMethod() === 'OPTIONS') {
                $response->setHeader('Access-Control-Allow-Methods', implode(', ', $this->options['allowedMethods']));
                $response->setHeader('Access-Control-Allow-Headers', implode(', ', $this->options['allowedHeaders']));

                if ($this->options['maxAge'] > 0) {
                    $response->setHeader('Access-Control-Max-Age', (string)$this->options['maxAge']);
                }

                // Для префлайт-запросов возвращаем ответ сразу
                $response->setStatusCode(204)->write('');
                return;
            }
        }

        // Продолжаем выполнение цепочки middleware
        $next();
    }

    /**
     * Проверка, разрешен ли источник
     */
    private function isOriginAllowed(string $origin): bool
    {
        if (in_array('*', $this->options['allowedOrigins'])) {
            return true;
        }

        return in_array($origin, $this->options['allowedOrigins']);
    }
}