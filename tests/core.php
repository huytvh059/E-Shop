<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
use App\Core\Csrf;
use App\Core\Validator;
use App\Core\Router;
function check(bool $result, string $label): void { if (!$result) { throw new RuntimeException('FAIL: ' . $label); } echo 'PASS: ' . $label . PHP_EOL; }
$_SESSION = [];
check(strlen(Csrf::token()) === 64, 'CSRF entropy');
check(Csrf::valid(Csrf::token()) && !Csrf::valid('invalid'), 'CSRF validation');
check(e('<script>') === '&lt;script&gt;', 'XSS escaping');
check(Validator::email('TEST@example.com') === 'test@example.com', 'email normalization');
check(Validator::integer('3', 1, 4) === 3, 'integer validation');
try { Validator::integer('1 OR 1=1'); throw new RuntimeException('FAIL integer SQL string'); } catch (InvalidArgumentException) { echo "PASS: reject invalid integer\n"; }
$called = false;
$router = new Router();
$router->add('GET', '/products/{id}', static function (string $id) use (&$called): void { $called = $id === '42'; });
$router->dispatch('GET', '/products/42?test=1');
check($called, 'parameterized route');
$hash = password_hash('test-only-password', PASSWORD_DEFAULT);
check(password_verify('test-only-password', $hash), 'password hash verification');
