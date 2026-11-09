<?php
declare(strict_types=1);
namespace App\Core;
final class Env
{
    public static function load(string $file): void
    {
        if (!is_file($file)) { return; }
        foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) { continue; }
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2 || !preg_match('/^[A-Z_][A-Z0-9_]*$/', $parts[0])) { throw new \RuntimeException('Invalid environment configuration.'); }
            [$key, $value] = $parts;
            $value = trim($value);
            if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) { $value = substr($value, 1, -1); }
            if (getenv($key) === false) { putenv($key . '=' . $value); }
        }
    }
    public static function get(string $key, string $default = ''): string { $value = getenv($key); return $value === false ? $default : $value; }
    public static function bool(string $key): bool { return filter_var(self::get($key), FILTER_VALIDATE_BOOLEAN); }
}
