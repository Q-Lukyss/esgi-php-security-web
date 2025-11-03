<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use Exception;
use ReflectionClass;

class EnvLoader {
    
    private static function get_env_file_name(string $dir, ?string $environment = null):string {
        if ($environment !== null) {
            return $dir . DIRECTORY_SEPARATOR . '.env.' . $environment;
        }
        return $dir . DIRECTORY_SEPARATOR . '.env';
    }

    private static function get_vars_from_file_name(string $file): array {
        if (!file_exists($file)) {
            return [];
        }
        $env = parse_ini_file($file);
        return $env;
    }    
    /**
     * @template T of object
     * @param class-string<T>|null $env_class
     * @return T|array<string,T>
     */
    public static function get_env(string $dir, ?string $env_class = null, ?string $environment = null)  {
        
        $env_file = static::get_env_file_name($dir);
        $env = static::get_vars_from_file_name($env_file);

        $env_environment_file = static::get_env_file_name($dir, $environment);
        $env_environment = static::get_vars_from_file_name($env_environment_file);

        $env = [
            ...$env,
            ...$env_environment
        ];

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
