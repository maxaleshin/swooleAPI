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
    private bool $outputToConsole;
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
    
    // ANSI цвета для консольного вывода
    private array $consoleColors = [
        self::EMERGENCY => "\033[41m", // Красный фон
        self::ALERT     => "\033[41;97m", // Красный фон, белый текст
        self::CRITICAL  => "\033[31;1m", // Ярко-красный
        self::ERROR     => "\033[31m", // Красный
        self::WARNING   => "\033[33m", // Желтый
        self::NOTICE    => "\033[36m", // Голубой
        self::INFO      => "\033[32m", // Зеленый
        self::DEBUG     => "\033[90m"  // Серый
    ];
    
    private string $resetColor = "\033[0m";

    public function __construct(string $logPath = 'logs', string $logLevel = self::DEBUG, bool $outputToConsole = true)
    {
        $this->logPath = rtrim($logPath, '/');
        $this->logLevel = $logLevel;
        $this->outputToConsole = $outputToConsole;
        
        // Создаем директорию для логов, если её нет
        if (!is_dir($this->logPath) && !mkdir($this->logPath, 0755, true)) {
            throw new \RuntimeException("Cannot create log directory: {$this->logPath}");
        }
    }

    /**
     * Включение/отключение вывода в консоль
     */
    public function setOutputToConsole(bool $outputToConsole): self
    {
        $this->outputToConsole = $outputToConsole;
        return $this;
    }

    /**
     * Установка уровня логирования
     */
    public function setLogLevel(string $logLevel): self
    {
        $this->logLevel = $logLevel;
        return $this;
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

        // Получаем информацию о вызове
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1] ?? $trace[0];
        $callerInfo = '';
        
        if (isset($caller['class'])) {
            $callerInfo = "{$caller['class']}::{$caller['function']}";
        } elseif (isset($caller['function'])) {
            $callerInfo = $caller['function'];
        }
        
        if (isset($caller['file']) && isset($caller['line'])) {
            $file = basename($caller['file']);
            $callerInfo .= " ({$file}:{$caller['line']})";
        }

        // Форматируем сообщение
        $dateTime = new \DateTime();
        $formattedMessage = sprintf(
            "[%s] %s: %s %s %s\n",
            $dateTime->format('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            !empty($callerInfo) ? "[$callerInfo]" : '',
            !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );

        // Записываем в файл
        $filename = $this->logPath . '/' . $dateTime->format('Y-m-d') . '.log';
        file_put_contents($filename, $formattedMessage, FILE_APPEND);
        
        // Выводим в консоль, если включено
        if ($this->outputToConsole) {
            $colorStart = $this->consoleColors[$level] ?? '';
            $colorEnd = $this->resetColor;
            
            // Удаляем перенос строки для форматирования консольного вывода
            $consoleMessage = rtrim($formattedMessage);
            
            // Выводим в stdout или stderr в зависимости от уровня
            if ($this->levels[$level] <= $this->levels[self::ERROR]) {
                // Для серьезных ошибок используем STDERR
                fwrite(STDERR, $colorStart . $consoleMessage . $colorEnd . PHP_EOL);
            } else {
                // Для обычных сообщений используем STDOUT
                fwrite(STDOUT, $colorStart . $consoleMessage . $colorEnd . PHP_EOL);
            }
        }
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