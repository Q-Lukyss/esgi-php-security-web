<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use Closure;
use Composer\Autoload\ClassLoader;
use Flender\Dash\Attributes\Route;
use Flender\Dash\Enums\Method;
use Flender\Dash\Response\Response;
use ReflectionClass;

class Router {

    private array $routes;
    private array $container = [];
    private string $base_path = '';
    private string $static_path;
    private string $cache_router = '';
    private bool $used_cache = false;
    
    public static string $APP_BASE = '';
    public static string $TEMPLATES_DIRECTORY = '';
    public static string $CONTROLLER_DIRECTORY = '';
    private bool $debug = false;

    private const ERROR_ROUTE = "error";
    private const NOT_FOUND_ROUTE = "404";
    

    public function __construct(
    ) {
        $this->routes = [
            "routes" => [],
            self::ERROR_ROUTE => fn() => new Response('Enternal Server Error', 500),
            self::NOT_FOUND_ROUTE => fn() => new Response('Not Found', 404)
        ];

        // Set default paths using composer autoload
        $vender_dir = dirname((new ReflectionClass(ClassLoader::class))->getFileName());
        self::$APP_BASE = dirname($vender_dir, 2);
        $src_dir = self::$APP_BASE . DIRECTORY_SEPARATOR . 'src';
        self::$TEMPLATES_DIRECTORY = $src_dir . DIRECTORY_SEPARATOR . 'templates';
        self::$CONTROLLER_DIRECTORY = $src_dir . DIRECTORY_SEPARATOR . 'Controllers';
    }

    public function get_routes_from_controller(string $controller): array {
        $reflexion = new \ReflectionClass($controller);
        $routes = [];
        foreach ($reflexion->getMethods() as $method) {
            $attributes = $method->getAttributes(Route::class);
            foreach ($attributes as $attribute) {
                /** @var Route $routeInstance */
                $routeInstance = $attribute->newInstance();
                $routeInstance->set_callback([$reflexion->getName(), $method->getName()]);
                $routes[] = $routeInstance;
            }
        }
        return $routes;
    }

    public function set_cache_router(string $path):self {
        $this->cache_router = self::$APP_BASE . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
        if (file_exists($this->cache_router)) {
            $this->routes = json_decode(file_get_contents($this->cache_router), true);
            $this->used_cache = true;
        }
        return $this;
    }

    public function set_controllers_directory(string $directory = null):self {
        if ($this->used_cache) {
            $this->log("Using cached routes, skipping controller directory scan.");
            return $this;
        }

        if ($directory === null) {
            $directory = self::$CONTROLLER_DIRECTORY;
        } else {
            $directory = self::$APP_BASE . DIRECTORY_SEPARATOR . ltrim($directory, '/\\');
        }


        if (!is_dir($directory)) {
            throw new \InvalidArgumentException("Directory $directory does not exist.");
        }
        $files = glob($directory . '/*.php');
        foreach ($files as $file) {
            $class_name = pathinfo($file, PATHINFO_FILENAME);
            $full_class_name = "App\\Controllers\\$class_name";
            if (class_exists($full_class_name)) {
                $routes = $this->get_routes_from_controller($full_class_name);
                foreach ($routes as $route) {
                    $this->register_route($route);
                }
            }
        }
        return $this;
    }

    public function set_debug(bool $debug = true):self {
        $this->debug = $debug;
        return $this;
    }

    private function log(string $message):void {
        if ($this->debug) {
            echo "<pre style='background:#333;color:#0f0;padding:10px;'>" . htmlspecialchars($message) . "</pre>";
        }
    }

    public function run(): void {

        // Enable error reporting in debug mode
        if ($this->debug) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        }

        if ($this->cache_router && !$this->used_cache) {
            file_put_contents($this->cache_router, json_encode($this->routes));
        }

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        if ($this->base_path && str_starts_with($path, $this->base_path)) {
            $path = substr($path, strlen($this->base_path));
        }

        $handler = $this->routes[self::NOT_FOUND_ROUTE];
        $match_params = [];

