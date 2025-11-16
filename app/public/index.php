<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Classes\ProjectEnv;
use App\Classes\Security;
use Flender\Dash\Classes\EnvLoader;
use Flender\Dash\Classes\Router;
use Flender\Dash\Interfaces\ISecurity;
use Flender\Dash\Response\Response;

$app = new Router();
$env = EnvLoader::get_env(dirname(__DIR__), ProjectEnv::class, "test");

$app->set_base_path('/lpm')
    // ->set_static_path('/static', __DIR__ . '/../public')
    ->set_debug(true)
    ->set_404_callback(fn() => new Response('Custom Not Found Page', 404))
    ->set_error_callback(fn(Throwable $error) => new Response('Custom Error Page: ' . $error->getMessage(), 500))
    ->remove_trailing_slash(false)
    ->set_cache_router("/cache/router.json")
    ;
$container = [
    PDO::class => fn() => new PDO('sqlite:' . Router::$APP_BASE . DIRECTORY_SEPARATOR . 'database.db'),
    ISecurity::class => fn() => new Security()
    // Other services
];
$app->set_container($container);

$app->run();
