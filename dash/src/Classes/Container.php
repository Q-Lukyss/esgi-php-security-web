<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use Closure;
use Exception;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionFunction;

class Container
{
    private array $external_data = [];

    public function __construct(private array $container = []) {}

    public function set(string $key, $data)
    {
        $this->container[$key] = $data;
    }

    public function set_external_data(array $external_data)
    {
        $this->external_data = $external_data;
    }

    public function add_multiple(array $data)
    {
        $this->container = [...$data, ...$this->container];
    }

    /**
     * Get a value from the container by key.
     * @param string $key The key. It is often the class name: `App\MyClass::class`
     * @return mixed|null
     */
    public function get(string $key)
    {
        $value = $this->container[$key] ?? null;

        // If value existe and is callable, call it and store the result in the container
        if ($value !== null) {
            if (is_callable($value)) {
                $entity = $value();
                $this->set($key, $entity);
                return $entity;
            } else {
                return $value;
            }
        }

        // If the key is a class name, create an instance of it
        // Cannot be done with `new $key` because class can have parameters. E.g if multiple class need the ILogger
        if (class_exists($key)) {
            return $this->create_class($key);
        }

        return null;
    }

    /**
     * Call a function and return the result.
     * The goal is to use instance in the container as parameter or $args as parameters of the function.
     *
     * @template T
     * @param callable(): T $callback
     * @param array<string, mixed> $args [name => value]
     * @return T
     */
    public function call(
        mixed $callback,
        array $args = [],
        ?array $template = null,
    ) {
        // Handle [MyClass::class, 'myMethod']
        // var_dump($args);

        if (is_array($callback) && count($callback) === 2) {
            if (is_string($callback[0]) && class_exists($callback[0])) {
                // Instantiate class using container
                $callback[0] = $this->get($callback[0]);
            }
            if (!is_callable($callback)) {
                throw new InvalidArgumentException(
                    'Le tableau fourni n\'est pas callable.',
                );
            }
        }

        if (!is_callable($callback)) {
            throw new InvalidArgumentException(
                'Le callback fourni n\'est pas callable.',
            );
        }

        $closure = Closure::fromCallable($callback);
        $resolved = [];

        // If template is not defined, use the Reflection
        // Template is used by cache to avoid reflection each time
        if ($template === null) {
            $reflector = new ReflectionFunction($closure);
            $template = array_map(
                fn($it) => [$it->getName(), $it->getType()->getName()],
                $reflector->getParameters(),
            );
        }

        // For each argument, get from $args or the container
        foreach ($template as [$name, $type]) {
            // Is a matched parameter
            if (array_key_exists($name, $args)) {
                $resolved[] = $args[$name];
            } elseif ($type !== null) {
                $value = $this->get($type);
                if ($value === null) {
                    throw new Exception(
                        "Cannot resolve type \${$type}: no Entity/Service or Class in the Container.",
                    );
                }
                $resolved[] = $value;
            } else {
                throw new Exception(
                    "Cannot resolve parameter \${$name}: no type hint and not in args.",
                );
            }
        }

        return \call_user_func_array($closure, $resolved);
    }

    /**
     * Create a new instance of the given class using the container to get class parameters.
     * @param string $class
     * @return object
     */
    private function create_class(string $class)
    {
        $reflector = new ReflectionClass($class);
        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();
            if (array_key_exists($name, $this->external_data)) {
                $dependencies[] = $this->external_data[$name];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } elseif ($type !== null) {
                $value = $this->get($type->getName());
                if ($value === null) {
                    throw new Exception(
                        "Cannot resolve parameter {$parameter->getName()} ({$type->getName()}).",
                    );
                }
                $dependencies[] = $value;
            } else {
                throw new Exception(
                    "Cannot resolve parameter {$parameter->getName()}: no type hint.",
                );
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
