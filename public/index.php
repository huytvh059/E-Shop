<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
$router = new App\Core\Router();
require BASE_PATH . '/config/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