        foreach ($this->routes["routes"] as $route) {
            $route_path = $route["regex"];
            $pattern = '#^' . $route_path . '$#';
            $this->log("Trying pattern: $pattern with path: $path");
            if (preg_match($pattern, $path, $match_params)) {
                $handler = $route["methods"][$method] ?? null;

                if (!$handler) {
                    // Return 405 Method Not Allowed
                    $handler = $this->routes[self::ERROR_ROUTE];
                }

                if (is_array($handler) && is_string($handler[0]) && class_exists($handler[0]) && method_exists($handler[0], $handler[1])) {
                    [$class, $method] = $handler;
                    $instance = new $class();
                    $match_params = [...array_slice($match_params, 1)];
                    $this->log("Matched raw parameters: " . json_encode($match_params));

                    // Match parameters with types
                    $params_info = $route["parameters"];
                    $this->log("Parameters info: " . json_encode($params_info));
                    $typed_params = [];
                    $id = 0;
                    foreach ($params_info as [$name, $type]) {
                        $this->log("Processing parameter: $name of type $type");
                        if ($type === 'string' || $type === 'int' || $type === 'float') {
                            $value = $match_params[$id++] ?? null;
                        } else {
                            $value = null;
                        }
                        if ($value === null) {
                            continue;
                        }
                        $out = settype($value, $type);
                        if ($out === false) {
                            $this->log("Failed to set type for parameter: $name with value: $value to type: $type");
                            continue;
                        }

                        $typed_params[$name] = $value;
                    }

                    $this->log("Typed parameters before container: " . json_encode($typed_params));

                    // Add from container for method parameters not in route
                    if (count($typed_params) < count($params_info)) {
                        foreach ($params_info as [$name, $type]) {
                            if (!array_key_exists($name, $typed_params) && isset($this->container[$type])) {
                                $container_value = $this->container[$type];
                                if (is_callable($container_value)) {
                                    $typed_params[$name] = $container_value();
                                } else {
                                    $typed_params[$name] = $container_value;
                                }
                            }
                        }
                    }

                    $this->log("Matched parameters: " . json_encode($typed_params));

                    $handler = $instance->$method(...$typed_params);
                    if ($handler instanceof Response) {
                        $handler->send();
                        return;
                    }
                    echo $handler;
                    return;
                }


                // Transform $match_params to typed values

                // Stocker callback || (class name + method)

                // Add values from container if needed
                break;
            }
        }

        // Send 404
        if (!$handler) {
            $handler = $this->routes[self::NOT_FOUND_ROUTE];
        }

        if (is_callable($handler)) {
            $response = $handler(...array_values($match_params));
            if ($response instanceof Response) {
                $response->send();
                return;
            }
            echo $response;
            return;
        }

        // $response = $handler(...array_values($match_params));
        // if ($response instanceof Response) {
        //     $response->send();
        //     return;
        // }
        // echo $response;
        
    }

    public function set_container(array $container):self {
        $this->container = $container;
        return $this;
    }
    public function set_base_path(string $base_path):self {
        $this->base_path = $base_path;
        return $this;
    }

    private function register_route(Route $route) {
        if (!$route->get_callback()) {
            throw new \InvalidArgumentException("Route must have a callback.");
        }
        $path = $route->get_path();
        $base = &$this->routes["routes"];
        
        // If route path does not exist, create it
        if (!is_array($base[$path] ?? null)) {
            [$regex, $parameters] = $route->get_config();
            $methods = [];
            $base[$path] = compact('regex', 'parameters', 'methods');
        }

        // If method already exists for this path, throw error
        if (isset($base[$path]["methods"][$route->get_method()->value])) {
            throw new \InvalidArgumentException("Route $path already exists for method " . $route->get_method()->value);
        }

        $base[$path]["methods"][$route->get_method()->value] = $route->get_callback();
    }

    public function get(string $path, callable|array $callback):self {
        $this->register_route(new Route(Method::GET, $path, $callback));
        return $this;
    }

    public function set_404_callback(callable|array $callback):self {
        $this->routes[self::NOT_FOUND_ROUTE] = $callback;
        return $this;
    }

    public function set_error_callback(callable|array $callback):self {
        $this->routes[self::ERROR_ROUTE] = $callback;
        return $this;
    }

    public function set_static_path(string $path):self {
        $this->static_path = $path;
        return $this;
    }
    

}