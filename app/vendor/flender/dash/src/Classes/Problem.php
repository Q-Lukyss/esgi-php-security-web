<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use JsonSerializable;

class Problem implements JsonSerializable {
    public function __construct(public readonly string $type, public readonly string $title, public readonly string $detail, public readonly string $instance) {}

    public function jsonSerialize(): array {
        return get_object_vars($this);
    }
}