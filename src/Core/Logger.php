<?php

namespace SwooleAPI\Core;

class Logger
{
    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    private string $logPath;
    private string $logLevel;
    private array $levels = [
        self::EMERGENCY => 0,
        self::ALERT     => 1,
        self::CRITICAL  => 2,
        self::ERROR     => 3,
        self::WARNING   => 4,
        self::NOTICE    => 5,
        self::INFO      => 6,
        self::DEBUG     => 7
    ];

    public function __construct(string $logPath = 'logs', string $logLevel = self::DEBUG)
    {
        $this->logPath = rtrim($logPath, '/');
        $this->logLevel = $logLevel;
        
        // Создаем директорию для логов, если её нет
        if (!is_dir($this->logPath) && !mkdir($this->logPath, 0755, true)) {
            throw new \RuntimeException("Cannot create log directory: {$this->logPath}");
        }
    }

    /**
     * Логирование сообщения с указанным уровнем
     */
    public function log(string $level, string $message, array $context = []): void
    {
        // Проверяем, нужно ли логировать сообщение этого уровня
        if ($this->levels[$level] > $this->levels[$this->logLevel]) {
            return;
        }

        // Форматируем сообщение
        $dateTime = new \DateTime();
        $formattedMessage = sprintf(
            "[%s] %s: %s %s\n",
            $dateTime->format('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context) : ''
        );

        // Записываем в файл
        $filename = $this->logPath . '/' . $dateTime->format('Y-m-d') . '.log';
        file_put_contents($filename, $formattedMessage, FILE_APPEND);
    }

    /**
     * Логирование экстренного сообщения
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    /**
     * Логирование тревожного сообщения
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    /**
     * Логирование критического сообщения
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    /**
     * Логирование ошибки
     */
    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    /**
     * Логирование предупреждения
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    /**
     * Логирование уведомления
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    /**
     * Логирование информационного сообщения
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    /**
     * Логирование отладочного сообщения
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }
}