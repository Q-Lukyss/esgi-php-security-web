<?php declare(strict_types=1);

namespace Flender\Dash\Classes;

abstract class Controller {
 
    public function render(string $template, array $data = []) {
        extract($data);
        ob_start();
        include $template;
        $content = ob_get_clean();
        return $content;
    }

}