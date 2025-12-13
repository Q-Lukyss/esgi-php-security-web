# Dash
## Tiny PHP Router

Dash is a tiny PHP Framework that helps you create simple apps with ease.
The goal is to provide basic tools. To have more advanced features, plugins can be used.

The code est a complete mess, 'cause PHP... Use Rust instead ;-)

## Functionnalities
- Dynamic routes: `/user/:id`
- Middleware (global & per route): With this, it is possible to create your own **Rate Limiter**, **Authentificator**, etc.*
- Error handling: Possibilities to overload gloabal error handler and global not found handler.
- Dependency Injection: Main part. You can define a `container` that will be used to inject dependencies in your controllers. Morever, it is possible to define dynamic endpoint with `/:id` syntax, injected in your controller. Is it also possible to define a custom class and use it in the controller, e.g AuthBody with username and password fields. Class will be instantiated with fields from GET or POST parameters.

## Usage
```php
$app = new Dash\Router();

$app->get('/user/:id', function(int $id) {
    return "Hello user n°$id";
});

$app->run()
```
