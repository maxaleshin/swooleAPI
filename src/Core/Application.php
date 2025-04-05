<?php

namespace SwooleAPI\Core;

use Swoole\Http\Server;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use SwooleAPI\Http\Request;
use SwooleAPI\Http\Response;
use SwooleAPI\Routing\AttributeRouteCollector;
use SwooleAPI\Routing\Router;

class Application
{
    private Server $server;
    private Container $container;
    private Router $router;
    private Config $config;
    private Logger $logger;
    private static ?self $instance = null;

    public function __construct(array $config = [])
    {
        $this->container = new Container();
        $this->config = new Config($config);
        $this->logger = new Logger();
        
        // Регистрируем базовые сервисы
        $this->container->singleton(Config::class, fn() => $this->config);
        $this->container->singleton(Logger::class, fn() => $this->logger);
        $this->container->singleton(Router::class, fn() => new Router());
        $this->container->singleton(Container::class, fn() => $this->container);
        $this->container->singleton(Application::class, fn() => $this);
        
        $this->router = $this->container->get(Router::class);
        
        self::$instance = $this;
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Запуск HTTP-сервера на указанном хосте и порту
     */
    public function run(string $host = '0.0.0.0', int $port = 8000): void
    {
        $this->server = new Server($host, $port);
        
        // Настройка сервера
        $this->server->set([
            'worker_num' => $this->config->get('server.workers', swoole_cpu_num()),
            'max_request' => $this->config->get('server.max_requests', 10000),
            'enable_coroutine' => true,
            'log_level' => SWOOLE_LOG_INFO,
        ]);
        
        // Регистрация обработчиков запросов
        $this->server->on('start', [$this, 'onStart']);
        $this->server->on('request', [$this, 'onRequest']);
        
        // Собираем маршруты на основе атрибутов
        $routeCollector = new AttributeRouteCollector($this->router);
        $routeCollector->collect($this->config->get('app.controllers_path', []));
        
        // Запуск сервера
        $this->logger->info("Server started at http://{$host}:{$port}");
        $this->server->start();
    }
    
    /**
     * Обработчик запуска сервера
     */
    public function onStart(Server $server): void
    {
        $this->logger->info('Swoole HTTP server started successfully');
    }
    
    /**
     * Обработчик HTTP-запросов
     */
    public function onRequest(SwooleRequest $swooleRequest, SwooleResponse $swooleResponse): void
    {
        // Преобразуем Swoole запрос/ответ в наши объекты
        $request = new Request($swooleRequest);
        $response = new Response($swooleResponse);
        
        try {
            // Обработка запроса через маршрутизатор
            $handler = new RequestHandler($this->container);
            $handler->handle($request, $response, $this->router);
        } catch (\Throwable $e) {
            // Обработка исключений
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $response->setStatusCode(500)->json([
                'error' => $this->config->get('app.debug', false) ? $e->getMessage() : 'Internal Server Error',
            ]);
        }
    }
    
    /**
     * Получение экземпляра контейнера
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
    
    /**
     * Получение экземпляра маршрутизатора
     */
    public function getRouter(): Router
    {
        return $this->router;
    }
    
    /**
     * Регистрация сервис-провайдера
     */
    public function register(string $providerClass): self
    {
        $provider = new $providerClass($this);
        $provider->register();
        
        return $this;
    }
}