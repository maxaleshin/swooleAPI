<?php

namespace SwooleAPI\Database;

use SwooleAPI\Core\Application;

abstract class Model
{
    /**
     * Имя таблицы в базе данных
     */
    protected string $table;

    /**
     * Первичный ключ таблицы
     */
    protected string $primaryKey = 'id';

    /**
     * Указывает, автоматически ли управлять полями created_at и updated_at
     */
    protected bool $timestamps = true;

    /**
     * Имя поля для хранения времени создания
     */
    protected string $createdAtColumn = 'created_at';

    /**
     * Имя поля для хранения времени обновления
     */
    protected string $updatedAtColumn = 'updated_at';

    /**
     * Атрибуты модели
     */
    protected array $attributes = [];

    /**
     * Атрибуты, которые можно массово заполнять
     */
    protected array $fillable = [];

    /**
     * Атрибуты, которые нельзя массово заполнять
     */
    protected array $guarded = ['id'];

    /**
     * Исходные атрибуты модели
     */
    protected array $original = [];

    /**
     * Конструктор модели
     */
    public function __construct(array $attributes = [])
    {
        // Заполняем атрибуты
        $this->fill($attributes);
        
        // Сохраняем исходные атрибуты
        $this->syncOriginal();
    }

    /**
     * Получение имени таблицы
     */
    public function getTable(): string
    {
        if (isset($this->table)) {
            return $this->table;
        }

        // Если имя таблицы не задано, используем имя класса во множественном числе
        $className = basename(str_replace('\\', '/', get_class($this)));
        return strtolower($className) . 's';
    }

    /**
     * Получение имени первичного ключа
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Получение значения первичного ключа
     */
    public function getKey()
    {
        return $this->getAttribute($this->primaryKey);
    }

    /**
     * Синхронизация исходных атрибутов
     */
    public function syncOriginal(): self
    {
        $this->original = $this->attributes;
        return $this;
    }

    /**
     * Заполнение модели атрибутами
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * Проверка, можно ли заполнять атрибут
     */
    public function isFillable(string $key): bool
    {
        // Если атрибут в списке защищенных, его нельзя заполнять
        if (in_array($key, $this->guarded)) {
            return false;
        }

        // Если список разрешенных пуст, или атрибут в списке разрешенных, его можно заполнять
        return empty($this->fillable) || in_array($key, $this->fillable);
    }

    /**
     * Установка значения атрибута
     */
    public function setAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Получение значения атрибута
     */
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Получение всех атрибутов
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Проверка существования атрибута
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Получение изменений атрибутов
     */
    public function getDirty(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Проверка, были ли изменены атрибуты
     */
    public function isDirty(): bool
    {
        return !empty($this->getDirty());
    }

    /**
     * Получение QueryBuilder для таблицы модели
     */
    public function newQuery(): QueryBuilder
    {
        $connection = $this->getConnection();
        return $connection->table($this->getTable());
    }

    /**
     * Получение соединения с базой данных
     */
    public function getConnection(): Connections\Connection
    {
        $db = Application::getInstance()->getContainer()->get('db');
        return $db->connection();
    }

    /**
     * Сохранение модели в базе данных
     */
    public function save(): bool
    {
        // Если у модели есть первичный ключ, обновляем её
        if ($this->getKey()) {
            return $this->update();
        }

        // Иначе создаем новую запись
        return $this->insert();
    }

    /**
     * Создание новой записи в базе данных
     */
    protected function insert(): bool
    {
        // Если нужно автоматически управлять временем
        if ($this->timestamps) {
            $this->setAttribute($this->createdAtColumn, date('Y-m-d H:i:s'));
            $this->setAttribute($this->updatedAtColumn, date('Y-m-d H:i:s'));
        }

        // Вставляем запись
        $result = $this->newQuery()->insert($this->attributes);

        // Если вставка успешна, получаем ID
        if ($result) {
            $id = $this->getConnection()->lastInsertId();
            $this->setAttribute($this->primaryKey, $id);
            $this->syncOriginal();
        }

        return $result;
    }

    /**
     * Обновление существующей записи в базе данных
     */
    protected function update(): bool
    {
        // Получаем только измененные атрибуты
        $dirty = $this->getDirty();

        // Если нет изменений, ничего не делаем
        if (empty($dirty)) {
            return true;
        }

        // Если нужно автоматически управлять временем
        if ($this->timestamps) {
            $this->setAttribute($this->updatedAtColumn, date('Y-m-d H:i:s'));
            $dirty[$this->updatedAtColumn] = $this->getAttribute($this->updatedAtColumn);
        }

        // Обновляем запись
        $result = $this->newQuery()
            ->where($this->primaryKey, $this->getKey())
            ->update($dirty);

        // Если обновление успешно, синхронизируем исходные атрибуты
        if ($result) {
            $this->syncOriginal();
        }

        return $result;
    }

    /**
     * Удаление записи из базы данных
     */
    public function delete(): bool
    {
        // Проверяем наличие первичного ключа
        if (!$this->getKey()) {
            return false;
        }

        // Удаляем запись
        return $this->newQuery()
            ->where($this->primaryKey, $this->getKey())
            ->delete();
    }

    /**
     * Поиск модели по первичному ключу
     */
    public static function find($id): ?self
    {
        $instance = new static();
        $result = $instance->newQuery()
            ->where($instance->primaryKey, $id)
            ->first();

        if (!$result) {
            return null;
        }

        return (new static($result))->syncOriginal();
    }

    /**
     * Получение всех моделей
     */
    public static function all(array $columns = ['*']): array
    {
        $instance = new static();
        $results = $instance->newQuery()
            ->select($columns)
            ->get();

        $models = [];
        foreach ($results as $result) {
            $models[] = (new static($result))->syncOriginal();
        }

        return $models;
    }

    /**
     * Создание нового запроса
     */
    public static function query(): QueryBuilder
    {
        return (new static())->newQuery();
    }

    /**
     * Создание новой модели с указанными атрибутами
     */
    public static function create(array $attributes): self
    {
        $model = new static($attributes);
        $model->save();

        return $model;
    }

    /**
     * Магический метод для доступа к атрибутам
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Магический метод для установки атрибутов
     */
    public function __set(string $key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Магический метод для проверки существования атрибута
     */
    public function __isset(string $key): bool
    {
        return $this->hasAttribute($key);
    }

    /**
     * Магический метод для удаления атрибута
     */
    public function __unset(string $key)
    {
        unset($this->attributes[$key]);
    }

    /**
     * Магический метод для преобразования в строку
     */
    public function __toString(): string
    {
        return json_encode($this->attributes, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Преобразование модели в массив
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Преобразование модели в JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}