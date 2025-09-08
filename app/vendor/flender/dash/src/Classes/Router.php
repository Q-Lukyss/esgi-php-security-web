<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use Flender\Dash\Attributes\Route;
use Flender\Dash\Enums\Method;
use Flender\Dash\Response\Response;

class Router {

    private array $routes;
    private string $base_path;
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

    public function run(): void {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        if ($this->base_path && str_starts_with($path, $this->base_path)) {
            $path = substr($path, strlen($this->base_path));
        }

        var_dump($path, $method);
        var_dump($this->routes['routes']);

        $handler = $this->routes[self::NOT_FOUND_ROUTE];
        $match_params = [];

        foreach ($this->routes["routes"] as $route_path => $methods) {
            $pattern = '#^' . $route_path . '$#';
            var_dump("Pattern:", $pattern);
            if (preg_match($pattern, $path, $match_params)) {
                var_dump("Matched", $match_params);
                $handler = $methods[$method];
                break;
            }
        }

        $response = $handler();
        if ($response instanceof Response) {
            $response->send();
            return;
        }
        echo $response;
        
    }
    public function set_base_path(string $base_path):self {
        $this->base_path = $base_path;
        return $this;
    }

    // Cache route file + pack/unpack ?

    private function register_route(Route $route) {
        $this->routes["routes"][$route->get_path()][$route->get_method()->value] = $route->get_callback();
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