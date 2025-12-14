<?php declare(strict_types=1);

namespace App\Classes;

use PDO;

class Security
{
    private array $hash_options = [];

    public function __construct(private PDO $pdo)
    {
        $this->hash_options = [
            "memory_cost" => 65536,
            "time_cost" => 4,
        ];
    }

    public function hash_password(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, $this->hash_options);
    }

    private function permissions_from_role(string $role): array
    {
        return match ($role) {
            "admin" => [
                "test",
                "read:cocktail",
                "read:private",
                "write:cocktail",
                "delete:cocktail",
            ],
            "premium" => [
                "test",
                "read:cocktail",
                "read:private",
                "write:cocktail",
            ],
            default => ["test", "read:cocktail", "write:cocktail"],
        };
    }

    public function get_user(string $identifier, string $password): ?SessionUser
    {
        $sql = "
            SELECT id, username, email, password_hash, role
            FROM users
            WHERE username = ? OR email = ?
            LIMIT 1
        ";
        $sth = $this->pdo->prepare($sql);
        $sth->execute([$identifier, $identifier]);
        $row = $sth->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $storedHash = (string) $row["password_hash"];
        if (!password_verify($password, $storedHash)) {
            return null;
        }

        if (
            password_needs_rehash(
                $storedHash,
                PASSWORD_ARGON2ID,
                $this->hash_options,
            )
        ) {
            $newHash = $this->hash_password($password);
            $upd = $this->pdo->prepare(
                "UPDATE users SET password_hash = ? WHERE id = ?",
            );
            $upd->execute([$newHash, (int) $row["id"]]);
        }

        return new SessionUser(
            (int) $row["id"],
            (string) $row["username"],
            (string) $row["email"],
            $this->permissions_from_role((string) $row["role"]),
        );
    }

    /**
     * Utilisé par SecurityMiddleware: récupère l'utilisateur depuis le cookie sid
     */
    public function get_user_from_sid(string $sid): ?SessionUser
    {
        $sql = "
            SELECT u.id, u.username, u.email, u.role
            FROM sessions s
            INNER JOIN users u ON u.id = s.user_id
            WHERE s.sid = ?
              AND s.expires_at > NOW()
            LIMIT 1
        ";
        $sth = $this->pdo->prepare($sql);
        $sth->execute([$sid]);
        $row = $sth->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return new SessionUser(
            (int) $row["id"],
            (string) $row["username"],
            (string) $row["email"],
            $this->permissions_from_role((string) $row["role"]),
        );
    }

    public function generate_session_id_for_user(SessionUser $user): string
    {
        $sid = bin2hex(random_bytes(32)); // 64 chars

        $sql = "
            INSERT INTO sessions (sid, user_id, expires_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))
        ";
        $sth = $this->pdo->prepare($sql);
        $sth->execute([$sid, $user->id]);

        return $sid;
    }

    public function delete_session(string $sid): void
    {
        $sth = $this->pdo->prepare("DELETE FROM sessions WHERE sid = ?");
        $sth->execute([$sid]);
    }
}
