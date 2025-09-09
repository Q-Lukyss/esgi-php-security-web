<?php

namespace Flender\Dash\Classes;

abstract class Entity {

    // Return an array of errors if any
    abstract public function verify(): array;

}