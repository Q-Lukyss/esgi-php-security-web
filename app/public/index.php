<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Flender\Dash\Classes\Router;
use Flender\Dash\Response\Response;

$app = new Router();

$app->set_base_path('/lpm')
    ->set_static_path('/static', __DIR__ . '/../public')
    ->set_404_callback(fn() => new Response('Custom Not Found Page', 404))
    ->set_error_callback(fn() => new Response('Custom Error Page', 500))
    ->remove_trailing_slash()
    ->set_cache_router("/cache/router.json")
    ->set_debug(false);

$app->set_controllers_directory();

$app->set_container([
    PDO::class => fn() => new PDO('sqlite:' . Router::$APP_BASE . DIRECTORY_SEPARATOR . 'database.db'),
    // Other services
]);

$app->run();
