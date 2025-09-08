<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use Closure;
use Flender\Dash\Attributes\Route;
use Flender\Dash\Enums\Method;
use Flender\Dash\Response\Response;

class Router {

    private array $routes;
    private array $container = [];
    private string $base_path = '';
    private string $static_path;

    private const ERROR_ROUTE = "error";
    private const NOT_FOUND_ROUTE = "404";

    public function __construct(
    ) {
        $this->routes = [
            "routes" => [],
            self::ERROR_ROUTE => fn() => new Response('Enternal Server Error', 500),
            self::NOT_FOUND_ROUTE => fn() => new Response('Not Found', 404)
        ];
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

    public function set_controllers_directory(string $directory):self {

        // If winfows, normalize slashes
        if (DIRECTORY_SEPARATOR === '\\') {
            $directory = str_replace('/', DIRECTORY_SEPARATOR, $directory);
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

    public function run(): void {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        if ($this->base_path && str_starts_with($path, $this->base_path)) {
            $path = substr($path, strlen($this->base_path));
        }

        $handler = $this->routes[self::NOT_FOUND_ROUTE];
        $match_params = [];

        var_dump("Request:", $method, $path);

        foreach ($this->routes["routes"] as $route_path => $methods) {
            $pattern = '#^' . $route_path . '$#';
            var_dump("Pattern:", $pattern);
            if (preg_match($pattern, $path, $match_params)) {
                var_dump("Matched", $match_params);
                $handler = $methods[$method]["callback"] ?? null;

                if (!$handler) {
                    $handler = $this->routes[self::NOT_FOUND_ROUTE];
                    break;
                }

                var_dump("Handler:", $handler);
                if (is_array($handler) && is_string($handler[0]) && class_exists($handler[0]) && method_exists($handler[0], $handler[1])) {
                    [$class, $method] = $handler;
                    $instance = new $class();
                    $match_params = [...array_slice($match_params, 1)];
                    $handler = $instance->$method(...array_values($match_params));
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

    // Cache route file + pack/unpack ?

    private function register_route(Route $route) {
        $this->routes["routes"][$route->get_path()][$route->get_method()->value] = [
            "callback" => $route->get_callback(),
            "parameters" => [] // TODO: get parameters from reflection
        ];
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