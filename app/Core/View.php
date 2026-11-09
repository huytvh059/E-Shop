<?php
declare(strict_types=1);
namespace App\Core;
final class View
{
    public static function render(string $name, array $data = [], ?string $layout = null): void
    {
        if (!preg_match('/^[a-zA-Z0-9_\/-]+$/', $name)) { throw new \InvalidArgumentException('Invalid view.'); }
        extract($data, EXTR_SKIP);
        ob_start();
        require BASE_PATH . '/app/Views/' . $name . '.php';
        $content = ob_get_clean();
        if ($layout !== null) { require BASE_PATH . '/app/Views/layouts/' . $layout . '.php'; } else { echo $content; }
    }
}
