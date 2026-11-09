<?php
declare(strict_types=1);
const BASE_PATH = __DIR__;
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (str_starts_with($class, $prefix)) {
        $path = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) { require $path; }
    }
});
require BASE_PATH . '/app/helpers.php';
App\Core\Env::load(BASE_PATH . '/.env');
date_default_timezone_set('Asia/Ho_Chi_Minh');
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php.log');
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) { return false; }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(static function (Throwable $error): void {
    // Không ghi thông điệp PDO vì có thể chứa thông tin kết nối.
    App\Core\Logger::error('Unhandled exception', ['type' => get_class($error), 'file' => basename($error->getFile()), 'line' => $error->getLine()]);
    $message = App\Core\Env::bool('APP_DEBUG') ? $error->getMessage() : 'Hệ thống tạm thời không khả dụng. Vui lòng thử lại sau.';
    if (PHP_SAPI === 'cli') { fwrite(STDERR, $message . PHP_EOL); exit(1); }
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) { App\Core\Response::json(['error' => $message], 500); }
    App\Core\Response::abort(500, $message);
});
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('eshop_session');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => str_starts_with(App\Core\Env::get('APP_URL'), 'https://'), 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
