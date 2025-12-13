<?php declare(strict_types=1);

namespace Flender\Dash\Enums\Status;


enum RedirectionStatus: int {
    case MULTIPLE_CHOICES = 300;
    case MOVED_PERMANENTLY = 301;
    case FOUND = 302;
    case SEE_OTHER = 303;
    case NOT_MODIFIED = 304;
    case TEMPORARY_REDIRECT = 307;
    case PERMANENT_REDIRECT = 308;
}