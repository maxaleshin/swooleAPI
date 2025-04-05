<?php

namespace SwooleAPI\Core;

class Config
{
    private array $config = [];

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Получение значения по ключу с поддержкой точечной нотации
     * Например: $config->get('database.mysql.host')
     */
    public function get(string $key, $default = null)
    {
        // Разбиваем ключ на части
        $parts = explode('.', $key);
        $config = $this->config;

        // Ищем значение в конфигурации
        foreach ($parts as $part) {
            if (!is_array($config) || !array_key_exists($part, $config)) {
                return $default;
            }
            $config = $config[$part];
        }

        return $config;
    }

    /**
     * Установка значения по ключу с поддержкой точечной нотации
     */
    public function set(string $key, $value): self
    {
        // Разбиваем ключ на части
        $parts = explode('.', $key);
        $config = &$this->config;

        // Создаем вложенные массивы, если их нет
        $lastKey = array_pop($parts);
        foreach ($parts as $part) {
            if (!isset($config[$part]) || !is_array($config[$part])) {
                $config[$part] = [];
            }
            $config = &$config[$part];
        }

        // Устанавливаем значение
        $config[$lastKey] = $value;

        return $this;
    }

    /**
     * Загрузка конфигурации из файла
     */
    public function load(string $path): self
    {
        if (file_exists($path)) {
            $config = require $path;
            
            if (is_array($config)) {
                $this->merge($config);
            }
        }

        return $this;
    }

    /**
     * Слияние с другой конфигурацией
     */
    public function merge(array $config): self
    {
        $this->config = array_merge_recursive($this->config, $config);
        return $this;
    }

    /**
     * Проверка наличия ключа в конфигурации
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Получение всей конфигурации
     */
    public function all(): array
    {
        return $this->config;
    }
}