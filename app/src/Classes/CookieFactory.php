<?php declare(strict_types=1);

namespace App\Classes;

use Flender\Dash\Classes\Cookie;

class CookieFactory {

    public static function create(string $name, string $value): Cookie {
        return Cookie::create($name, $value)
            ->expiration(time() + 3600 * 1)
            ->domain("")
            ->secure(true)
            ->httponly(true)
            ->samesite(Cookie::SAMESITE_STRICT);
    }

        public static function empty(string $name): Cookie {
            return CookieFactory::create($name, "");
        }


}