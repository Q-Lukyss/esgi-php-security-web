<?php
namespace Flender\Dash\Response;

class Response {

    private array $headers;
    private string $body;
    private int $status;

    private bool $sended;


    public function __construct(?string $body = null, int $status = 200, array $headers = []) {
        $this->headers = $headers;
        $this->body = $body ?? '';
        $this->status = $status;

        $this->sended = false;
    }

    public function set_body(string $body):void {
        $this->body = $body;
    }

    public function set_status(int $status):void {
        $this->status = $status;
    }

    public function add_header(string $header):void {
        $this->headers[] = $header;
    }

    public function add_headers(...$headers):void {
        foreach ($headers as $header) {
            $this->add_header($header);
        }
    }


    public function send():void {
        if ($this->sended) {
            throw new \InvalidArgumentException("Response already sent.");
        }
        $this->sended = true;

        foreach ($this->headers as $header) {
            header($header);
        }
        http_response_code($this->status);
        echo $this->body;
    }

}