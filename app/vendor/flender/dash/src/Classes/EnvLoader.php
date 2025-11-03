<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use Exception;
use ReflectionClass;

class EnvLoader {
    /**
     * @template T of object
     * @param class-string<T>|null $env_class
     * @return T|array<string,T>
     */
    public static function get_env(string $dir, ?string $env_class = null)  {
        $env_path = $dir . DIRECTORY_SEPARATOR . '.env';
        if (!file_exists($env_path)) {
            throw new Exception("This file does not exist:" . $env_path);
        }
        $env = parse_ini_file($env_path);
        if ($env_class !== null && class_exists($env_class) ) {
            $instance = new $env_class();

            $rc = new ReflectionClass($instance);
            foreach ($rc->getProperties() as $prop) {
                $env_var = $env[$prop->getName()];
                if (!$env_var && !$prop->hasDefaultValue()) {
                    throw new Exception("Missing environment variable: " . $prop->getName());
                }
                $prop->setValue($instance, $env_var);
            }


            return $instance;
        }

        return $env;
    }

}
