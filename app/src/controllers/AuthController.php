<?php declare(strict_types=1);

namespace App\Controllers;

use App\Classes\CookieFactory;
use App\Classes\CSRF;
use App\Classes\Security;
use App\Classes\SessionUser;
use App\Interfaces\IVerifiable;
use App\Middlewares\RateLimiter;
use Flender\Dash\Attributes\Route;
use Flender\Dash\Classes\Controller;
use Flender\Dash\Classes\ILogger;
use Flender\Dash\Enums\Method;
use Flender\Dash\Response\HtmlResponse;
use App\Middlewares\SecurityMiddleware;
use Flender\Dash\Response\JsonResponse;
use Flender\Dash\Response\RedirectResponse;
use Flender\Dash\Response\Response;
use PDO;

class LoginBody implements IVerifiable
{
    public function __construct(
        public readonly string $username = "",
        public readonly string $password = "",
    ) {}

    public function verify(): array
    {
        $errors = [];
        if (empty($this->username)) {
            array_push($errors, "Username can't be empty");
        }
        if (empty($this->password)) {
            array_push($errors, "Password can't be empty");
        }
        return $errors;
    }
}

class AuthController extends Controller
{
    public function __construct(private ILogger $logger) {}

    #[
        Route(
            Method::GET,
            "/me",
            middlewares: [new RateLimiter(40), SecurityMiddleware::class],
            permissions: ["test"],
        ),
    ]
    public function me(?SessionUser $user)
    {
        $content = print_r($user, true);
        return new HtmlResponse("<pre>$content</pre>");
    }

    #[Route(Method::GET, "/login")]
    public function login_view()
    {
        return new HtmlResponse(
            "<form method='post' enctype='application/x-www-form-urlencoded'><input name='username' type='text' placeholder='username'><input name='password' type='password' placeholder='password'><button type='submit'>Login</button></form>",
        );
    }

    #[Route(Method::POST, "/login")]
    public function login(
        Security $security,
        PDO $pdo,
        LoginBody $body,
        Response $res,
    ) {
        var_dump($body);
        $errors = $body->verify();
        if ($errors !== []) {
            return new JsonResponse($errors, 400);
        }

        // Get password of user
        // Test hash with password
        $user = $security->get_user($body->username, $body->password);
        if ($user === null) {
            return new JsonResponse(["error" => "Invalid credentials"], 401);
        }

        // Return cookie
        $sid = $security->generate_session_id_for_user($user);

        $res->add_cookie(
            CookieFactory::create(SecurityMiddleware::SESSION_NAME, $sid),
        );

        return new HtmlResponse("<pre>$sid</pre>");
    }

    #[Route(Method::GET, "/logout", middlewares: [SecurityMiddleware::class])]
    public function logout(Response $res, SessionUser $user)
    {
        $this->logger->info("User logout", ["user_id" => $user->id]);
        $res->remove_cookie(
            CookieFactory::empty(SecurityMiddleware::SESSION_NAME),
        );
        return new RedirectResponse("home");
    }
}
