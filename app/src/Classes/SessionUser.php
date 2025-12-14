<?php declare(strict_types=1);

namespace App\Classes;

use Flender\Dash\Classes\ConnectedUser;

class SessionUser extends ConnectedUser
{
    public function __construct(
        public readonly int $id,
        private string $username,
        private string $email,
        array $permissions = [],
    ) {
        parent::__construct($permissions);
    }
    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
