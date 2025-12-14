<?php declare(strict_types=1);

namespace App\Classes;

use Flender\Dash\Classes\Cookie;

class CookieFactory
{
    private static function is_secure_request(): bool
    {
        $https = $_SERVER["HTTPS"] ?? "";
        $xfp = $_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "";
        return ($https !== "" && $https !== "off") || $xfp === "https";
    }

    public static function create(string $name, string $value): Cookie
    {
        $secure = self::is_secure_request();

        return Cookie::create($name, $value)
            ->expiration(time() + 3600 * 24 * 7)
            ->domain("")
            ->secure($secure)
            ->httponly(true)
            ->samesite(Cookie::SAMESITE_STRICT);
    }

    public static function empty(string $name): Cookie
    {
        $secure = self::is_secure_request();

        return Cookie::create($name, "")
            ->expiration(time() - 3600) // suppression
            ->domain("")
            ->secure($secure)
            ->httponly(true)
            ->samesite(Cookie::SAMESITE_STRICT);
    }
}
