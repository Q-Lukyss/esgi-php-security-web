<?php

namespace Flender\Dash\Classes;

class Request
{
    public readonly array $headers;
    public readonly string $method;
    public readonly array $cookies;
    public readonly string $path;
    public readonly array $data;
    public readonly string $ip;

    public static function from_global(): Request
    {
        return new Request($_SERVER);
    }

    public function __construct(array $data)
    {
        // $this->headers = $data['HTTP_HEADERS'];
        $this->headers = getallheaders();
        $this->method = $data["REQUEST_METHOD"];
        $this->path = parse_url($data["REQUEST_URI"], PHP_URL_PATH);
        $this->cookies = $_COOKIE;
        $this->ip = $_SERVER["REMOTE_ADDR"];
    }

    public function get_data(): array
    {
        if ($this->method === "GET") {
            return $_GET;
        } else {
            // $body = file_get_contents("php://input");
            // var_dump($body);
            // return json_decode($body, true) ?? [];
            return $_POST;
        }
    }

    public function get_headers(): array
    {
        return $this->headers;
    }

    public function get_header(string $header): ?string
    {
        return $this->headers[$header] ?? null;
    }

    public function get_method(): string
    {
        return $this->method;
    }

    public function get_path(): string
    {
        return $this->path;
    }

    public function get_cookie(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    public function get_cookies(): array
    {
        return $this->cookies;
    }
}
