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
use ReflectionFunction;
use Throwable;

class Router
{
    /**
     * All routes of the application
     * @var array
     */
    private array $routes;

    /**
     * Storage K/V (fn) used to implement dependency injection (DI)
     * @var array
     */
    private Container $container;

    /**
     * The base path of the application
     * Used in case if the base path is not only the origin
     * like Apache
     * @var string
     */
    private string $base_path = "";

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

    private ILogger $logger;
    private array $middlewares = [];

    public static string $APP_BASE = "";
    public static string $TEMPLATES_DIRECTORY = "";
    public static string $CONTROLLER_DIRECTORY = "";

    private const ERROR_ROUTE = "error";
    private const NOT_FOUND_ROUTE = "404";

    public function __construct()
    {
        // Set default routes
        header_remove();
        $this->routes = [
            "routes" => [],
            self::ERROR_ROUTE => fn() => new Response(
                "Enternal Server Error",
                500,
            ),
            self::NOT_FOUND_ROUTE => fn() => new Response("Not Found", 404),
        ];

        // Set default paths using composer autoload
        $this->logger = new EmptyLogger();
        $this->init_static_variable();
        $this->container = new Container([]);
    }

    /**
     * Analyser the incomming request, recover Controllers, performe middlewares and output the request
     */
    public function run(): void
    {
        // 1. Get all data from context
        // Add Request & Response to container
        $request = Request::from_global();
        $response = new Response();
        $this->container->add_multiple([
            Response::class => fn() => $response,
            Request::class => fn() => $request,
            Container::class => fn() => $this->container
        ]);

        if ($this->debug === false) {
            $this->logger = new EmptyLogger();
        }

        // 2. Get routes
        // Extract route from cache, or analyse the Controller/ directory
        // eventualy, save to cache
        $controller_loader = new ControllerLoader($this->logger);
        $is_cache_router_exists =
            $this->cache_router !== null && is_file($this->cache_router);
        if ($is_cache_router_exists) {
            $this->routes["routes"] = $controller_loader->get_routes_from_cache(
                $this->cache_router,
            );
        } else {
            $fetched_routes = $controller_loader->get_routes_from_directory(Router::$CONTROLLER_DIRECTORY);
            $this->routes["routes"] = $fetched_routes;
            if ($this->cache_router !== null) {
                $encoded_routes = json_encode($fetched_routes);
                file_put_contents($this->cache_router, $encoded_routes);
                $this->logger->info("Added routes to cache", [
                    "file" => $this->cache_router,
                    "routes" => $fetched_routes
                ]);
            }
        }

        // 3. Find the route
        // Prepare the path
        $route_matcher = new RouteMatcher($this->routes["routes"]);
        $path = $request->get_path();
        $have_base_path = $this->base_path !== null &&
            str_starts_with($path, $this->base_path);
        if ($have_base_path) {
            $path = substr($path, strlen($this->base_path));
        }

        // Test if a route match the patch
        [$route, $match_params] = $route_matcher->match($path);



        try {
            $res = $this->handle_request($route, $request, $match_params);
        } catch (Exception $ex) {
            $this->container->set(Exception::class, $ex);
            $res = $this->call($this->routes[self::ERROR_ROUTE]);
        } catch (Throwable $th) {
            $ex = new Exception($th->getMessage());
            $this->container->set(Exception::class, $ex);
            $res = $this->call($this->routes[self::ERROR_ROUTE]);
        }


        $res->send();
    }

    private function handle_request($route, $request, $match_params): Response
    {
        // If no route found, return 404
        if ($route === null) {
            $this->logger->info("No route found", [
                "path" => $request->get_path()
            ]);
            return $this->call($this->routes[self::NOT_FOUND_ROUTE]);
        }

        // If the method doesn't exist, return 405
        $r = $route["methods"][$request->get_method()] ?? null;
        if (!$r) {
            return $this->call(
                fn() => new JsonResponse(
                    ["error" => "method not allowed"],
                    405,
                ),
            );
        }

        $response = $this->container->get(Response::class);
        foreach ($this->middlewares as $middleware) {
            $rf = new ReflectionFunction($middleware);
            $optionnal_res = $this->call(
                $middleware,
                array_map(fn($it) => [
                    $it->getName(),
                    $it->getType()->__toString()
                ], $rf->getParameters()),
            );
            if ($optionnal_res !== null) {
                return $optionnal_res;
            }
        }

        foreach ($r["middlewares"] as $middleware) {
            $optionnal_res = $this->call(
                $middleware["callback"],
                $middleware["parameters"],
            );
            if ($optionnal_res !== null) {
                return $optionnal_res;
            }
        }

        // Finally, call the handler
        return $this->call($r["callback"], $r["parameters"], $match_params)->merge($response);
    }

