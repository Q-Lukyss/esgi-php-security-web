<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use Closure;
use Composer\Autoload\ClassLoader;
use Exception;
use Flender\Dash\Attributes\Route;
use Flender\Dash\Enums\Method;
use Flender\Dash\Interfaces\IVerifiable;
use Flender\Dash\Response\JsonResponse;
use Flender\Dash\Response\Response;
use Throwable;

class Router {

    /**
     * All routes of the application
     * @var array
     */
    private array $routes;

    /**
     * Storage K/V (fn) used to implement dependency injection (DI)
     * @var array
     */
    private array $container = [];

    /**
     * The base path of the application
     * Used in case if the base path is not only the origin
     * like Apache
     * @var string
     */
    private string $base_path = '';
    
    // /**
    //  * Path to k
    //  * @var string
    //  */
    // private string $static_path;
    

    /**
     * The path to the cache router, used to cache the routes of the application, parameters, etc..
     * Null by default, so don't use the cache
     * @var string
     */
    private ?string $cache_router = null;
    private bool $is_new_cache_router = false;
    
    
    /**
     * Is debug mode enabled ?
     * @var bool
     */
    private bool $debug = false;
    
    public static string $APP_BASE = '';
    public static string $TEMPLATES_DIRECTORY = '';
    public static string $CONTROLLER_DIRECTORY = '';

    private const ERROR_ROUTE = "error";
    private const NOT_FOUND_ROUTE = "404";
    

    public function __construct(
    ) {
        
        // Set default routes
        $this->routes = [
            "routes" => [],
            self::ERROR_ROUTE => fn() => new Response('Enternal Server Error', 500),
            self::NOT_FOUND_ROUTE => fn() => new Response('Not Found', 404)
        ];

        // Set default paths using composer autoload
        $this->set_static_variable();
    }

    private function get_routes_from_cache():array {
        if (!is_file($this->cache_router)) {
            throw new \InvalidArgumentException("Cache router does not exist.");
        }
        $content = file_get_contents($this->cache_router);
        $json = json_decode($content, true);
        if (json_last_error()) {
            throw new \InvalidArgumentException("Cache router is not a valid JSON.");
        }
        return $json;
    }

    private function set_static_variable() {
        $vender_directory = dirname((new \ReflectionClass(ClassLoader::class))->getFileName());
        self::$APP_BASE = dirname($vender_directory, 2);

        $src_directory = self::$APP_BASE . DIRECTORY_SEPARATOR . 'src';
        self::$TEMPLATES_DIRECTORY = $src_directory . DIRECTORY_SEPARATOR . 'templates';
        self::$CONTROLLER_DIRECTORY = $src_directory . DIRECTORY_SEPARATOR . 'Controllers';
    }

    private function get_routes_from_controller(string $controller): array {
        $reflexion = new \ReflectionClass($controller);
        $routes = [];
        foreach ($reflexion->getMethods() as $method) {
            $attributes = $method->getAttributes(Route::class);
            foreach ($attributes as $attribute) {
                /** @var Route $routeInstance */
                $routeInstance = $attribute->newInstance();
                $routeInstance->set_callback([$reflexion->getName(), $method->getName()]);
                $routes[] = $routeInstance;
            }
        }
        return $routes;
    }

    /**
     * Set the file to the cache router
     * If the file already exists, use the data to
     * populate the routes array:w
     * @param string $path
     * @throws \Exception
     * @return Router
     */
    public function set_cache_router(string $path):self {
        $path = ltrim($path, '/\\');
        $this->cache_router = self::$APP_BASE . DIRECTORY_SEPARATOR . $path;
        return $this;
    }

    private function get_routes_from_controller_directory():array {
        $directory = self::$CONTROLLER_DIRECTORY;
        
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException(message: "Directory $directory does not exist.");
        }

