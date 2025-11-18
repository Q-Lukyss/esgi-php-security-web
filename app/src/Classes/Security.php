<?php declare(strict_types=1);

namespace App\Classes;

use App\Entity\User;
use Flender\Dash\Interfaces\ISecurity;
use PDO;

class Security implements ISecurity {

    private array $hash_options = [];

    function __construct() {
        $this->hash_options = [
            'memory_cost' => 65536,
            'time_cost' => 4,
        ];
    }

    function hash_password(string $password): string {
        return password_hash($password, PASSWORD_ARGON2ID, $this->hash_options);
    }

    public function verify_user_password(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    public function verify_session(string $token): ?User {
        return null;
    }

    public function create_session(User $user): string {

    }


    private function create_session_id(): string {
    }

}