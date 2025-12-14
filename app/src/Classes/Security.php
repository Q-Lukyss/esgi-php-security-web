<?php declare(strict_types=1);

namespace App\Classes;

use DateInterval;
use DateTime;
use PDO;

class Security
{
    private array $hash_options = [];
    private DateInterval $session_duration;

    function __construct(private PDO $pdo)
    {
        $this->hash_options = [
            "memory_cost" => 65536,
            "time_cost" => 4,
            "threads" => 1,
        ];
        $this->session_duration = new DateInterval("P1D");
    }

    function hash_password(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, $this->hash_options);
    }

    public function get_user_from_sid(string $sid): ?SessionUser
    {
        $stmt = $this->pdo->prepare(
            <<<SQL
                SELECT u.id, u.username, u.email
                FROM sessions AS s
                JOIN users AS u ON s.user_id = u.id
                WHERE s.sid = :sid
                AND s.expires_at > NOW()
            SQL
            ,
        );

        $stmt->execute(["sid" => $sid]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user_data) {
            return null;
        }

        $permissions = [];
        // $permissions = $this->get_permissions($user_data["id"]);

        return new SessionUser(
            $user_data["id"],
            $user_data["username"],
            $user_data["email"],
            $permissions,
        );
    }
    public function get_user(string $user, string $password): ?SessionUser
    {
        $stmt = $this->pdo->prepare(
            <<<SQL
                SELECT id, username, email, password_hash
                FROM users
                WHERE username = :identifier OR email = :identifier
            SQL
            ,
        );
        $stmt->execute(["identifier" => $user]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user_data) {
            return null;
        }

        // if (password_verify($password, $user_data["password_hash"])) {
        if ($password === $user_data["password_hash"]) {
            $permissions = $this->get_permissions($user_data["id"]);
            return new SessionUser(
                (int) $user_data["id"],
                $user_data["username"],
                $user_data["email"],
                $permissions,
            );
        }

        return null;
    }

    private function get_permissions(int $user_id): array
    {
        $stmt = $this->pdo->prepare(
            <<<SQL
            SELECT  p.name
            FROM users AS u
            JOIN role_permissions AS rp ON u.id = rp.role_id
            JOIN  permissions AS p ON rp.permission_id = p.id
            WHERE  u.id = :user_id;
            SQL
            ,
        );
        $stmt->execute(["user_id" => $user_id]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $permissions;
    }

    public function generate_session_id_for_user(SessionUser $user): string
    {
        $sid = bin2hex(random_bytes(32));
        $expires_at = new DateTime();
        $expires_at->add($this->session_duration)->format("Y-m-d H:i:s");

        $stmt = $this->pdo->prepare(
            <<<SQL
                INSERT INTO sessions (sid, user_id, expires_at)
                VALUES (:sid, :user_id, :expires_at)
            SQL
            ,
        );
        $stmt->execute([
            "sid" => $sid,
            "user_id" => $user->id,
            "expires_at" => $expires_at,
        ]);

        return $sid;
    }

    public function clean_expired_sessions(): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM sessions WHERE expires_at < NOW()",
        );
        $stmt->execute();
    }
}
