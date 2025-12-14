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
use Flender\Dash\Classes\Request;
use PDO;

class LoginBody implements IVerifiable
{
    public function __construct(
        public readonly string $username = "",
        public readonly string $password = "",
        public readonly string $csrf = "",
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

    #[Route(Method::GET, "/connexion")]
    public function connexion(CSRF $csrf, Request $req, Security $security)
    {
        // Si déjà connecté (cookie sid valide) -> redirect
        $sid = $req->get_cookie(SecurityMiddleware::SESSION_NAME);
        if ($sid !== null && $sid !== "") {
            $u = $security->get_user_from_sid($sid);
            if ($u !== null) {
                return new RedirectResponse("/cocktails");
            }
        }

        $token = $csrf->issue_token();

        $redirect = isset($_GET["redirect"])
            ? (string) $_GET["redirect"]
            : "/cocktails";

        return $this->render("connexion", [
            "title" => "Connexion",
            "csrf_token" => $token,
            "redirect" => $redirect,
            "user" => null,
        ]);
    }

    #[Route(Method::POST, "/login")]
    public function login(
        Security $security,
        PDO $pdo,
        LoginBody $body,
        Response $res,
        CSRF $csrf,
    ) {
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

        if ($csrf->verify_token($user, $body->csrf) === false) {
            return new JsonResponse(["error" => "Invalid CSRF token"], 401);
        }

        // Return cookie
        $sid = $security->generate_session_id_for_user($user);

        $res->add_cookie(
            CookieFactory::create(SecurityMiddleware::SESSION_NAME, $sid),
        );

        $contentType = $_SERVER["CONTENT_TYPE"] ?? "";
        $isForm =
            str_contains($contentType, "application/x-www-form-urlencoded") ||
            str_contains($contentType, "multipart/form-data");

        if ($isForm) {
            $redirect = isset($_POST["redirect"])
                ? (string) $_POST["redirect"]
                : "/cocktails";

            // évite les redirects externes
            if ($redirect === "" || !str_starts_with($redirect, "/")) {
                $redirect = "/cocktails";
            }

            return new RedirectResponse($redirect);
        }
        return new JsonResponse(["ok" => true], 200);
    }

    #[Route(Method::GET, "/logout", middlewares: [SecurityMiddleware::class])]
    public function logout(Response $res, SessionUser $user, Security $security)
    {
        $this->logger->info("User logout", ["user_id" => $user->id]);

        // Supprime la session côté DB si on a le cookie sid
        $sid = $_COOKIE[SecurityMiddleware::SESSION_NAME] ?? "";
        if (is_string($sid) && $sid !== "") {
            $security->delete_session($sid);
        }

        // Supprime le cookie côté client
        $res->remove_cookie(
            CookieFactory::empty(SecurityMiddleware::SESSION_NAME),
        );

        return new RedirectResponse("home");
    }
}
