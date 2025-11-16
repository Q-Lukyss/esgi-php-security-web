<?php declare(strict_types=1);

namespace Flender\Dash\Interfaces;

interface ISecurity {
    function verify_session(string $token);
}