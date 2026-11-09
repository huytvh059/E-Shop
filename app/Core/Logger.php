<?php
declare(strict_types=1);
namespace App\Core;
final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        $entry = json_encode(['time' => date(DATE_ATOM), 'message' => $message, 'context' => $context], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        error_log($entry . PHP_EOL, 3, BASE_PATH . '/storage/logs/app.log');
    }
}
