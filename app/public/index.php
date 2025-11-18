<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use App\Classes\Logger;
use App\Classes\ProjectEnv;
use App\Classes\Security;
use Flender\Dash\Classes\Container;
use Flender\Dash\Classes\EnvLoader;
use Flender\Dash\Classes\Router;
use Flender\Dash\Interfaces\ISecurity;
use Flender\Dash\Response\Response;

        


$app = new Router();
$env = EnvLoader::get_env(dirname(__DIR__), ProjectEnv::class);

$app->set_base_path("/lpm")
    // ->set_static_path('/static', __DIR__ . '/../public')
    ->set_debug(false)
    ->set_404_callback(fn() => new Response("Custom Not Found Page", 404))
    ->set_error_callback(
        fn(Exception $error) => new Response(
            "Custom Error Page: " . $error->getMessage(),
            500,
        ),
    )
    ->add_global_middleware(function(Response $req) {
        // All headers
        $req->set_headers([
            "X-Content-Type-Options" => "nosniff always",
            "X-Frame-Options" => "DENY always",
            "Referrer-Policy" => "strict-origin-when-cross-origin always",
            "Cross-Origin-Resource-Policy" => "same-origin always",
            "Content-Security-Policy" => "\"default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'; frame-ancestors 'none';\" always;",
            "Permissions-Policy" => "\"geolocation=(), microphone=(), camera=()\" always",
            "Strict-Transport-Security" => "\"max-age=31536000; includeSubDomains; preload\" always"
        ]);

        // Add cache controle pour les pages sensibles
    })
    ->set_logger(new Logger())
    ->remove_trailing_slash(false)
    // ->set_cache_router("/cache/router.json")
    ->set_container(new Container([
    PDO::class => fn() => new PDO(
        "sqlite:" . Router::$APP_BASE . DIRECTORY_SEPARATOR . "database.db",
    ),
    ISecurity::class => fn() => new Security(),
    // Other services
]));

$app->run();
