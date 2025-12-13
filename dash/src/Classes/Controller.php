<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

use Flender\Dash\Response\HtmlResponse;

abstract class Controller {
    public function render(string $template, array $data = []) {
        extract($data);
        ob_start();
        $path = Router::$TEMPLATES_DIRECTORY . DIRECTORY_SEPARATOR . $template . ".php";
        include $path;
        $content = ob_get_clean();
        return new HtmlResponse($content);
    }
}