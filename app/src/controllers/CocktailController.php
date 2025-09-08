<?php
use Flender\Dash\Classes\Controller;
use Flender\Dash\Attributes\Route;
use Flender\Dash\Enums\Method;

class CocktailController extends Controller {

    // #[Route(Method::GET, "/cocktails")]
    // // liste des cocktails
    // // ajouter un param de recherche pour filtrer et recherche par nom

    // #[Route(Method::POST, "/cocktail")]
    // // Créer un Cocktail

    // #[Route(Method::PATCH, "/cocktail/:id")]
    // // Editer un cocktail

    // #[Route(Method::DELETE, "/cocktail/:id")]
    // // Supprimer un cocktail

    // #[Route(Method::POST, "/cocktail/:id/like")]
    // // Liker un cocktail

    // #[Route(Method::DELETE, "/cocktail/:id/like")]
    // // Supprimer le like d'un cocktail

    // #[Route(Method::GET, "/cocktails/like")]
    // // Voir mes cocktails likés

    // #[Route(Method::POST, "/cocktail/:id/rate/:value")]
    // // Noter un cocktail

    // #[Route(Method::PATCH, "/cocktail/:id/rate/:value")]
    // // Modifier la note d'un cocktail

    // #[Route(Method::DELETE, "/cocktail/:id/rate")]
    // Supprimer la note d'un cocktail







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