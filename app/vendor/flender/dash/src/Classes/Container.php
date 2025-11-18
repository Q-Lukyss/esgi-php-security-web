<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

class Container
{
    public function __construct(private array $container = [])
    {
    }

    function set(string $key, $data)
    {
        $this->container[$key] = $data;
    }

    function add_multiple(array $data)
    {
        $this->container = [
            ...$data,
            ...$this->container
        ];
    }

    function get(string $key)
    {
        $value = $this->container[$key] ?? NULL;
        if ($value === NULL) {
            return NULL;
        }
        if (is_callable($value)) {
            $entity = $value();
            $this->set($key, $entity);
            return $entity;
        } else {
            return $value;
        }
    }
}