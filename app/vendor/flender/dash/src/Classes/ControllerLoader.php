<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use Flender\Dash\Attributes\Route;

class ControllerLoader
{
    public function __construct(private ?ILogger $logger) {}

    public function get_routes_from_cache(string $file_path): array
    {
        if (!is_file($file_path)) {
            $this->logger->warning("Router cache not found", [
                "file" => $file_path,
            ]);
            return [];
        }
        $content = file_get_contents($file_path);
        $json = json_decode($content, true);
        if (json_last_error()) {
            $this->logger->warning("Router cache decode error", [
                "jso_error" => json_last_error_msg(),
            ]);
            return [];
        }
        return $json;
    }

    public function get_routes_from_directory(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException(
                message: "Directory $directory does not exist.",
            );
        }

        $files = glob($directory . "/*.php");
        return array_reduce(
            $files,
            function ($routes, $file) {
                $file_name = pathinfo($file, PATHINFO_FILENAME);
                $class_namespace = "App\\Controllers\\" . $file_name;

                if (class_exists($class_namespace)) {

                    $routes_from_controller = $this->get_routes_from_controller(
                        $class_namespace,
                    );

                    foreach ($routes_from_controller as $route) {
                        [$regex, $parameters] = $route->get_config();
                        $path = $route->get_path();
                        if (!key_exists($path, $routes)) {
                            $routes[$path] = [
                                "regex" => $regex,
                                "methods" => [],
                            ];
                        }
                        $routes[$path]["methods"][
                            $route->get_method()->value
                        ] = [
                            "callback" => $route->get_callback(),
                            "parameters" => $parameters,
                            "middlewares" => $route->get_middlewares(),
                        ];
                    }
                }
                return $routes;
            },
            [],
        );
    }

    private function get_routes_from_controller(string $controller): array
    {
        $reflexion = new \ReflectionClass($controller);
        $routes = [];
        foreach ($reflexion->getMethods() as $method) {
            $attributes = $method->getAttributes(Route::class);
            foreach ($attributes as $attribute) {
                /** @var Route $routeInstance */
                $routeInstance = $attribute->newInstance();
                $routeInstance->set_callback([
                    $reflexion->getName(),
                    $method->getName(),
                ]);
                $routes[] = $routeInstance;
            }
        }
        return $routes;
    }
}
