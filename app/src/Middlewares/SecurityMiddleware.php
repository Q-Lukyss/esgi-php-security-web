<?php declare(strict_types=1);

namespace App\Middlewares;

use App\Classes\Security;
use App\Classes\SessionUser;
use App\Entity\User;
use Flender\Dash\Classes\Container;
use Flender\Dash\Classes\Permissions;
use Flender\Dash\Classes\Problem;
use Flender\Dash\Classes\Request;
use Flender\Dash\Interfaces\ISecurity;
use Flender\Dash\Response\JsonResponse;
use Flender\Dash\Response\ProblemeResponse;
use Flender\Dash\Response\Response;
use PDO;

class SecurityMiddleware
{
    const string SESSION_NAME = "lpm-sid";

    function __invoke(
        Request $req,
        Security $security,
        Container $container,
        Permissions $permissions,
    ) {
        // Get the session id
        $sid = $req->get_cookie(self::SESSION_NAME);
        if ($sid === null || $sid === "") {
            return new ProblemeResponse(new Problem("https://example.com/probs/unauthorized", "Session not found", "Invalid or expired token", $req->get_path()), 401);
        }

        // Get the user from the token
        $user = $security->get_user_from_sid($sid);
        if ($user === null) {
            return new ProblemeResponse(new Problem("https://example.com/probs/unauthorized", "Invalid or expired token", "Invalid or expired token", $req->get_path()), 401);
        }

        // Test permissions using RBAC
        if ($user->is_allowed_array($permissions->permissions) === false) {
            return new ProblemeResponse(new Problem("https://example.com/probs/unauthorized", "Accès non autorisé", "L'accès à la page est restrain avec les permissions de la session courante.", $req->get_path()), 403);
        }

        // Add the using in the context of the request
        $container->set(SessionUser::class, $user);
    }
}
