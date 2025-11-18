<?php declare(strict_types=1);

namespace App\Classes;

use Flender\Dash\Classes\ILogger;

class Logger implements ILogger
{
    private function log(
        string $level,
        string $message,
        array $context = [],
    ): void {
        echo "<pre style='background:#333;color:#0f0;padding:10px;'>" .
            $level .
            "<br>" .
            htmlspecialchars($message) .
            ($context !== []
                ? "<br>---<br>" . json_encode($context, JSON_PRETTY_PRINT)
                : "") .
            "</pre>";
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log("DEBUG", $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log("INFO", $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log("WARNING", $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log("ERROR", $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log("CRITICAL", $message, $context);
    }
}
