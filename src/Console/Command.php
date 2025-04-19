<?php

namespace SwooleAPI\Console;

abstract class Command
{
    protected string $name = '';
    protected string $description = '';
    protected array $arguments = [];
    protected array $options = [];

    /**
     * Выполнение команды
     */
    abstract public function handle(array $args = [], array $options = []): int;

    /**
     * Получение имени команды
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Получение описания команды
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Получение аргументов команды
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Получение опций команды
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Вывод сообщения в консоль
     */
    protected function write(string $message, string $style = null): void
    {
        if ($style) {
            echo $this->applyStyle($message, $style) . PHP_EOL;
        } else {
            echo $message . PHP_EOL;
        }
    }

    /**
     * Вывод информационного сообщения
     */
    protected function info(string $message): void
    {
        $this->write($message, 'info');
    }

    /**
     * Вывод сообщения об успехе
     */
    protected function success(string $message): void
    {
        $this->write($message, 'success');
    }

    /**
     * Вывод предупреждения
     */
    protected function warning(string $message): void
    {
        $this->write($message, 'warning');
    }

    /**
     * Вывод сообщения об ошибке
     */
    protected function error(string $message): void
    {
        $this->write($message, 'error');
    }

    /**
     * Применение стиля к тексту
     */
    protected function applyStyle(string $message, string $style): string
    {
        $styles = [
            'info' => "\033[36m", // Голубой
            'success' => "\033[32m", // Зеленый
            'warning' => "\033[33m", // Желтый
            'error' => "\033[31m", // Красный
            'bold' => "\033[1m", // Жирный
            'underline' => "\033[4m", // Подчеркнутый
            'reset' => "\033[0m" // Сброс стилей
        ];

        if (!isset($styles[$style])) {
            return $message;
        }

        return $styles[$style] . $message . $styles['reset'];
    }

    /**
     * Запрос ввода от пользователя
     */
    protected function ask(string $question, string $default = null): string
    {
        $defaultText = $default ? " [" . $this->applyStyle($default, 'bold') . "]" : '';
        $this->write($question . $defaultText . ": ", 'info');
        
        $answer = trim(fgets(STDIN));
        
        return $answer !== '' ? $answer : $default;
    }

    /**
     * Запрос подтверждения от пользователя
     */
    protected function confirm(string $question, bool $default = false): bool
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        $answer = $this->ask($question . " (" . $defaultText . ")");
        
        if ($answer === '') {
            return $default;
        }
        
        return strtolower($answer[0]) === 'y';
    }

    /**
     * Запрос выбора из списка
     */
    protected function choice(string $question, array $choices, $default = null): string
    {
        $this->write($question, 'info');
        
        foreach ($choices as $key => $choice) {
            $mark = ($default !== null && $default == $key) ? '*' : ' ';
            $this->write(" [{$mark}] {$key}: {$choice}");
        }
        
        $answer = $this->ask('Ваш выбор');
        
        if ($answer === '' && $default !== null) {
            return $default;
        }
        
        if (!isset($choices[$answer])) {
            $this->error('Некорректный выбор.');
            return $this->choice($question, $choices, $default);
        }
        
        return $answer;
    }
}