<?php declare(strict_types=1);

namespace App\Classes;

use App\Entity\User;
use Flender\Dash\Interfaces\ISecurity;
use PDO;

class Security implements ISecurity {

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

    public function verify_user_password(string $username, string $password): bool {
        // get user from database
        $sql = <<<SQL
            SELECT password FROM users WHERE username = :username
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }

        $hash = $user["password"];
        return password_verify($password, $hash);
    }

    public function verify_session(string $token): ?User {
        // pdo
        return null;
    }

    public function create_session(User $user): string {
        // Set the session id in DB
        $sid = $this->create_session_id();
        $sql = <<<SQL
            UPDATE users SET session_id = :session_id WHERE id = :user_id
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':session_id', $sid);
        $stmt->bindValue(':user_id', $user->id());
        $stmt->execute();

        if (!$stmt->affectedRows()) {
        }

        return $this->create_session_id();
    }


    private function create_session_id(): string {
        return "a_random_session_id";
    }

}