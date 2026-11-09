<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Core\Auth;
use App\Core\Response;
final class RoleMiddleware
{
    public static function require(string $permission): void
    {
        AuthMiddleware::staff();
        if (!Auth::can($permission)) { Response::abort(403, 'Bạn không có quyền thực hiện thao tác này.'); }
    }
}
