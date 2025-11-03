<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

class RouteMatcher {

    public function __construct(private array $routes) {
    }

    public function match(string $path):array {
        $match_params = [];
        foreach ($this->routes as $route) {
            $pattern = '#^' . $route["regex"] . '$#';
            if (preg_match($pattern, $path, $match_params)) {
                return [$route, $match_params];
            }
        }
        return [null, []];
    }

}
