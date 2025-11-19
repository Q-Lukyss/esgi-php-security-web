<?php declare(strict_types=1);

namespace App\Classes;

use Flender\Dash\Classes\SecuredPdo;

class CSRF {

    private const TOKEN_LIFETIME = 1800;

    public function __construct(private SecuredPdo $pdo, private Security $security) {}

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
        

        
        return true;
    }

}