<?php
declare(strict_types=1);
namespace App\Core;
final class Response
{
    public static function json(array $data, int $status = 200): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR); exit; }
    public static function redirect(string $path): never { header('Location: ' . $path, true, 303); exit; }
    public static function abort(int $status, string $message): never { http_response_code($status); echo '<!doctype html><html lang="vi"><meta charset="utf-8"><title>E-Shop</title><h1>' . $status . '</h1><p>' . e($message) . '</p></html>'; exit; }
}
