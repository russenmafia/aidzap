<?php
declare(strict_types=1);

namespace Core;

class View
{
    public static function render(string $view, array $data = [], string $layout = 'main'): void
    {
        $viewFile   = APP_PATH . '/Views/' . $view . '.php';
        $layoutFile = APP_PATH . '/Views/layouts/' . $layout . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "View not found: {$view}"; return;
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if (file_exists($layoutFile)) require $layoutFile;
        else echo $content;
    }
}
