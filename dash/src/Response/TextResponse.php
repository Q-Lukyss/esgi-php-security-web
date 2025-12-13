<?php declare(strict_types=1);

namespace Flender\Dash\Response;

class TextResponse extends Response
{
    public function __construct($body, int $status = 200)
    {
        parent::__construct($body, $status, ["Content-Type: text/plain"]);
    }
}
