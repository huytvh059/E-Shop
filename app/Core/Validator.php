<?php
declare(strict_types=1);
namespace App\Core;
final class Validator
{
    public static function text(mixed $value, int $min = 1, int $max = 255): string
    {
        if (!is_string($value)) { throw new \InvalidArgumentException('Dữ liệu không hợp lệ.'); }
        $value = trim($value);
        if (mb_strlen($value) < $min || mb_strlen($value) > $max) { throw new \InvalidArgumentException("Độ dài phải từ $min đến $max ký tự."); }
        return $value;
    }
    public static function email(mixed $value): string { $email = mb_strtolower(self::text($value, 3, 190)); if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new \InvalidArgumentException('Email không hợp lệ.'); } return $email; }
    public static function integer(mixed $value, int $min = 1, int $max = PHP_INT_MAX): int { $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]); if ($number === false) { throw new \InvalidArgumentException('Số không hợp lệ.'); } return $number; }
}
