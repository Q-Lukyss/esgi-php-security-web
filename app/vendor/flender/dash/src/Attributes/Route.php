<?php
namespace Flender\Dash\Attributes;

use Exception;
use Flender\Dash\Enums\Method;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Route
{

    private array $callback = [];
    private ?string $regex = null;
    private ?array $params = null;

    public function __construct(private Method $method, private string $path, private array $middlewares = [], private ?object $rate_limiter = null, private array $permissions = [])
    {
    }

    public function get_regex(): string
    {
        if ($this->regex === NULL) {
            $this->apply_config();
        }
        return $this->regex;
    }

    public function get_parameters(): array
    {
        if ($this->params === NULL) {
            $this->apply_config();
        }
        return $this->params;
    }

    private function apply_config()
    {
        [$regex, $params] = $this->get_config();
        $this->regex = $regex;
        $this->params = $params;
    }

    public function get_path(): string
    {
        return $this->path;
    }

    public function get_permissions(): array
    {
        return $this->permissions;
    }

    public function get_middlewares(): array
    {
        return array_map(function ($it) {

            // If it's already initialized
            if (is_string($it) === false) {
                return [$it, "__invoke"];
            }

            if (!class_exists($it)) {
                throw new Exception("Class $it does not exist.");
            }
            $rc = new \ReflectionClass($it);
            // Get method 'handle'
            if (!$rc->hasMethod('__invoke')) {
                throw new Exception("Class $it do not implement __invoke");
            }
            return [$it, "__invoke"];

        }, $this->middlewares);
    }

    public function set_callback(array $callback): self
    {
        // Add some tests
        $this->callback = $callback;
        return $this;
    }

    private function get_config(): array
    {
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

        $regex = preg_replace_callback('/:(\w+)/', function ($matches) use ($change_type_to_regex) {
            $param_name = $matches[1];
            return $change_type_to_regex[$param_name];
        }, $this->path);

        $parameters = array_map(fn($param) => [
            $param->getName(),
            $param->getType()?->getName() ?? 'string'
        ], $routeReflexion->getParameters());

        return [$regex, $parameters];
    }

    public function get_method(): Method
    {
        return $this->method;
    }
    public function get_callback()
    {
        return $this->callback;
    }

}
