<?php declare(strict_types=1);

namespace App\Classes;

class ProjectEnv {
    public string $DATABASE_URL;
    private string $DATABASE_USER;
    private string $DATABASE_PASSWORD;
}