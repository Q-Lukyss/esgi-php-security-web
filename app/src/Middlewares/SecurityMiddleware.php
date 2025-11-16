<?php declare(strict_types=1);

namespace App\Middlewares;

use Flender\Dash\Classes\Request;
use Flender\Dash\Response\Response;

class SecurityMiddleware {
    function handle(Request $req, Response $res) {
        if ($req->get_header("Authorization") === null) {
            $res->set_status(401);
            return $res;
        }
    }
}