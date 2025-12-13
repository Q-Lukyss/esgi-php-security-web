<?php

namespace Flender\Dash\Response;

use Flender\Dash\Enums\Status\RedirectionStatus;


class RedirectResponse extends Response
{

    public function __construct(string $url, RedirectionStatus $status = RedirectionStatus::MOVED_PERMANENTLY)
    {
        parent::__construct("", $status->value, [
            "Content-Type" => "application/json",
            "Location" => $url
        ]);
    }

}