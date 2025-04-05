<?php

use SwooleAPI\Core\Application;
use SwooleAPI\Core\Container;
use SwooleAPI\Http\Response;
use SwooleAPI\Exceptions\HttpException;

if (!function_exists('app')) {
    /**
     * Получение экземпляра приложения или сервиса из контейнера
     *
     * @param string|null $abstract Имя сервиса
     * @return mixed|Application
     */
    function app(?string $abstract = null)
    {
        $app = Application::getInstance();

        if ($abstract === null) {
            return $app;
        }

        return $app->getContainer()->get($abstract);
    }
}

if (!function_exists('container')) {
    /**
     * Получение экземпляра контейнера
     *
     * @return Container
     */
    function container(): Container
    {
        return app()->getContainer();
    }
}

if (!function_exists('config')) {
    /**
     * Получение значения конфигурации
     *
     * @param string $key Ключ
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    function config(string $key, $default = null)
    {
        return app('SwooleAPI\Core\Config')->get($key, $default);
    }
}

if (!function_exists('env')) {
    /**
     * Получение значения переменной окружения
     *
     * @param string $key Имя переменной
     * @param mixed $default Значение по умолчанию
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }

        return $value;
    }
}

if (!function_exists('base_path')) {
    /**
     * Получение пути к корневой директории приложения
     *
     * @param string $path Относительный путь
     * @return string
     */
    function base_path(string $path = ''): string
    {
        return rtrim(config('app.base_path', dirname(__DIR__, 2)), '/') . ($path ? '/' . $path : '');
    }
}

if (!function_exists('storage_path')) {
    /**
     * Получение пути к директории хранилища
     *
     * @param string $path Относительный путь
     * @return string
     */
    function storage_path(string $path = ''): string
    {
        return base_path('storage') . ($path ? '/' . $path : '');
    }
}

if (!function_exists('public_path')) {
    /**
     * Получение пути к публичной директории
     *
     * @param string $path Относительный путь
     * @return string
     */
    function public_path(string $path = ''): string
    {
        return base_path('public') . ($path ? '/' . $path : '');
    }
}

if (!function_exists('response')) {
    /**
     * Создание объекта ответа
     *
     * @param mixed $content Содержимое
     * @param int $status Статус код
     * @param array $headers Заголовки
     * @return Response
     */
    function response($content = '', int $status = 200, array $headers = []): Response
    {
        $response = new Response();
        
        if (!empty($headers)) {
            $response->setHeaders($headers);
        }
        
        $response->setStatusCode($status);
        
        if (!empty($content)) {
            if (is_array($content) || is_object($content)) {
                return $response->json($content);
            }
            
            return $response->write((string)$content);
        }
        
        return $response;
    }
}

if (!function_exists('json')) {
    /**
     * Создание JSON ответа
     *
     * @param mixed $data Данные
     * @param int $status Статус код
     * @param array $headers Заголовки
     * @return Response
     */
    function json($data, int $status = 200, array $headers = []): Response
    {
        return response()->setStatusCode($status)
            ->setHeaders($headers)
            ->json($data);
    }
}

if (!function_exists('abort')) {
    /**
     * Прерывание выполнения с HTTP-исключением
     *
     * @param int $code Код статуса
     * @param string $message Сообщение
     * @param array $headers Заголовки
     * @throws HttpException
     */
    function abort(int $code, string $message = '', array $headers = []): void
    {
        throw new HttpException($code, $message, $headers);
    }
}

if (!function_exists('view')) {
    /**
     * Рендеринг представления
     *
     * @param string $view Имя представления
     * @param array $data Данные
     * @param int $status Статус код
     * @return Response
     */
    function view(string $view, array $data = [], int $status = 200): Response
    {
        $viewPath = config('view.path', base_path('resources/views'));
        $viewExtension = config('view.extension', '.php');
        
        $viewFile = $viewPath . '/' . str_replace('.', '/', $view) . $viewExtension;
        
        if (!file_exists($viewFile)) {
            abort(404, "View {$view} not found");
        }
        
        // Извлекаем переменные из массива данных
        extract($data);
        
        // Запускаем буферизацию вывода
        ob_start();
        
        // Подключаем файл представления
        include $viewFile;
        
        // Получаем содержимое буфера и очищаем его
        $content = ob_get_clean();
        
        // Возвращаем ответ с HTML
        return response()->setStatusCode($status)
            ->html($content);
    }
}

if (!function_exists('redirect')) {
    /**
     * Перенаправление на другой URL
     *
     * @param string $url URL
     * @param int $status Статус код
     * @return Response
     */
    function redirect(string $url, int $status = 302): Response
    {
        return response()->redirect($url, $status);
    }
}

if (!function_exists('url')) {
    /**
     * Генерация полного URL
     *
     * @param string $path Путь
     * @return string
     */
    function url(string $path = ''): string
    {
        $baseUrl = config('app.url', 'http://localhost');
        
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * Генерация URL для статического ресурса
     *
     * @param string $path Путь
     * @return string
     */
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('dd')) {
    /**
     * Дамп данных и завершение скрипта
     *
     * @param mixed ...$vars Переменные для вывода
     * @return void
     */
    function dd(...$vars): void
    {
        foreach ($vars as $var) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
        }
        die(1);
    }
}

if (!function_exists('value')) {
    /**
     * Возвращает значение переменной или результат вызова замыкания
     *
     * @param mixed $value Значение или замыкание
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('retry')) {
    /**
     * Повторение выполнения замыкания при возникновении ошибок
     *
     * @param int $times Количество попыток
     * @param callable $callback Callback-функция
     * @param int $sleep Пауза между попытками в миллисекундах
     * @return mixed
     * @throws \Exception
     */
    function retry(int $times, callable $callback, int $sleep = 0)
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $times) {
            try {
                return $callback($attempts);
            } catch (\Exception $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts < $times && $sleep) {
                    usleep($sleep * 1000);
                }
            }
        }

        throw $lastException;
    }
}

if (!function_exists('now')) {
    /**
     * Получение текущей даты и времени
     *
     * @param string $format Формат
     * @return string
     */
    function now(string $format = 'Y-m-d H:i:s'): string
    {
        return (new \DateTime())->format($format);
    }
}

if (!function_exists('collect')) {
    /**
     * Создание коллекции из массива
     *
     * @param array $items Элементы
     * @return array
     */
    function collect(array $items = []): array
    {
        return $items;
    }
}

if (!function_exists('logger')) {
    /**
     * Запись в лог
     *
     * @param string $message Сообщение
     * @param array $context Контекст
     * @param string $level Уровень
     * @return void
     */
    function logger(string $message, array $context = [], string $level = 'info'): void
    {
        $logger = app('SwooleAPI\Core\Logger');
        $logger->$level($message, $context);
    }
}