        $files = glob($directory . '/*.php');
        return array_reduce($files, function($routes, $file) {
            $file_name = pathinfo($file, PATHINFO_FILENAME);
            $class_namespace = "App\\Controllers\\" . $file_name;
            if (class_exists($class_namespace)) {
                $routes_from_controller = $this->get_routes_from_controller($class_namespace);

                foreach ($routes_from_controller as $route) {
                    
                    [$regex, $parameters] = $route->get_config();
                    $path = $route->get_path();
                    if (!key_exists($path, $routes)) {
                        $routes[$path] = [
                            "regex" => $regex,
                            "methods" => []
                        ];
                    }
                    $routes[$path]["methods"][$route->get_method()->value] = [
                        "callback" => $route->get_callback(),
                        "parameters" => $parameters,
                    ];
                }
            }
            return $routes;
        }, []);
    }
    public function set_controllers_directory(string $directory):self {
        self::$CONTROLLER_DIRECTORY = self::$APP_BASE . DIRECTORY_SEPARATOR . ltrim($directory, '/\\');
        return $this;
    }

    public function set_debug(bool $debug = true):self {
        $this->debug = $debug;
        if ($this->debug === true) {
            $this->enable_debug_reporting();
        }
        return $this;
    }

    private function log(string $message):void {
        if ($this->debug) {
            echo "<pre style='background:#333;color:#0f0;padding:10px;'>" . htmlspecialchars($message) . "</pre>";
        }
    }

    private function enable_debug_reporting():void {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
    }

    private function call(array|Closure $handler, array $parameters = [], array $matched_params = []) {
        // If is a array (Controller) extract the callback
        if (is_array($handler) && is_string($handler[0]) && class_exists($handler[0])) {
            [$class, $method] = $handler;
            $instance = new $class();
            $handler = [$instance, $method];
        }

        // Add needed params to container
        $needed_params = $parameters;
        $params = [];
        $id = 1;
        foreach ($needed_params as [$name, $type]) {
            $this->log("processing $name of type $type");
            if (in_array($type, ["string", "int", "float"])) {
                if (settype($matched_params[$id], $type)) {
                    $params[$name] = $matched_params[$id];
                    $id++;
                }
            } else if (str_starts_with($type, 'App\\Entity\\')) {
                if (str_starts_with($type, 'App\\Entity\\') && class_exists($type) && is_subclass_of($type, Entity::class)) {
                    // If Get request, try to get values from query parameters
                    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                        $query_params = $_GET;
                    } else {
                        // Else try to get values from body (assuming JSON)
                        $body = file_get_contents('php://input');
                        $query_params = json_decode($body, true) ?? [];
                    }
                    $entity_instance = new $type(...$query_params);
                    if (is_subclass_of($type, IVerifiable::class)) {
                        /** @var IVerifiable $entity_instance */
                        $errors = $entity_instance->verify();
                    }
                    if (count($errors) > 0) {
                        $handler = fn() => new Response('Entity validation failed: ' . implode(', ', $errors), 400);
                        continue;
                    }
                    $params[$name] = $entity_instance;
                }
            } else{
                $params[$name] = $this->container[$type]();
            }
        } 
        

        try {
            $response = $handler(...$params);
        } catch (Throwable $e) {
            // $this->container[Throwable::class] = $e;
            $this->log("error" . print_r($e, true));
            die();
            $this->call($this->routes[self::ERROR_ROUTE]);
            return;
        }
        if ($response instanceof Response) {
            $response->send();
            return;
        }
        echo $response;

    }

    public function run(): void {

        // Extract route from cache, or analyse the Controller/ directory
        // eventualy, save to cache
        if ($this->cache_router !== null && is_file($this->cache_router)) {
            $this->log("Loading routes from cache");
            $this->routes["routes"] = $this->get_routes_from_cache();
        } else {
            $this->log("Loading routes from controller directory");
            $routes_from_controller = $this->get_routes_from_controller_directory();
            $this->routes["routes"] = $routes_from_controller;
            if ($this->cache_router !== null) {
                $this->log("Saving routes to cache");
                $encoded_routes = json_encode($routes_from_controller);
                file_put_contents($this->cache_router, $encoded_routes);
                // { "regex" => "/:path", methods: { "get" => { "callback": [ "text:text" ], "parameters: []" } } }
            }
        }

        // Add Request & Response to container
        $request = Request::from_global();
        $this->container = [
            ...$this->container,
            Response::class => fn() => new Response(),
            Request::class => fn() => $request
        ];


        // Add to Container

        // $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // $method = $_SERVER['REQUEST_METHOD'];

        // if ($this->base_path && str_starts_with($path, $this->base_path)) {
        //     $path = substr($path, strlen($this->base_path));
        // }

        $route_matcher = new RouteMatcher($this->routes["routes"]);
        $path = $request->get_path();
        if ($this->base_path !== null && str_starts_with($path, $this->base_path)) {
            $path = substr($path, strlen($this->base_path));
        }

        [$route, $match_params] = $route_matcher->match($path);

        // If no route found, return 404
        if ($route === null) {
            $this->call($this->routes[self::NOT_FOUND_ROUTE]);
            return;
        }

        // If the method doesn't exist, return 405
        $r = $route["methods"][$request->get_method()] ?? null;
        
        if (!$r) {
            $this->call(fn() => new JsonResponse([ "error" => "method not allowed" ], 405));
            return;
        }

        // Finally, call the handler
        $this->call($r["callback"], $r["parameters"], $match_params);
        return;


        // $handler = $this->routes[self::NOT_FOUND_ROUTE];
        // $match_params = [];

        // foreach ($this->routes["routes"] as $route) {
        //     $route_path = $route["regex"];
        //     $pattern = '#^' . $route_path . '$#';
        //     $this->log("Trying pattern: $pattern with path: $path");
        //     if (preg_match($pattern, $path, $match_params)) {
        //         $handler = $route["methods"][$method] ?? null;

        //         if (!$handler) {
        //             // Return 405 Method Not Allowed
        //             $handler = $this->routes[self::ERROR_ROUTE];
        //         }

        //         if (is_array($handler) && is_string($handler[0]) && class_exists($handler[0]) && method_exists($handler[0], $handler[1])) {
        //             [$class, $method] = $handler;
        //             $instance = new $class();
        //             $match_params = [...array_slice($match_params, 1)];
        //             $this->log("Matched raw parameters: " . json_encode($match_params));

        //             // Match parameters with types
        //             $params_info = $route["parameters"];
        //             $this->log("Parameters info: " . json_encode($params_info));
        //             $typed_params = [];
        //             $id = 0;
        //             foreach ($params_info as [$name, $type]) {
        //                 $this->log("Processing parameter: $name of type $type");
        //                 if ($type === 'string' || $type === 'int' || $type === 'float') {
        //                     $value = $match_params[$id++] ?? null;
        //                 } else {
        //                     $value = null;
        //                 }
        //                 if ($value === null) {
        //                     continue;
        //                 }
        //                 $out = settype($value, $type);
        //                 if ($out === false) {
        //                     $this->log("Failed to set type for parameter: $name with value: $value to type: $type");
        //                     continue;
        //                 }

        //                 $typed_params[$name] = $value;
        //             }

        //             $this->log("Typed parameters before container: " . json_encode($typed_params));

        //             // Add from container for method parameters not in route
        //             if (count($typed_params) < count($params_info)) {
        //                 foreach ($params_info as [$name, $type]) {
        //                     // Get from container if not already set
        //                     if (!array_key_exists($name, $typed_params) && isset($this->container[$type])) {
        //                         $container_value = $this->container[$type];
        //                         if (is_callable($container_value)) {
        //                             $typed_params[$name] = $container_value();
        //                         } else {
        //                             $typed_params[$name] = $container_value;
        //                         }
        //                     }
        //                     // Is Entity ?
        //                     if (str_starts_with($type, 'App\\Entity\\') && class_exists($type) && is_subclass_of($type, Entity::class)) {
        //                         // If Get request, try to get values from query parameters
        //                         if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        //                             $query_params = $_GET;
        //                         } else {
        //                             // Else try to get values from body (assuming JSON)
        //                             $body = file_get_contents('php://input');
        //                             $query_params = json_decode($body, true) ?? [];
        //                         }
        //                         $entity_instance = new $type(...$query_params);
        //                         if (is_subclass_of($type, IVerifiable::class)) {
        //                             /** @var IVerifiable $entity_instance */
        //                             $errors = $entity_instance->verify();
        //                         }
        //                         if (count($errors) > 0) {
        //                             $handler = fn() => new Response('Entity validation failed: ' . implode(', ', $errors), 400);
        //                             $typed_params = [];
        //                             break 2; // Break both foreach and if
        //                         }
        //                         $typed_params[$name] = $entity_instance;
        //                     }

        //                 }
        //             }

        //             $this->log("Matched parameters: " . json_encode($typed_params));

        //             $handler = $instance->$method(...$typed_params);
        //             if ($handler instanceof Response) {
        //                 $handler->send();
        //                 return;
        //             }
        //             echo $handler;
        //             return;
        //         }


        //         // Transform $match_params to typed values

        //         // Stocker callback || (class name + method)

        //         // Add values from container if needed
        //         break;
        //     }
        // }

        // // Send 404
        // if (!$handler) {
        //     $this->log("No handler found for path: $path");
        //     $handler = $this->routes[self::NOT_FOUND_ROUTE];
        //     $this->log("Using 404 handler:". print_r($this->routes, true));
        // }

        // if (is_callable($handler)) {
        //     $response = $handler(...array_values($match_params));
        //     if ($response instanceof Response) {
        //         $response->send();
        //         return;
        //     }
        //     echo $response;
        //     return;
        // } else {
        //     $this->log("Not a callable");
        // }

        // $response = $handler(...array_values($match_params));
        // if ($response instanceof Response) {
        //     $response->send();
        //     return;
        // }
        // echo $response;
        
    }

    public function set_container(array $container):self {
        $this->container = $container;
        return $this;
    }
    public function set_base_path(string $base_path):self {
        $this->base_path = $base_path;
        return $this;
    }

    private function register_route(Route $route) {
        if (!$route->get_callback()) {
            throw new \InvalidArgumentException("Route must have a callback.");
        }
        $path = $route->get_path();
        $base = &$this->routes["routes"];
        
        // If route path does not exist, create it
        if (!is_array($base[$path] ?? null)) {
            [$regex, $parameters] = $route->get_config();
            $methods = [];
            $base[$path] = compact('regex', 'parameters', 'methods');
        }

        // If method already exists for this path, throw error
        if (isset($base[$path]["methods"][$route->get_method()->value])) {
            throw new \InvalidArgumentException("Route $path already exists for method " . $route->get_method()->value);
        }

        $base[$path]["methods"][$route->get_method()->value] = $route->get_callback();
    }

    public function get(string $path, callable|array $callback):self {
        $this->register_route(new Route(Method::GET, $path, $callback));
        return $this;
    }

    public function set_404_callback(callable|array $callback):self {
        $this->routes[self::NOT_FOUND_ROUTE] = $callback;
        return $this;
    }

    public function set_error_callback(callable|array $callback):self {
        $this->routes[self::ERROR_ROUTE] = $callback;
        return $this;
    }

    public function remove_trailing_slash(bool $remove = true):self {
        if ($remove && substr($_SERVER['REQUEST_URI'], -1) === '/' && $_SERVER['REQUEST_URI'] !== '/') {
            $new_url = rtrim($_SERVER['REQUEST_URI'], '/');
            if ($this->base_path && !str_starts_with($new_url, $this->base_path)) {
                $new_url = $this->base_path . $new_url;
            }
            header("Location: $new_url", true, 301);
            exit();
        }
        return $this;
    }

    public function set_static_path(string $path):self {
        $this->static_path = $path;
        return $this;
    }
    

}