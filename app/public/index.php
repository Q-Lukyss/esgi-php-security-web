<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Flender\Dash\Classes\Router;
use Flender\Dash\Response\Response;

$app = new Router();

$app->set_base_path('')
    ->set_static_path('/static', __DIR__ . '/../public')
    ->set_404_callback(fn() => new Response('Custom Not Found Page', 404))
    ->set_error_callback(fn() => new Response('Custom Error Page', 500));

$app->get('/', function() {
    require __DIR__ . '/../src/templates/index.php';
});

$app->get('/about', function() {
    require __DIR__ . '/../src/templates/about.html';
});

// $app->set_controllers_directory(__DIR__ . '/../app/Controllers');

// $app->set_container([
//     PDO::class => function() {
//         return new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
//     }
// ]);

$app->run();