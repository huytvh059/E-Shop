<?php
declare(strict_types=1);
namespace App\Core;
final class Auth
{
    public static function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) { return null; }
        $user = Database::execute('SELECT id, name, email, phone FROM users WHERE id = ? AND is_active = 1', [$id])->fetch();
        if (!$user) { unset($_SESSION['user_id']); }
        return $user ?: null;
    }
    public static function staff(): ?array
    {
        $id = $_SESSION['staff_id'] ?? null;
        if (!$id) { return null; }
        $staff = Database::execute('SELECT s.id, s.name, s.email, r.name AS role FROM staff s JOIN roles r ON r.id = s.role_id WHERE s.id = ? AND s.is_active = 1', [$id])->fetch();
        if (!$staff) { unset($_SESSION['staff_id']); }
        return $staff ?: null;
    }
    public static function can(string $permission): bool
    {
        $staff = self::staff();
        if (!$staff) { return false; }
        $roles = require BASE_PATH . '/config/permissions.php';
        $permissions = $roles[$staff['role']] ?? [];
        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }
    public static function login(int $id, bool $staff = false): void
    {
        session_regenerate_id(true);
        $_SESSION[$staff ? 'staff_id' : 'user_id'] = $id;
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    public static function logout(bool $staff = false): void
    {
        unset($_SESSION[$staff ? 'staff_id' : 'user_id']);
        session_regenerate_id(true);
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
}
