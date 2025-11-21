<?php declare(strict_types=1);

namespace App\Middlewares;

use Flender\Dash\Classes\Request;
use Flender\Dash\Classes\SecuredPdo;
use Flender\Dash\Response\TooManyRequestResponse;

class RateLimiter {

    public function __construct(public readonly int $ms) {}

    function __invoke(
        Request $req,
        SecuredPdo $pdo,
    ) {

        // Test rate limit
        $ip = $req->ip;
        // SQL count()

        $is_ok = true;
        if ($is_ok) {
            $retry_after = 10;
            return new TooManyRequestResponse($retry_after);
        }


    }

}