    private function call(
        array|Closure $handler,
        array $parameters = [],
        array $matched_params = [],
    ): ?Response {
        // If is a array (Controller) extract the callback
        if (
            is_array($handler) &&
            is_string($handler[0]) &&
            class_exists($handler[0])
        ) {
            [$class, $method] = $handler;
            $instance = new $class();
            $handler = [$instance, $method];
        } else if ($handler instanceof Closure) {
            $rc = new \ReflectionFunction($handler);
            $parameters = array_map(fn($it) => [
                $it->getName(),
                $it->getType()->__toString()
            ], $rc->getParameters());
        }



        // Add needed params to container
        $needed_params = $parameters;
        $params = [];
        $id = 1;
        foreach ($needed_params as [$name, $type]) {
            $this->logger->info("processing $name of type $type");
            if (in_array($type, ["string", "int", "float"])) {
                if (settype($matched_params[$id], $type)) {
                    $params[$name] = $matched_params[$id];
                    $id++;
                }
            } else if (str_starts_with($type, "App\\") && class_exists($type)) {
                if ($_SERVER["REQUEST_METHOD"] === "GET") {
                    $query_params = $_GET;
                } else {
                    // Else try to get values from body (assuming JSON)
                    $body = file_get_contents("php://input");
                    $query_params = json_decode($body, true) ?? [];
                }

                $entity_instance = new $type(...$query_params);

                if (is_subclass_of($type, IVerifiable::class)) {
                    /** @var IVerifiable $entity_instance */
                    $errors = $entity_instance->verify();
                }
                if (count($errors) > 0) {
                    $handler = fn() => new Response(
                        "Entity validation failed: " .
                        implode(", ", $errors),
                        400,
                    );
                    var_dump("test");
                    $params = [];
                    break;
                }
                $params[$name] = $entity_instance;

            } else {
                $data = $this->container->get($type);
                if (is_callable($data)) {
                    $params[$name] = $data();
                } else {
                    $params[$name] = $data;
                }
            }
        }


        return $handler(...$params);
    }

    public function add_global_middleware(callable $middleware): self
    {
        $this->middlewares = [...$this->middlewares, $middleware];
        return $this;
    }

    public function set_logger(ILogger $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    private function init_static_variable()
    {
        $vender_directory = dirname(
            (new \ReflectionClass(ClassLoader::class))->getFileName(),
        );
        self::$APP_BASE = dirname($vender_directory, 2);

        $src_directory = self::$APP_BASE . DIRECTORY_SEPARATOR . "src";
        self::$TEMPLATES_DIRECTORY =
            $src_directory . DIRECTORY_SEPARATOR . "templates";
        self::$CONTROLLER_DIRECTORY =
            $src_directory . DIRECTORY_SEPARATOR . "Controllers";
    }

    /**
     * Set the file to the cache router
     * If the file already exists, use the data to
     * populate the routes array:w
     * @param string $path
     * @throws \Exception
     * @return Router
     */
    public function set_cache_router(string $path): self
    {
        $path = ltrim($path, "/\\");
        $this->cache_router = self::$APP_BASE . DIRECTORY_SEPARATOR . $path;
        return $this;
    }

    public function set_controllers_directory(string $directory): self
    {
        self::$CONTROLLER_DIRECTORY =
            self::$APP_BASE . DIRECTORY_SEPARATOR . ltrim($directory, "/\\");
        return $this;
    }

    public function set_debug(bool $debug = true): self
    {
        $this->debug = $debug;
        if ($this->debug === true) {
            $this->enable_debug_reporting();
        }
        return $this;
    }

    private function enable_debug_reporting(): void
    {
        ini_set("display_errors", "1");
        ini_set("display_startup_errors", "1");
        error_reporting(E_ALL);
    }

    public function set_container(Container $container): self
    {
        $this->container = $container;
        return $this;
    }
    public function set_base_path(string $base_path): self
    {
        $this->base_path = $base_path;
        return $this;
    }

    private function register_route(Route $route)
    {
        if (!$route->get_callback()) {
            throw new \InvalidArgumentException("Route must have a callback.");
        }
        $path = $route->get_path();
        $base = &$this->routes["routes"];

        // If route path does not exist, create it
        if (!is_array($base[$path] ?? null)) {
            [$regex, $parameters] = $route->get_config();
            $methods = [];
            $base[$path] = compact("regex", "parameters", "methods");
        }

        // If method already exists for this path, throw error
        if (isset($base[$path]["methods"][$route->get_method()->value])) {
            throw new \InvalidArgumentException(
                "Route $path already exists for method " .
                $route->get_method()->value,
            );
        }

        $base[$path]["methods"][
            $route->get_method()->value
        ] = $route->get_callback();
    }

    public function get(string $path, callable|array $callback): self
    {
        $this->register_route(new Route(Method::GET, $path, $callback));
        return $this;
    }

    public function set_404_callback(callable|array $callback): self
    {
        $this->routes[self::NOT_FOUND_ROUTE] = $callback;
        return $this;
    }

    public function set_error_callback(callable|array $callback): self
    {
        $this->routes[self::ERROR_ROUTE] = $callback;
        return $this;
    }

    public function remove_trailing_slash(bool $remove = true): self
    {
        if (
            $remove &&
            substr($_SERVER["REQUEST_URI"], -1) === "/" &&
            $_SERVER["REQUEST_URI"] !== "/"
        ) {
            $new_url = rtrim($_SERVER["REQUEST_URI"], "/");
            if (
                $this->base_path &&
                !str_starts_with($new_url, $this->base_path)
            ) {
                $new_url = $this->base_path . $new_url;
            }
            header("Location: $new_url", true, 301);
            exit();
        }
        return $this;
    }

    // public function set_static_path(string $path):self {
    //     $this->static_path = $path;
    //     return $this;
    // }
}
