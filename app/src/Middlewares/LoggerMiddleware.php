<?php declare(strict_types=1);

namespace App\Middlewares;

use Flender\Dash\Classes\ILogger;
use Flender\Dash\Classes\Request;

class LoggerMiddleware {

    public function __invoke(ILogger $logger, Request $req) {
        $logger->info("Request", [
            "method" => $req->method,
            "path" => $req->path,
        ]);
    }

}