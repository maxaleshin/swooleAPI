<?php

namespace SwooleAPI\Http;

use Swoole\Http\Response as SwooleResponse;

class Response
{
    private SwooleResponse $swooleResponse;
    private int $statusCode = 200;
    private array $headers = [];
    private bool $sent = false;

    public function __construct(SwooleResponse $swooleResponse)
    {
        $this->swooleResponse = $swooleResponse;
    }

    /**
     * Установка HTTP статус кода
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Получение текущего статус кода
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Установка HTTP заголовка
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Установка нескольких HTTP заголовков
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    /**
     * Получение всех заголовков
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Установка куки
     */
    public function setCookie(
        string $name,
        string $value = '',
        int $expires = 0,
        string $path = '/',
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = false
    ): self {
        $this->swooleResponse->cookie($name, $value, $expires, $path, $domain, $secure, $httpOnly);
        return $this;
    }

    /**
     * Запись текстового содержимого
     */
    public function write(string $content): self
    {
        if ($this->sent) {
            return $this;
        }

        // Устанавливаем статус код
        $this->swooleResponse->status($this->statusCode);

        // Устанавливаем заголовки
        foreach ($this->headers as $name => $value) {
            $this->swooleResponse->header($name, $value);
        }

        // Устанавливаем тип содержимого, если он не установлен
        if (!isset($this->headers['Content-Type'])) {
            $this->swooleResponse->header('Content-Type', 'text/plain; charset=utf-8');
        }

        // Отправляем содержимое
        $this->swooleResponse->end($content);
        $this->sent = true;

        return $this;
    }

    /**
     * Отправка JSON ответа
     */
    public function json($data): self
    {
        if ($this->sent) {
            return $this;
        }

        // Устанавливаем заголовок Content-Type
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');

        // Кодируем данные в JSON
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Отправляем ответ
        return $this->write($json);
    }

    /**
     * Отправка HTML ответа
     */
    public function html(string $html): self
    {
        if ($this->sent) {
            return $this;
        }

        // Устанавливаем заголовок Content-Type
        $this->setHeader('Content-Type', 'text/html; charset=utf-8');

        // Отправляем ответ
        return $this->write($html);
    }

    /**
     * Перенаправление на другой URL
     */
    public function redirect(string $url, int $statusCode = 302): self
    {
        if ($this->sent) {
            return $this;
        }

        // Устанавливаем заголовок Location и статус код
        $this->setHeader('Location', $url);
        $this->setStatusCode($statusCode);

        // Отправляем пустой ответ
        return $this->write('');
    }

    /**
     * Отправка файла
     */
    public function sendFile(string $path): self
    {
        if ($this->sent) {
            return $this;
        }

        // Проверяем существование файла
        if (!file_exists($path)) {
            $this->setStatusCode(404);
            return $this->write('File not found');
        }

        // Определяем MIME-тип файла
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
        $this->setHeader('Content-Type', $mimeType);

        // Устанавливаем статус код
        $this->swooleResponse->status($this->statusCode);

        // Устанавливаем заголовки
        foreach ($this->headers as $name => $value) {
            $this->swooleResponse->header($name, $value);
        }

        // Отправляем файл
        $this->swooleResponse->sendfile($path);
        $this->sent = true;

        return $this;
    }

    /**
     * Отправка бинарных данных
     */
    public function binary(string $data, string $mimeType = 'application/octet-stream'): self
    {
        if ($this->sent) {
            return $this;
        }

        // Устанавливаем заголовок Content-Type
        $this->setHeader('Content-Type', $mimeType);

        // Отправляем ответ
        return $this->write($data);
    }

    /**
     * Проверка, был ли ответ отправлен
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Получение исходного Swoole-ответа
     */
    public function getSwooleResponse(): SwooleResponse
    {
        return $this->swooleResponse;
    }
}