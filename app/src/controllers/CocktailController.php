<?php

namespace App\Controllers;

use Flender\Dash\Classes\Controller;
use Flender\Dash\Attributes\Route;
use Flender\Dash\Enums\Method;

use PDO;

class CocktailController extends Controller {

     #[Route(Method::GET, "/cocktails")]
    // liste des cocktails
    public function cocktails(PDO $pdo) {
        $sql = 'SELECT a.username, c.slug, c.name, c.description, c.created_at
                FROM cocktails c
                INNER JOIN users a ON c.author_id = a.id
                WHERE visibility = "public"
                ORDER BY created_at DESC';
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $cocktails = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $this->render("cocktails", [
            "title" => "Nos Potions",
            "cocktails" => $cocktails,
        ]);
    }

    #[Route(Method::GET, "/cocktails/:slug")]
     public function cocktails_detail(PDO $pdo, string $slug) {
        // details cocktail
        $cocktail_rq = "SELECT a.username, c.slug, c.name, c.description, c.created_at
                FROM cocktails c
                INNER JOIN users a ON c.author_id = a.id
                WHERE visibility = 'public'
                and c.slug = ?
                ORDER BY c.created_at DESC";
        $sth = $pdo->prepare($cocktail_rq);
        $sth->execute([$slug]);
        $sth->execute();
        $cocktail = $sth->fetch(PDO::FETCH_ASSOC);

        // liste ingrédients du cocktail
        $ingredients_rq = "SELECT i.name,ci.quantity,ci.unit
                FROM cocktails c
                INNER JOIN cocktail_ingredients ci ON c.id = ci.cocktail_id
                INNER JOIN ingredients i ON ci.ingredient_id = i.id
                WHERE c.visibility = 'public'
                and c.slug = ?
                ORDER BY c.created_at DESC";
        $sth = $pdo->prepare($ingredients_rq);
        $sth->execute([$slug]);
        $sth->execute();
        $ingredients = $sth->fetchAll(PDO::FETCH_ASSOC);

        // nb like
        $upvotes_rq = "SELECT cl.user_id
                FROM cocktail_likes cl
                INNER JOIN cocktails c ON c.id = cl.cocktail_id
                WHERE c.visibility = 'public'
                and c.slug = ?
                ORDER BY c.created_at DESC";
        $sth = $pdo->prepare($upvotes_rq);
        $sth->execute([$slug]);
        $sth->execute();
        $upvotes = $sth->fetchAll(PDO::FETCH_ASSOC);
        
        // commentaires
        

        return $this->render("cocktail-detail", [
            "title" => $cocktail['name'] ?? 'Potion inconnue',
            "cocktail" => $cocktail,
            "ingredients" => $ingredients,
            "upvotes" => $upvotes,
        ]);
    }

    

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