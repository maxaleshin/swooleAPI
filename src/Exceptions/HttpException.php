<?php

namespace SwooleAPI\Exceptions;

class HttpException extends \Exception
{
    protected int $statusCode;
    protected array $headers;

    public function __construct(int $statusCode, string $message = '', array $headers = [], \Throwable $previous = null)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Получение HTTP статус-кода
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Получение HTTP заголовков
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Создание исключения 400 Bad Request
     */
    public static function badRequest(string $message = 'Bad Request', array $headers = []): self
    {
        return new static(400, $message, $headers);
    }

    /**
     * Создание исключения 401 Unauthorized
     */
    public static function unauthorized(string $message = 'Unauthorized', array $headers = []): self
    {
        return new static(401, $message, $headers);
    }

    /**
     * Создание исключения 403 Forbidden
     */
    public static function forbidden(string $message = 'Forbidden', array $headers = []): self
    {
        return new static(403, $message, $headers);
    }

    /**
     * Создание исключения 404 Not Found
     */
    public static function notFound(string $message = 'Not Found', array $headers = []): self
    {
        return new static(404, $message, $headers);
    }

    /**
     * Создание исключения 405 Method Not Allowed
     */
    public static function methodNotAllowed(string $message = 'Method Not Allowed', array $headers = []): self
    {
        return new static(405, $message, $headers);
    }

    /**
     * Создание исключения 409 Conflict
     */
    public static function conflict(string $message = 'Conflict', array $headers = []): self
    {
        return new static(409, $message, $headers);
    }

    /**
     * Создание исключения 422 Unprocessable Entity
     */
    public static function unprocessableEntity(string $message = 'Unprocessable Entity', array $headers = []): self
    {
        return new static(422, $message, $headers);
    }

    /**
     * Создание исключения 429 Too Many Requests
     */
    public static function tooManyRequests(string $message = 'Too Many Requests', array $headers = []): self
    {
        return new static(429, $message, $headers);
    }

    /**
     * Создание исключения 500 Internal Server Error
     */
    public static function internalServerError(string $message = 'Internal Server Error', array $headers = []): self
    {
        return new static(500, $message, $headers);
    }

    /**
     * Создание исключения 503 Service Unavailable
     */
    public static function serviceUnavailable(string $message = 'Service Unavailable', array $headers = []): self
    {
        return new static(503, $message, $headers);
    }
}