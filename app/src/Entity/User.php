<?php declare(strict_types=1);

namespace App\Entity;

use Flender\Dash\Classes\Entity;
use Flender\Dash\Interfaces\IVerifiable;

class User extends Entity implements IVerifiable {

    public function __construct(public string $name, public int $age = 3) {
    }

    public function verify(): array {
        
        if (empty($this->name)) {
            return ["name" => "Name cannot be empty"];
        }

        return [
        ];
    }

}

