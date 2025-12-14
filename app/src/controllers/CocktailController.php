<?php

namespace App\Controllers;

use Flender\Dash\Classes\Controller;
use Flender\Dash\Attributes\Route;
use Flender\Dash\Enums\Method;

use PDO;

class CocktailController extends Controller
{
    // liste des cocktails
    #[Route(Method::GET, "/cocktails")]
    public function cocktails(PDO $pdo)
    {
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

    #[Route(Method::GET, "/cocktails/new")]
    public function cocktails_new(PDO $pdo)
    {
        $sth = $pdo->prepare(
            "SELECT id, name FROM ingredients ORDER BY name ASC",
        );
        $sth->execute();
        $allIngredients = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $this->render("cocktail-new", [
            "title" => "Créer une Potion",
            "allIngredients" => $allIngredients,
        ]);
    }

    #[Route(Method::POST, "/cocktails/new")]
    public function cocktails_create(PDO $pdo)
    {
        $name = isset($_POST["name"]) ? trim((string) $_POST["name"]) : "";
        $description = isset($_POST["description"])
            ? trim((string) $_POST["description"])
            : "";
        $visibility =
            isset($_POST["visibility"]) && $_POST["visibility"] === "private"
                ? "private"
                : "public";
        $ingredients =
            isset($_POST["ingredients"]) && is_array($_POST["ingredients"])
                ? $_POST["ingredients"]
                : [];

        if ($name === "") {
            header("Location: /cocktails/new?error=missing_name");
            exit();
        }

        // Pas d'auth pour l'instant -> admin = user id 5
        $authorId = 5;

        // slug simple (sans vérif unicité)
        $slug = mb_strtolower($name, "UTF-8");
        $slug = preg_replace("~[^a-z0-9]+~", "-", $slug);
        $slug = trim((string) $slug, "-");
        if ($slug === "") {
            $slug = "potion";
        }

        try {
            $pdo->beginTransaction();

            $sth = $pdo->prepare("
                    INSERT INTO cocktails (author_id, slug, name, description, visibility, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
            $sth->execute([$authorId, $slug, $name, $description, $visibility]);

            $cocktailId = (int) $pdo->lastInsertId();

            // ingrédients (optionnels)
            if (!empty($ingredients)) {
                $ins = $pdo->prepare("
                        INSERT INTO cocktail_ingredients (cocktail_id, ingredient_id, quantity, unit)
                        VALUES (?, ?, ?, ?)
                    ");

                foreach ($ingredients as $row) {
                    $ingredientId = isset($row["ingredient_id"])
                        ? (int) $row["ingredient_id"]
                        : 0;
                    $quantity = isset($row["quantity"])
                        ? trim((string) $row["quantity"])
                        : "";
                    $unit = isset($row["unit"])
                        ? trim((string) $row["unit"])
                        : "";

                    if ($ingredientId <= 0) {
                        continue;
                    }
                    if ($quantity === "") {
                        continue;
                    }

                    $ins->execute([
                        $cocktailId,
                        $ingredientId,
                        $quantity,
                        $unit,
                    ]);
                }
            }

            $pdo->commit();

            header("Location: /cocktails/" . urlencode((string) $slug));
            exit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            header("Location: /cocktails/new?error=create_failed");
            exit();
        }
    }

    #[Route(Method::GET, "/cocktails/:slug")]
    public function cocktails_detail(PDO $pdo, string $slug)
    {
        // details cocktail
        $cocktail_rq = "SELECT a.username, c.slug, c.name, c.description, c.created_at
                FROM cocktails c
                INNER JOIN users a ON c.author_id = a.id
                WHERE c.visibility = 'public'
                AND c.slug = ?
                ORDER BY c.created_at DESC";
        $sth = $pdo->prepare($cocktail_rq);
        $sth->execute([$slug]);
        $cocktail = $sth->fetch(PDO::FETCH_ASSOC);

        // liste ingrédients du cocktail
        $ingredients_rq = "SELECT i.name, ci.quantity, ci.unit
                FROM cocktails c
                INNER JOIN cocktail_ingredients ci ON c.id = ci.cocktail_id
                INNER JOIN ingredients i ON ci.ingredient_id = i.id
                WHERE c.visibility = 'public'
                AND c.slug = ?
                ORDER BY c.created_at DESC";
        $sth = $pdo->prepare($ingredients_rq);
        $sth->execute([$slug]);
        $ingredients = $sth->fetchAll(PDO::FETCH_ASSOC);

        // nb like
        $upvotes_rq = "SELECT cl.user_id
                FROM cocktail_likes cl
                INNER JOIN cocktails c ON c.id = cl.cocktail_id
                WHERE c.visibility = 'public'
                AND c.slug = ?
                ORDER BY c.created_at DESC";
        $sth = $pdo->prepare($upvotes_rq);
        $sth->execute([$slug]);
        $upvotes = $sth->fetchAll(PDO::FETCH_ASSOC);

        // commentaires
        $commentary_rq = "SELECT cr.rating, cr.comment, cr.created_at
                FROM cocktail_ratings cr
                INNER JOIN cocktails c ON c.id = cr.cocktail_id
                WHERE c.visibility = 'public'
                AND c.slug = ?
                ORDER BY c.created_at DESC";
        $sth = $pdo->prepare($commentary_rq);
        $sth->execute([$slug]);
        $ratings = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $this->render("cocktail-detail", [
            "title" => $cocktail["name"] ?? "Potion inconnue",
            "cocktail" => $cocktail,
            "ingredients" => $ingredients,
            "upvotes" => $upvotes,
            "ratings" => $ratings,
        ]);
    }

    #[Route(Method::GET, "/cocktails/edit/:slug")]
    public function cocktails_edit(PDO $pdo, string $slug)
    {
        $sth = $pdo->prepare("
            SELECT c.id, c.slug, c.name, c.description, c.visibility, c.created_at
            FROM cocktails c
            WHERE c.slug = ?
            LIMIT 1
        ");
        $sth->execute([$slug]);
        $cocktail = $sth->fetch(PDO::FETCH_ASSOC);

        $sth = $pdo->prepare(
            "SELECT id, name FROM ingredients ORDER BY name ASC",
        );
        $sth->execute();
        $allIngredients = $sth->fetchAll(PDO::FETCH_ASSOC);

        $sth = $pdo->prepare("
            SELECT ci.ingredient_id, ci.quantity, ci.unit
            FROM cocktail_ingredients ci
            INNER JOIN cocktails c ON c.id = ci.cocktail_id
            WHERE c.slug = ?
            ORDER BY ci.cocktail_id ASC
        ");
        $sth->execute([$slug]);
        $cocktailIngredients = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $this->render("cocktail-edit", [
            "title" => "Éditer " . ($cocktail["name"] ?? "Potion"),
            "cocktail" => $cocktail,
            "allIngredients" => $allIngredients,
            "cocktailIngredients" => $cocktailIngredients,
        ]);
    }

    #[Route(Method::POST, "/cocktails/edit/:slug")]
    public function cocktails_update(PDO $pdo, string $slug)
    {
        $name = isset($_POST["name"]) ? trim((string) $_POST["name"]) : "";
        $description = isset($_POST["description"])
            ? trim((string) $_POST["description"])
            : "";
        $visibility =
            isset($_POST["visibility"]) && $_POST["visibility"] === "private"
                ? "private"
                : "public";
        $ingredients =
            isset($_POST["ingredients"]) && is_array($_POST["ingredients"])
                ? $_POST["ingredients"]
                : [];

        if ($name === "") {
            header(
                "Location: /cocktails/edit/" .
                    urlencode((string) $slug) .
                    "?error=missing_name",
            );
            exit();
        }

        try {
            $pdo->beginTransaction();

            // retrouver l'id du cocktail
            $sth = $pdo->prepare(
                "SELECT id FROM cocktails WHERE slug = ? LIMIT 1",
            );
            $sth->execute([$slug]);
            $row = $sth->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $pdo->rollBack();
                header("Location: /cocktails?error=not_found");
                exit();
            }

            $cocktailId = (int) $row["id"];

            // update cocktail
            $sth = $pdo->prepare(
                "UPDATE cocktails SET name = ?, description = ?, visibility = ? WHERE id = ?",
            );
            $sth->execute([$name, $description, $visibility, $cocktailId]);

            // reset ingrédients
            $sth = $pdo->prepare(
                "DELETE FROM cocktail_ingredients WHERE cocktail_id = ?",
            );
            $sth->execute([$cocktailId]);

            if (!empty($ingredients)) {
                $ins = $pdo->prepare("
                    INSERT INTO cocktail_ingredients (cocktail_id, ingredient_id, quantity, unit)
                    VALUES (?, ?, ?, ?)
                ");

                foreach ($ingredients as $rowIng) {
                    $ingredientId = isset($rowIng["ingredient_id"])
                        ? (int) $rowIng["ingredient_id"]
                        : 0;
                    $quantity = isset($rowIng["quantity"])
                        ? trim((string) $rowIng["quantity"])
                        : "";
                    $unit = isset($rowIng["unit"])
                        ? trim((string) $rowIng["unit"])
                        : "";

                    if ($ingredientId <= 0) {
                        continue;
                    }
                    if ($quantity === "") {
                        continue;
                    }

                    $ins->execute([
                        $cocktailId,
                        $ingredientId,
                        $quantity,
                        $unit,
                    ]);
                }
            }

            $pdo->commit();

            header(
                "Location: /cocktails/edit/" .
                    urlencode((string) $slug) .
                    "?updated=1",
            );
            exit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            header(
                "Location: /cocktails/edit/" .
                    urlencode((string) $slug) .
                    "?error=update_failed",
            );
            exit();
        }
    }

    #[Route(Method::POST, "/cocktails/delete/:slug")]
    public function cocktails_delete(PDO $pdo, string $slug)
    {
        try {
            $pdo->beginTransaction();

            $sth = $pdo->prepare(
                "SELECT id FROM cocktails WHERE slug = ? LIMIT 1",
            );
            $sth->execute([$slug]);
            $row = $sth->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $pdo->rollBack();
                header("Location: /cocktails?error=not_found");
                exit();
            }

            $cocktailId = (int) $row["id"];

            // nettoyage dépendances (si pas de cascade)
            $pdo->prepare(
                "DELETE FROM cocktail_ingredients WHERE cocktail_id = ?",
            )->execute([$cocktailId]);
            $pdo->prepare(
                "DELETE FROM cocktail_likes WHERE cocktail_id = ?",
            )->execute([$cocktailId]);
            $pdo->prepare(
                "DELETE FROM cocktail_ratings WHERE cocktail_id = ?",
            )->execute([$cocktailId]);

            $pdo->prepare("DELETE FROM cocktails WHERE id = ?")->execute([
                $cocktailId,
            ]);

            $pdo->commit();

            header("Location: /cocktails?deleted=1");
            exit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            header("Location: /cocktails?error=delete_failed");
            exit();
        }
    }
}
