<?php
namespace Flender\Dash\Attributes;

use Flender\Dash\Enums\Method;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Route {

    private Method $method;
    private string $path;
    private $callback;

    public function __construct(Method $method, string $path, callable $callback) {
        $this->method = $method;
        // $this->path = $path;
        $this->callback = $callback;

        // path to regex
        // Use reflection to get method parameters and match them if string/int/float...
        $routeReflexion = new \ReflectionFunction($callback);
        $change_type_to_regex = [];
        foreach ($routeReflexion->getParameters() as $param) {
            $regex = match ($param->getType()?->getName()) {
                'int' => '(\d+)',
                'float' => '([\d.]+)',
                'string' => '([^/]+)',
                default => null
            };
            if ($regex) {
                $change_type_to_regex[$param->getName()] = $regex;
            }
        }

        $this->path = preg_replace_callback('/\{(\w+)\}/', function($matches) use ($change_type_to_regex) {
            $param_name = $matches[1];
            return $change_type_to_regex[$param_name];
        }, $path);
    }

    public function get_path(): string {
        return $this->path;
    }

    public function get_method(): Method {
        return $this->method;
    }
    public function get_callback(): callable {
        return $this->callback;
    }

}
