<?php declare(strict_types=1);

namespace App\Interfaces;

interface IVerifiable {

    // Return an array of errors if any
    public function verify(): array;

}