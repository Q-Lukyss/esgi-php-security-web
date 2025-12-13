<?php
namespace Flender\Dash\Response;

use Flender\Dash\Classes\Problem;

class ProblemeResponse extends Response {

    public function __construct(Problem $problem, int $status = 400) {
        parent::__construct(json_encode($problem), $status, [
            "Content-Type: application/problem+json"
        ]);
    }

}