<?php declare(strict_types=1);

namespace App\Entity;

use Flender\Dash\Classes\Entity;

class User extends Entity {

    public function __construct(public string $name) {
    }

    public function verify(): array {
        
        if (empty($this->name)) {
            return ["name" => "Name cannot be empty"];
        }

        return [
        ];
    }

}

