<?php declare(strict_types=1);

namespace App\Controllers;

use App\Classes\CookieFactory;
use App\Classes\SessionUser;
use Flender\Dash\Attributes\Route;
use Flender\Dash\Classes\Controller;
use Flender\Dash\Enums\Method;
use Flender\Dash\Interfaces\ISecurity;
use Flender\Dash\Interfaces\IVerifiable;
use Flender\Dash\Response\HtmlResponse;
use App\Middlewares\SecurityMiddleware;
use Flender\Dash\Response\Response;
use PDO;


class LoginBody implements IVerifiable {
    public function __construct(private string $username = "", private string $password = "") {}

    public function verify(): array {
        $errors = [];
        if (empty($this->username)) array_push($errors, "Username can't be empty");
        if (empty($this->password)) array_push($errors, "Password can't be empty");
        return $errors;
    }
}

class AuthController extends Controller {


    #[Route(Method::GET, "/me", middlewares: [SecurityMiddleware::class])]
    function me(?SessionUser $user) {
        $content = print_r($user, true);
        return new HtmlResponse("<pre>$content</pre>");
    }

    #[Route(Method::POST, "/login")]
    function login(ISecurity $security, PDO $pdo, LoginBody $body, Response $res) {
        
        // Get password of user
        // Test hash with password

        // If true, generate sid
        // Update fields (sid + time) in DB

        // Return cookie
        $sid = "213";
        
        $res->add_cookie(CookieFactory::create(SecurityMiddleware::SESSION_NAME, $sid));

        return new HtmlResponse("<pre>$sid</pre>");
    }

}