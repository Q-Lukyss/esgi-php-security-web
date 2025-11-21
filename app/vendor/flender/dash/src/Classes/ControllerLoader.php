<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use Flender\Dash\Attributes\Route;

class ControllerLoader
{
    public function __construct(private ?ILogger $logger)
    {
    }

    /**
     * 
     * @param string $file_path
     * @return RouterTree|null
     */
    public function get_router_tree_from_cache(string $file_path)
    {
        if (!is_file($file_path)) {
            $this->logger->warning("Router cache not found", [
                "file" => $file_path,
            ]);
            return null;
        }
        $content = file_get_contents($file_path);
        $json = json_decode($content, true);
        if (json_last_error()) {
            $this->logger->warning("Router cache decode error", [
                "json_error" => json_last_error_msg(),
            ]);
            return null;
        }

        $schemes = RouteScheme::fromArray($json);
        return new RouterTree($schemes);
    }

    /**
     * 
     * @param string $directory
     * @throws \InvalidArgumentException
     * @return RouterTree
     */
    public function get_router_tree_from_directory(string $directory)
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException(
                message: "Directory $directory does not exist.",
            );
        }

        $files = glob($directory . "/*.php");
        return new RouterTree(array_reduce(
            $files,
            function ($routes, $file) {
                $file_name = pathinfo($file, PATHINFO_FILENAME);
                $class_namespace = "App\\Controllers\\" . $file_name;

                if (class_exists($class_namespace)) {

                    $routes_from_controller = $this->get_routes_from_controller(
                        $class_namespace,
                    );

                    foreach ($routes_from_controller as $route) {
                        $regex = $route->get_regex();
                        if (array_key_exists($regex, $routes) === false) {
                            $routes[$regex] = [];
                        }

                        $routes[$regex][$route->get_method()->value] = new RouteScheme($route->get_method(), $route->get_middlewares(), $route->get_callback(), $route->get_parameters(), $route->get_permissions());
                    }
                }
                return $routes;
            },
            [],
        ));
    }

    /**
     * Summary of get_routes_from_controller
     * @param string $controller
     * @return Route[]
     */
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
