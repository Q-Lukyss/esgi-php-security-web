<?php

namespace Flender\Dash\Classes;

abstract class Entity {
    
    public function __construct() {

        $errors = $this->verify();
        if (!empty($errors)) {
            throw new \Exception(implode(", ", $errors));
        }
    }

    // Return an array of errors if any
    abstract public function verify(): array;

}