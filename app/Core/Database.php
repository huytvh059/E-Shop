<?php
declare(strict_types=1);
namespace App\Core;
use PDO;
final class Database
{
    private static ?PDO $connection = null;
    public static function connection(): PDO
    {
        if (self::$connection === null) {
            foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
                if (Env::get($key) === '') { throw new \RuntimeException('Missing database configuration.'); }
            }
            $port = filter_var(Env::get('DB_PORT', '3306'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
            if ($port === false || !preg_match('/^[a-zA-Z0-9_]+$/', Env::get('DB_NAME'))) { throw new \RuntimeException('Invalid database configuration.'); }
            $host = Env::get('DB_HOST');
            if (str_contains($host, ';') || preg_match('/[\r\n]/', $host)) { throw new \RuntimeException('Invalid database host.'); }
            self::$connection = new PDO('mysql:host=' . $host . ';port=' . $port . ';dbname=' . Env::get('DB_NAME') . ';charset=utf8mb4', Env::get('DB_USER'), Env::get('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 5]);
            self::execute("SET time_zone = '+07:00'");
        }
        return self::$connection;
    }
    public static function execute(string $sql, array $params = []): \PDOStatement
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }
    public static function transaction(callable $callback): mixed
    {
        $db = self::connection();
        $db->beginTransaction();
        try { $result = $callback($db); $db->commit(); return $result; }
        catch (\Throwable $error) { if ($db->inTransaction()) { $db->rollBack(); } throw $error; }
    }
}
