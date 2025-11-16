<?php
namespace Flender\Dash\Attributes;

use Exception;
use Flender\Dash\Enums\Method;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Route {

    public function __construct(private Method $method, private string $path, private $callback = null, private array $middlewares = []) {
    }

    public function get_path(): string {
        return $this->path;
    }

    public function get_middlewares(): array {
        return array_map(function($it) {
            if (!class_exists($it)) {
                throw new Exception("Class $it does not exist.");
            }
            $rc = new \ReflectionClass($it);
            // Get method 'handle'
            if (!$rc->hasMethod('handle')) {
               throw new Exception("Class $it do not implement IMiddleware");
            }
            $rm = $rc->getMethod('handle');
            return 
            
            [
                "callback" => [ $it, "handle" ],
                "parameters" => array_map(function($param) {
                    return [ 
                        $param->getName(),
                        $param->getType()?->getName()
                    ];
                }, $rm->getParameters())
            ];

        }, $this->middlewares);
    }

    public function set_callback($callback):self {
        $this->callback = $callback;
        return $this;
    }

    public function get_config(): array {
        if (!is_array($this->callback)) {
            $routeReflexion = new \ReflectionFunction($this->callback);
        } else {
            $routeReflexion = new \ReflectionClass($this->callback[0]);
            $routeReflexion = $routeReflexion->getMethod($this->callback[1]);
        }
        $change_type_to_regex = [];
        foreach ($routeReflexion->getParameters() as $param) {
            $regex = match ($param->getType()?->getName()) {
                'int' => '(\d+)',
                'float' => '([\d.]+)',
                'string' => '([^/]+)',
                default => '([^/]+)'
            };
            $change_type_to_regex[$param->getName()] = $regex;
        }

        $regex =  preg_replace_callback('/:(\w+)/', function($matches) use ($change_type_to_regex) {
            $param_name = $matches[1];
            return $change_type_to_regex[$param_name];
        }, $this->path);

        $parameters = array_map(fn($param) => [
            $param->getName(), $param->getType()?->getName() ?? 'string'
        ], $routeReflexion->getParameters());

        return [$regex, $parameters];
    }

    public function get_method(): Method {
        return $this->method;
    }
    public function get_callback() {
        return $this->callback;
    }

}
