<?php

namespace App\Controllers;

use App\Entity\User;
use App\Middlewares\RateLimiter;
use Flender\Dash\Classes\Controller;
use Flender\Dash\Attributes\Route;
use Flender\Dash\Classes\Request;
use Flender\Dash\Enums\Method;
use Flender\Dash\Interfaces\ISecurity;
use Flender\Dash\Response\JsonResponse;
use Flender\Dash\Response\Response;
use PDO;

class HomeController extends Controller
{
    #[Route(Method::GET, "/", rate_limiter: new RateLimiter(10, 210))]
    public function index()
    {
        // throw new ErrorException("test");
        return $this->render("index", [
            "title" => "Accueil",
        ]);
    }

    #[Route(Method::GET, "/test/:id/id/:user")]
    public function test(
        PDO $pdo,
        int $id,
        string $user,
        Request $req,
        Response $res,
        ISecurity $sec,
    ) {
        $query = <<<SQL
            SELECT * from my_table
        SQL;
        $query = "SELECT * from my_table";
        $res->add_headers(
            "x-test: test",
            "x-test2: " . $sec->verify_session("token"),
        );
        return new JsonResponse(["user" => $user, "id" => $id]);
    }

    #[Route(Method::GET, "/test")]
    public function test_2(User $user, PDO $pdo)
    {
        return new JsonResponse($user);
    }

    #[Route(Method::GET, "/contact")]
    public function contact()
    {
        return $this->render("contact", [
            "title" => "Contact",
        ]);
    }

    #[Route(Method::GET, "/gallery")]
    public function gallery()
    {
        return $this->render("gallery", [
            "title" => "Gallerie",
        ]);
    }

    #[Route(Method::GET, "/product")]
    public function product()
    {
        return $this->render("product", [
            "title" => "Produits",
        ]);
    }

    #[Route(Method::GET, "/service")]
    public function service()
    {
        return $this->render("service", [
            "title" => "Nos Services",
        ]);
    }

    // #[Route(Method::GET, "/json/:id")]
    // public function json(int $id) {
    //     return new JsonResponse(["id" => $id]);
    // }

    // #[Route(Method::GET, "/greet/:name")]
    // public function greet(string $name) {
    //     return "Hello $name";
    // }

    // #[Route(Method::GET, "/home")]
    // public function home() {
    //     return $this->render("home", [
    //         "title" => "Home Test",
    //         "user" => $this->getUser(),
    //     ]);
    // }

    // #[Route(Method::GET, "/")]
    // public function redirect() {
    //     return new RedirectResponse(BASE_URL."/home");
    // }
}
