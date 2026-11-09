<?php
declare(strict_types=1);
require __DIR__ . '/core.php';
use App\Core\Database;
foreach (['categories' => 5, 'brands' => 8, 'products' => 30, 'staff' => 5, 'users' => 3, 'orders' => 5] as $table => $count) {
    // Tên bảng chỉ lấy từ danh sách cố định, không nhận dữ liệu người dùng.
    check((int) Database::execute('SELECT COUNT(*) FROM ' . $table)->fetchColumn() === $count, 'seed count ' . $table);
}
check((int) Database::execute('SELECT COUNT(*) FROM products p LEFT JOIN (SELECT product_id, SUM(quantity) AS quantity FROM stock_movements GROUP BY product_id) m ON m.product_id=p.id WHERE p.stock <> COALESCE(m.quantity,0)')->fetchColumn() === 0, 'stock ledger reconciliation');
check((int) Database::execute('SELECT COUNT(*) FROM orders o JOIN (SELECT order_id, SUM(unit_price*quantity) AS total FROM order_items GROUP BY order_id) i ON i.order_id=o.id WHERE o.total <> i.total')->fetchColumn() === 0, 'order totals reconciliation');
$hash = Database::execute('SELECT password_hash FROM staff WHERE id = ?', [1])->fetchColumn();
check(password_verify('password', $hash), 'demo password verification');
check((int) Database::execute('SELECT COUNT(*) FROM products p WHERE NOT EXISTS (SELECT 1 FROM product_specs s WHERE s.product_id=p.id)')->fetchColumn() === 0, 'all products have specifications');
try {
    Database::transaction(static function (): void {
        Database::execute('UPDATE products SET stock = stock + 1 WHERE id = ?', [1]);
        throw new RuntimeException('rollback-probe');
    });
} catch (RuntimeException $error) { check($error->getMessage() === 'rollback-probe', 'transaction exception propagation'); }
check((int) Database::execute('SELECT stock FROM products WHERE id = ?', [1])->fetchColumn() === 19, 'transaction rollback');
