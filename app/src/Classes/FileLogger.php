<?php declare(strict_types=1);

namespace App\Classes;

use Flender\Dash\Classes\ILogger;

class FileLogger implements ILogger
{
    private $handle;
    public function __construct(string $file)
    {
        // if file not exists, crate it
        $this->handle = fopen($file, "a");
    }

    private function log(
        string $level,
        string $message,
        array $context = [],
    ): void {
        $timestamp = gmdate("Y-m-d\TH:i:s\Z");
        $context_formatted = json_encode($context);
        $log = "$timestamp $level $message $context_formatted" . PHP_EOL;
        fwrite($this->handle, $log);
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
