<?php declare(strict_types=1);

namespace App\Middlewares;

use App\Classes\Security;
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

    function __invoke(
        Request $req,
        Response $res,
        Security $security,
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

        $user = $security->get_user_from_sid($sid);
        if ($user === null) {
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
