<?php

namespace Flender\Dash\Classes;

class Request {

    private array $headers;
    private string $method;
    private array $cookies;
    private string $path;

    public static function from_global():Request {
        return new Request($_SERVER);
    }

    public function __construct(array $data) {
        // $this->headers = $data['HTTP_HEADERS'];
        $this->headers = getallheaders();
        $this->method = $data['REQUEST_METHOD'];
        $this->path = parse_url($data['REQUEST_URI'], PHP_URL_PATH);
        $this->cookies = $_COOKIE;
    }

    public function get_headers():array {
        return $this->headers;
    }

    public function get_header(string $header):?string {
        return $this->headers[$header] ?? null;
    } 

    public function get_method():string {
        return $this->method;
    }

    public function get_path():string {
        return $this->path;
    }

    public function get_cookie(string $name): ?string {
        return $this->cookies[$name] ?? NULL;
    }

    public function get_cookies(): array {
        return $this->cookies;
    }

}
