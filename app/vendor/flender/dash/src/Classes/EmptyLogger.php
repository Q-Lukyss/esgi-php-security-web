<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

class EmptyLogger implements ILogger {
    function debug(string $message, array $context = []): void {}
        function info(string $message, array $context = []): void {}
    function warning(string $message, array $context = []): void {}
    function critical(string $message, array $context = []): void {}
    function error(string $message, array $context = []): void {}

}