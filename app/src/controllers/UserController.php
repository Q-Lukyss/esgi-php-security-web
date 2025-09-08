<?php
use Flender\Dash\Classes\Controller;
use Flender\Dash\Attributes\Route;
use Flender\Dash\Enums\Method;

class UserController extends Controller {

    // Connexion / Inscription
    // Edition Profil Pour utilisateur connecté

    #[Route(Method::GET, "/profile/:id")]
    // mon profil

    #[Route(Method::PATCH, "/profile/:id")]
    // edit profil

    #[Route(Method::POST, "/profile")]
    // inscription



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