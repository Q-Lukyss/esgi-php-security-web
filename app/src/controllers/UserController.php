<?php
namespace App\Controllers;

use Flender\Dash\Classes\Controller;
use Flender\Dash\Attributes\Route;
use Flender\Dash\Enums\Method;

use PDO;

class UserController extends Controller
{
    // Connexion / Inscription
    // Edition Profil Pour utilisateur connecté

    // #[Route(Method::GET, "/profile/:id")]
    // // mon profil

    // #[Route(Method::PATCH, "/profile/:id")]
    // // edit profil

    // #[Route(Method::POST, "/profile")]
    // inscription

    // liste des cocktails
    #[Route(Method::GET, "/druides")]
    public function druides(PDO $pdo)
    {
        $sql = 'SELECT id, role, display_name, bio, avatar_url
                FROM users u
                ORDER BY created_at DESC';
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $druides = $sth->fetchAll();

        return $this->render("druides", [
            "title" => "Nos Druides",
            "druides" => $druides,
        ]);
    }

    // liste des cocktails
    #[Route(Method::GET, "/druides/:id")]
    public function druide_detail(PDO $pdo, string $id)
    {
        $sql = 'SELECT id, role, display_name, bio, avatar_url
                FROM users u
                WHERE id = ?
                ORDER BY created_at DESC';
        $sth = $pdo->prepare($sql);
        $sth->execute([$id]);
        $druide = $sth->fetch(PDO::FETCH_ASSOC);

        return $this->render("druide-detail", [
            "title" => "Profil " . $druide["display_name"],
            "druide" => $druide,
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
