<?php
declare(strict_types=1);
namespace App\Controllers\Front;
use App\Core\Database;
use App\Core\Response;
final class HealthController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        try { Database::execute('SELECT 1')->fetchColumn(); $ok = true; }
        catch (\Throwable) { $ok = false; }
        Response::json(['status' => $ok ? 'ok' : 'degraded', 'db' => $ok ? 'up' : 'down', 'time' => date(DATE_ATOM)], $ok ? 200 : 503);
    }
}
