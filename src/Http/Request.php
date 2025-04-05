<?php

namespace SwooleAPI\Http;

use Swoole\Http\Request as SwooleRequest;

class Request
{
    private SwooleRequest $swooleRequest;
    private array $routeParams = [];
    private ?array $parsedBody = null;

    public function __construct(SwooleRequest $swooleRequest)
    {
        $this->swooleRequest = $swooleRequest;
    }

    /**
     * Получение метода запроса
     */
    public function getMethod(): string
    {
        return $this->swooleRequest->server['request_method'] ?? 'GET';
    }

    /**
     * Получение URI запроса
     */
    public function getUri(): string
    {
        return $this->swooleRequest->server['request_uri'] ?? '/';
    }

    /**
     * Получение URL запроса
     */
    public function getUrl(): string
    {
        $scheme = isset($this->swooleRequest->server['https']) && $this->swooleRequest->server['https'] === 'on' ? 'https' : 'http';
        $host = $this->swooleRequest->header['host'] ?? 'localhost';
        
        return $scheme . '://' . $host . $this->getUri();
    }

    /**
     * Получение HTTP заголовков
     */
    public function getHeaders(): array
    {
        return $this->swooleRequest->header ?? [];
    }

    /**
     * Получение значения заголовка
     */
    public function getHeader(string $name, $default = null)
    {
        $headers = $this->getHeaders();
        $name = strtolower($name);
        
        return $headers[$name] ?? $default;
    }

    /**
     * Получение GET параметров
     */
    public function getQueryParams(): array
    {
        return $this->swooleRequest->get ?? [];
    }

    /**
     * Получение значения GET параметра
     */
    public function getQuery(string $key, $default = null)
    {
        $params = $this->getQueryParams();
        
        return $params[$key] ?? $default;
    }

    /**
     * Установка параметров маршрута
     */
    public function setRouteParams(array $params): self
    {
        $this->routeParams = $params;
        
        return $this;
    }

    /**
     * Получение параметров маршрута
     */
    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Получение значения параметра маршрута
     */
    public function getRouteParam(string $key, $default = null)
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Получение POST параметров
     */
    public function getParsedBody(): array
    {
        if ($this->parsedBody !== null) {
            return $this->parsedBody;
        }
        
        // Если запрос типа application/json, декодируем его
        $contentType = $this->getHeader('content-type', '');
        
        if (strpos($contentType, 'application/json') !== false) {
            $body = $this->getBody();
            $this->parsedBody = json_decode($body, true) ?? [];
        } else {
            $this->parsedBody = $this->swooleRequest->post ?? [];
        }
        
        return $this->parsedBody;
    }
    
    /**
     * Получение значения POST параметра
     */
    public function getPost(string $key, $default = null)
    {
        $params = $this->getParsedBody();
        
        return $params[$key] ?? $default;
    }
    
    /**
     * Получение тела запроса
     */
    public function getBody(): string
    {
        return $this->swooleRequest->rawContent() ?? '';
    }
    
    /**
     * Получение загруженных файлов
     */
    public function getUploadedFiles(): array
    {
        return $this->swooleRequest->files ?? [];
    }
    
    /**
     * Получение информации о загруженном файле
     */
    public function getUploadedFile(string $key)
    {
        $files = $this->getUploadedFiles();
        
        return $files[$key] ?? null;
    }
    
    /**
     * Получение куки
     */
    public function getCookies(): array
    {
        return $this->swooleRequest->cookie ?? [];
    }
    
    /**
     * Получение значения куки
     */
    public function getCookie(string $key, $default = null)
    {
        $cookies = $this->getCookies();
        
        return $cookies[$key] ?? $default;
    }
    
    /**
     * Получение клиентского IP-адреса
     */
    public function getClientIp(): string
    {
        return $this->swooleRequest->server['remote_addr'] ?? '';
    }
    
    /**
     * Получение исходного Swoole-запроса
     */
    public function getSwooleRequest(): SwooleRequest
    {
        return $this->swooleRequest;
    }
    
    /**
     * Проверка метода запроса
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($this->getMethod()) === strtoupper($method);
    }
    
    /**
     * Проверка, является ли запрос AJAX
     */
    public function isAjax(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }
    
    /**
     * Проверка, использует ли запрос HTTPS
     */
    public function isSecure(): bool
    {
        return $this->swooleRequest->server['https'] ?? null === 'on';
    }
}