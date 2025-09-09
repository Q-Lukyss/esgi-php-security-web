<?php

namespace App\Controllers;

use App\Entity\User;
use Flender\Dash\Classes\Controller;
use Flender\Dash\Attributes\Route;
use Flender\Dash\Enums\Method;
use Flender\Dash\Response\JsonResponse;
use PDO;

class HomeController extends Controller {


    #[Route(Method::GET, "/")]
    public function index() {
        return $this->render("index", [
            "title" => "Accueil",
        ]);
    }

    #[Route(Method::GET, "/test/:id/id/:user")]
    public function test(string $user, PDO $pdo, int $id) {
        return new JsonResponse(["user" => $user, "id" => $id]);
    }

    #[Route(Method::GET, "/test")]
    public function test_2(User $user, PDO $pdo) {
        return new JsonResponse($user);
    }

    #[Route(Method::GET, "/about")]
    public function about() {
        return $this->render("about", [
            "title" => "A propos",
        ]);
    }

    #[Route(Method::GET, "/contact")]
    public function contact() {
        return $this->render("contact", [
            "title" => "Contact",
        ]);
    }

    #[Route(Method::GET, "/gallery")]
    public function gallery() {
        return $this->render("gallery", [
            "title" => "Gallerie",
        ]);
    }

    #[Route(Method::GET, "/product")]
    public function product() {
        return $this->render("product", [
            "title" => "Produits",
        ]);
    }

    #[Route(Method::GET, "/service")]
    public function service() {
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