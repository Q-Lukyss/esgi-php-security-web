<?php declare(strict_types=1);

namespace App\Classes;

use App\Entity\User;
use Flender\Dash\Interfaces\ISecurity;
use PDO;

class Security  {

    private array $hash_options = [];

    function __construct(private PDO $pdo) {
        $this->hash_options = [
            'memory_cost' => 65536,
            'time_cost' => 4,
        ];
    }

    function hash_password(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID, $this->hash_options);
    }

    public function get_user_from_sid(string $sid): ?SessionUser {


        /* $stmt = $this->pdo->prepare(
            <<<SQL
                SELECT id, username, email
                FROM users
                WHERE sid = :sid
                AND token_expiration > NOW()
            SQL
            ,
        );

        $stmt->execute([
            "sid" => $sid,
        ]); */

        // $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return new SessionUser("jean", "admin@test.fr", [
            "read:cocktail"
        ]);
    }
            public function get_user(string $password, string $hash): ?SessionUser {

        return password_verify($password, $hash);
    }

    public function generate_session_id(): string {
        return bin2hex(random_bytes(32));
    }

}