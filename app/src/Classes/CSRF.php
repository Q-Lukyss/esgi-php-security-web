<?php declare(strict_types=1);

namespace App\Classes;

use Flender\Dash\Classes\ILogger;
use Flender\Dash\Classes\SecuredPdo;

class CSRF {

    private const TOKEN_LIFETIME = 1800;

    public function __construct(private SecuredPdo $pdo, private Security $security, private ILogger $logger) {}

    public function generate_token(string $user_id) {
        $token = $this->security->generate_session_id();

        $sql = <<<SQL
            UPDATE csrf_token FOR
        SQL;

        $this->pdo->execute($sql, [
            ":user_id" => $user_id,
            ":token" => $token,
            ":now" => time()
        ]);
        
        return $token;
    }

    public function verify_token(string $user_id, string $token) {
        
        $sql = <<<SQL
            SELECT csrf_token FROM users WHERE id = :id
        SQL;

        $user = $this->pdo->query_one($sql, [ "id" => $user_id ]);
        if ($user === null) {
            return false;
        }
        
        $user_token = $user["csrf_token"];

        if ($token !== $user_token) {
            return false;
        }

        // Delete token from db, cause it's used
        $sql = <<<SQL
            UPDATE users SET csrf_token = :token WHERE id = :id
        SQL;
        if ($this->pdo->execute($sql, [ "id" => $user_id, "token" => null ]) === 0) {
            $this->logger->warning("No row affected by CSRF deletion", [
                "user_id" => $user_id
            ]);
            return false;
        };

        return true;
    }

}