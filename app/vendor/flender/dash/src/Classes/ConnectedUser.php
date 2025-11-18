<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

class ConnectedUser {

    public function __construct(private array $permissions = []) {}

    public function is_allowed(string $permission) {
        return in_array($permission, $this->permissions);
    }

}