<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

class Cookie implements \Stringable{

    const string SAMESITE_NONE = "None";
    const string SAMESITE_LAX = "Lax";
    const string SAMESITE_STRICT = "Strict";

    public function __construct(private string $name, private string $value, private int $expiration = 0, private string $path = "", private string $domain = "", private bool $secure = false, private bool $httponly = false, private string $samesite = self::SAMESITE_LAX) {}

    public function expiration(int $expiration): self {
        $this->expiration = $expiration;
        return $this;
    }

    public function clear(): self {
        $this->value = "";
        return $this;
    }


    public function path(string $path): self {
        $this->path = $path;
        return $this;
    }

    public function domain(string $domain): self {
        $this->domain = $domain;
        return $this;
    }

    public function samesite(string $samesite): self {
        $this->samesite = $samesite;
        return $this;
    }

    public function secure(bool $secure): self {
        $this->secure = $secure;
        return $this;
    }

    public function httponly(bool $httponly): self {
        $this->httponly = $httponly;
        return $this;
    }

    public static function create(string $name, string $value): Cookie {
        return new self($name, $value);
    }

    public function __tostring(): string {
         $parts = [];

        // Nom=Valeur (échapper si besoin)
        $parts[] = rawurlencode($this->name) . "=" . rawurlencode($this->value);

        // Expiration
        if ($this->expiration > 0) {
            // Format date GMT comme "D, d-M-Y H:i:s T"
            $expires = gmdate('D, d-M-Y H:i:s T', $this->expiration);
            $parts[] = "Expires=" . $expires;
        }

        // Path
        if ($this->path !== "") {
            $parts[] = "Path=" . $this->path;
        }

        // Domain
        if ($this->domain !== "") {
            $parts[] = "Domain=" . $this->domain;
        }

        // Secure
        if ($this->secure) {
            $parts[] = "Secure";
        }

        // HttpOnly
        if ($this->httponly) {
            $parts[] = "HttpOnly";
        }

        // SameSite
        if ($this->samesite !== "") {
            $parts[] = "SameSite=" . $this->samesite;
        }

        // Joindre toutes les parties avec un “; ”
        return implode("; ", $parts);
    }

}