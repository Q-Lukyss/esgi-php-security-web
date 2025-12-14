<?php declare(strict_types=1);

namespace App\Classes;

use Flender\Dash\Classes\ILogger;

class CSRF
{
    private const int TOKEN_LIFETIME = 1800;
    private const string KEY = "csrf_token";
    private const string KEY_TIME = "csrf_time";

    public function __construct(private ILogger $logger) {}

    private function ensure_session(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function issue_token(): string
    {
        $this->ensure_session();

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::KEY] = $token;
        $_SESSION[self::KEY_TIME] = time();

        return $token;
    }

    public function verify_token(SessionUser $user, string $token): bool
    {
        $this->ensure_session();

        $stored = $_SESSION[self::KEY] ?? null;
        $ts = $_SESSION[self::KEY_TIME] ?? null;

        if (!is_string($stored) || !is_int($ts)) {
            $this->logger->warning("CSRF missing", ["user_id" => $user->id]);
            return false;
        }

        if (time() - $ts > self::TOKEN_LIFETIME) {
            unset($_SESSION[self::KEY], $_SESSION[self::KEY_TIME]);
            $this->logger->warning("CSRF expired", ["user_id" => $user->id]);
            return false;
        }

        if (!hash_equals($stored, $token)) {
            $this->logger->warning("CSRF mismatch", ["user_id" => $user->id]);
            return false;
        }

        // one-shot
        unset($_SESSION[self::KEY], $_SESSION[self::KEY_TIME]);
        return true;
    }
}
