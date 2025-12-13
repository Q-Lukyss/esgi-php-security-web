<?php
namespace Flender\Dash\Response;

class JsonResponse extends Response {

    public function __construct(array $body, int $status = 200) {
        parent::__construct(json_encode($body), $status, [
            "Content-Type: application/json"
        ]);
    }

}