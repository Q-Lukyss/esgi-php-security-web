<?php declare(strict_types=1);

namespace App\Middlewares;

use App\Classes\SessionUser;
use App\Entity\User;
use Flender\Dash\Classes\Container;
use Flender\Dash\Classes\Request;
use Flender\Dash\Interfaces\ISecurity;
use Flender\Dash\Response\JsonResponse;
use Flender\Dash\Response\Response;
use PDO;

class SecurityMiddleware
{
    const string SESSION_NAME = "lpm-sid";

    function handle(
        Request $req,
        Response $res,
        ISecurity $security,
        PDO $pdo,
        Container $container,
    ) {
        $sid = $req->get_cookie(self::SESSION_NAME);
        if ($sid === null) {
            return new JsonResponse(
                [
                    "error" => "Session not found",
                ],
                401,
            );
        }

        $stmt = $pdo->prepare(
            <<<SQL
                SELECT id, username, email
                FROM users
                WHERE sid = :sid
                AND token_expiration > NOW()
            SQL
            ,
        );

        $stmt->execute([
            "sid" => $sid,
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $res->set_status(401);
            $res->set_body(
                json_encode(["error" => "Invalid or expired token"]),
            );
            return $res;
        }

        // Join on permission,
        // Interface/AC UserConnected with methods rbac
        // User in req

        $container->set(SessionUser::class, $user);
    }
}
