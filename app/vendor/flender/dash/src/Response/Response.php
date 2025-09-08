<?php
namespace Flender\Dash\Response;

class Response {

    private array $headers;
    private string $body;
    private int $status;

    public function __construct($body, int $status = 200, array $headers = []) {
        $this->headers = $headers;
        $this->body = $body;
        $this->status = $status;
    }

    public function send():void {
        foreach ($this->headers as $header) {
            header($header);
        }
        http_response_code($this->status);
        echo $this->body;
    }

}