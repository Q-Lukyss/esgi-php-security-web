<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use Flender\Dash\Enums\Method;


class RouteScheme
{
    public function __construct(public Method $method, public array $middlewares, public array $callback, public array $parameters)
    {
    }

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
                $out[$regex][$method->value] = new RouteScheme($method, $route["middlewares"], $route["callback"], $route["parameters"]);
            }
        }
        return $out;
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
                    "parameters" => $route->parameters
                ];
            }

        }
        return $out;
    }
}