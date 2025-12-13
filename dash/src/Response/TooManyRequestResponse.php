<?php

namespace Flender\Dash\Response;

class TooManyRequestResponse extends Response
{

    public function __construct(int $retry_after = null)
    {
        $headers = [];
        if ($retry_after !== null) {
            $headers["Retry-After"] = $retry_after;
            $headers["X-RateLimit-Reset"] = time() + $retry_after; 
        }

        parent::__construct("", 429, $headers);
    }

}