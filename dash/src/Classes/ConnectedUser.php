<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

class ConnectedUser
{

    public function __construct(private array $permissions = [])
    {
    }

    public function is_allowed(string $permission)
    {
        return in_array($permission, $this->permissions);
    }

    public function is_allowed_array(array $permissions)
    {
        foreach ($permissions as $permission) {
            if (!$this->is_allowed($permission)) {
                return false;
            }
        }
        return true;
    }

}