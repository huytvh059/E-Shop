<?php
declare(strict_types=1);
namespace App\Core;
final class Router
{
    private array $routes = [];
    public function add(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        $this->routes[] = [$method, $path, $handler, $middleware];
    }
    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $allowed = [];
        foreach ($this->routes as [$verb, $pattern, $handler, $middleware]) {
            $regex = preg_replace('/\\\{([a-zA-Z_]+)\\\}/', '(?P<$1>[0-9]+)', preg_quote($pattern, '~'));
            if (!preg_match('~^' . $regex . '$~D', $path, $matches)) { continue; }
            $allowed[] = $verb;
            if ($verb !== $method) { continue; }
            if ($method === 'POST') {
                $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
                if (!Csrf::valid(is_string($token) ? $token : null)) { Response::abort(419, 'Phiên hết hạn hoặc CSRF không hợp lệ.'); }
            }
            foreach ($middleware as $guard) { $guard(); }
            $arguments = array_values(array_filter($matches, static fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY));
            if (is_array($handler) && is_string($handler[0])) { $handler = [new $handler[0](), $handler[1]]; }
            $handler(...$arguments);
            return;
        }
        if ($allowed) { header('Allow: ' . implode(', ', array_unique($allowed))); Response::abort(405, 'Phương thức không được hỗ trợ.'); }
        Response::abort(404, 'Không tìm thấy trang.');
    }
}
