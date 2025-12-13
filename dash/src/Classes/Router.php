<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use Composer\Autoload\ClassLoader;
use Exception;
use Flender\Dash\Attributes\Route;
use Flender\Dash\Enums\Method;
use Flender\Dash\Response\JsonResponse;
use Flender\Dash\Response\Response;
use Flender\Dash\Response\TextResponse;
use Throwable;

class Router
{
    /**
     * All default routes of the application
     * @var array
     */
    private array $default_routes;

    private RouterTree $router_tree;

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
    private const METHOD_NOT_ALLOWED = "method_not_allowed";

    public function __construct()
    {
        // Set default routes
        header_remove();
        $this->default_routes = [
            "routes" => [],
            self::ERROR_ROUTE => fn() => new Response(
                "Eternal Server Error",
                500,
            ),
            self::NOT_FOUND_ROUTE => fn() => new Response("Not Found", 404),
            self::METHOD_NOT_ALLOWED => fn() => new JsonResponse(
                ["error" => "Method not allowed"],
                405,
            ),
        ];

        // Set default paths using composer autoload
        $this->logger = new EmptyLogger();
        $this->init_static_variable();
        $this->container = new Container([]);
        $this->router_tree = new RouterTree();
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

            // In case...
            Container::class => fn() => $this->container,
        ]);
        $this->container->set_external_data($request->get_data());

        // Reset logger is not in debug mode
        if ($this->debug === false) {
            $this->logger = new EmptyLogger();
        }
        $this->container->set(ILogger::class, $this->logger);

        // 2. Get routes
        // Extract route from cache, or analyse the Controller/ directory
        // eventually, save to cache
        $controller_loader = new ControllerLoader($this->logger);
        $is_cache_router_exists =
            $this->cache_router !== null && is_file($this->cache_router);
        if ($is_cache_router_exists) {
            $this->router_tree = $this->router_tree->merge(
                $controller_loader->get_router_tree_from_cache(
                    $this->cache_router,
                ),
            );
        } else {
            $this->router_tree = $this->router_tree->merge(
                $controller_loader->get_router_tree_from_directory(
                    Router::$CONTROLLER_DIRECTORY,
                ),
            );
            if ($this->cache_router !== null) {
                $encoded_routes = json_encode($this->router_tree);
                file_put_contents($this->cache_router, $encoded_routes);
                $this->logger->info("Added routes to cache", [
                    "file" => $this->cache_router,
                    "routes" => $encoded_routes,
                ]);
            }
        }

        $path = $request->get_path();
        $have_base_path =
            $this->base_path !== null &&
            str_starts_with($path, $this->base_path);
        if ($have_base_path) {
            $path = substr($path, strlen($this->base_path));
        }

        $matched_parameters = [];
        $route = $this->router_tree->match($path, $matched_parameters);

        // Call request, and middlewares
        $res = $this->container->get(Response::class);
        try {
            // If no route found, return 404
            if ($route === null) {
                $this->logger->info("No route found", [
                    "path" => $request->get_path(),
                ]);
                $res = $this->container
                    ->call($this->default_routes[self::NOT_FOUND_ROUTE])
                    ->merge($res);
            } elseif (
                array_key_exists($request->get_method(), $route) === false
            ) {
                $res = $this->container
                    ->call($this->default_routes[self::METHOD_NOT_ALLOWED])
                    ->merge($res);
            } else {
                $res = $this->handle_request(
                    $route[$request->get_method()],
                    $matched_parameters,
                );
            }
        } catch (Exception $ex) {
            $this->container->set(Exception::class, $ex);
            $res = $this->container
                ->call($this->default_routes[self::ERROR_ROUTE])
                ->merge($res);
        } catch (Throwable $th) {
            $this->container->add_multiple([
                Throwable::class => $th,
                Exception::class => new Exception($th->getMessage()),
            ]);
            $res = $this->container
                ->call($this->default_routes[self::ERROR_ROUTE])
                ->merge($res);
        }

        // Finally, send the response
        $res->send();
    }

    private function handle_request(
        RouteScheme $route,
        array $match_params,
    ): Response {
        $response = $this->container->get(Response::class);
        $this->container->add_multiple([
            Permissions::class => new Permissions($route->permissions),
        ]);

        // Middleware globals + from route
        $middlewares = [...$this->middlewares, ...$route->middlewares];

        foreach ($middlewares as $middleware) {
            $optionnal_res = $this->container->call($middleware);
            if ($optionnal_res !== null) {
                return $optionnal_res->merge($response);
            }
        }

        // Call the handler
        $response = $this->container->call(
            $route->callback,
            $route->get_arguments($match_params),
            $route->parameters,
        );
        if (!($response instanceof Response)) {
            $response = new TextResponse((string) $response);
        }
        return $response->merge($response);
    }

    public function add_global_middleware($middleware): self
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
        $rc = new \ReflectionClass(ClassLoader::class);
        $vender_directory = dirname($rc->getFileName());
        self::$APP_BASE = dirname($vender_directory, 2);

        $src_directory = self::$APP_BASE . DIRECTORY_SEPARATOR . "src";
        self::$TEMPLATES_DIRECTORY =
            $src_directory . DIRECTORY_SEPARATOR . "templates";
        self::$CONTROLLER_DIRECTORY =
            $src_directory . DIRECTORY_SEPARATOR . "controllers";
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

    private function register_route(Route $route): self
    {
        if (!$route->get_callback()) {
            throw new \InvalidArgumentException("Route must have a callback.");
        }
        $this->router_tree->add($route);
        return $this;
    }

    public function get(string $path, callable|array $callback): self
    {
        $route = new Route(Method::GET, $path);
        $route->set_callback($callback);
        $this->register_route($route);

        return $this;
    }

    public function set_404_callback(callable|array $callback): self
    {
        $this->default_routes[self::NOT_FOUND_ROUTE] = $callback;
        return $this;
    }

    public function set_error_callback(callable|array $callback): self
    {
        $this->default_routes[self::ERROR_ROUTE] = $callback;
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
