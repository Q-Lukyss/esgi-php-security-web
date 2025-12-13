<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use Flender\Dash\Enums\Method;

class RouteScheme
{
    public function __construct(
        public Method $method,
        public array $middlewares,
        public mixed $callback,
        public array $parameters,
        public array $permissions,
    ) {}

    /**
     * Summary of fromArray
     * @param array $data
     * @return array<string, array<RouteScheme>>
     */
    public static function fromArray(array $array)
    {
        $out = [];
        foreach ($array as $regex => $endpoint) {
            $out[$regex] = [];
            foreach ($endpoint as $method => $route) {
                $method = Method::tryFrom($method);
                $out[$regex][$method->value] = new RouteScheme(
                    $method,
                    $route["middlewares"],
                    $route["callback"],
                    $route["parameters"],
                    $route["permissions"],
                );
            }
        }
        return $out;
    }

    public function get_arguments(array $args): array
    {
        $params = [];
        $parameters = $this->parameters;
        $tmp_i = 0;
        for ($i = 0; $i < \count($parameters); $i++) {
            $param = $parameters[$i] ?? null;
            // var_dump("val", $param);
            if ($param !== null && \in_array($param[1], ["int", "string"])) {
                [$name, $type] = $parameters[$i];
                $params[$name] = $args[$tmp_i];
                $tmp_i++;
            }
        }
        // var_dump($params);
        return $params;
    }

    /**
     * Summary of toArray
     * @param array<string, array<RouteScheme>> $routeSchemes
     * @return array
     */
    public static function toArray(array $routeSchemes): array
    {
        $out = [];
        foreach ($routeSchemes as $regex => $routes) {
            $out[$regex] = [];
            foreach ($routes as $route) {
                $out[$regex][$route->method->value] = [
                    "middlewares" => $route->middlewares,
                    "callback" => $route->callback,
                    "parameters" => $route->parameters,
                    "permissions" => $route->permissions,
                ];
            }
        }
        return $out;
    }
}
