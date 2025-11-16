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

    public function merge(Response $response): static {
        $this->headers = [
            ...$this->headers,
            ...$response->get_headers()
        ];
        // $this->body = $response->get_body();
        // $this->status = $response->get_status();
        return $this;
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

    public function get_body():string {
        return $this->body;
    }

    public function get_status():int {
        return $this->status;
    }

    public function get_headers():array {
        return $this->headers;
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