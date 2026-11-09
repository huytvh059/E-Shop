<?php
declare(strict_types=1);
namespace App\Middleware;
use App\Core\Auth;
use App\Core\Response;
final class AuthMiddleware
{
    public static function customer(): void { if (!Auth::user()) { Response::redirect('/login'); } }
    public static function staff(): void { if (!Auth::staff()) { Response::redirect('/admin/login'); } }
}
