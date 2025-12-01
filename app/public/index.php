<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use App\Classes\FileLogger;
use App\Classes\ProjectEnv;
use App\Middlewares\LoggerMiddleware;
use Flender\Dash\Classes\Container;
use Flender\Dash\Classes\EnvLoader;
use Flender\Dash\Classes\Router;
use Flender\Dash\Response\Response;

$env = EnvLoader::get_env(dirname(__DIR__), ProjectEnv::class);

$app = new Router();
// ->set_base_path("/lpm")

// ->remove_trailing_slash(true)
$app
    // ->set_cache_router("/cache/router.json")

    // ->set_logger(new FileLogger("./cache/app.log"))
    ->set_debug(true)

    ->add_global_middleware([LoggerMiddleware::class, "__invoke"])
    ->add_global_middleware(function (Response $req) {
        // All headers
        $req->set_headers([
            "X-Content-Type-Options" => "nosniff always",
            "X-Frame-Options" => "DENY always",
            "Referrer-Policy" => "strict-origin-when-cross-origin always",
            "Cross-Origin-Resource-Policy" => "same-origin always",
            "Content-Security-Policy" =>
                "\"default-src 'self'; script-src 'self' 'unsafe-inline' https://code.jquery.com https://stackpath.bootstrapcdn.com; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'none';\" always;",
            "Permissions-Policy" =>
                "\"geolocation=(), microphone=(), camera=()\" always",
            "Strict-Transport-Security" =>
                "\"max-age=31536000; includeSubDomains; preload\" always",
        ]);

        // TODO: Add cache controle pour les pages sensibles
    })

    ->set_container(
        new Container([
            PDO::class => fn() => new PDO(
                "mysql:dbname=" .
                    $env->DATABASE_NAME . ";host=" . $env->DATABASE_URL, $env->DATABASE_USER, $env->DATABASE_PASSWORD
            ),

            // Other services...
        ]),
    )

    ->set_404_callback(fn() => new Response("Custom Not Found Page", 404))
    ->set_error_callback(
        fn(Exception $error) => new Response(
            "Custom Error Page: " . $error->getMessage(),
            500,
        ),
    );

// ->add_controller(HomeController::class)

/* $app->get("/test/test", function(PDO $pdo) {
    return "test";
}); */

$app->run();
