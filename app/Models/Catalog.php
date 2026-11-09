<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Database as DB;
final class Catalog
{
    public static function categories(): array { return DB::execute('SELECT * FROM categories ORDER BY id')->fetchAll(); }
    public static function brands(): array { return DB::execute('SELECT * FROM brands ORDER BY name')->fetchAll(); }
    public static function search(array $filters, int $page = 1, int $limit = 12): array
    {
        $where = ['p.is_active = 1']; $params = [];
        if (!empty($filters['q'])) { $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; $term = '%' . mb_substr((string) $filters['q'],0,100) . '%'; $params[] = $term; $params[] = $term; }
        foreach (['category' => 'category_id', 'brand' => 'brand_id'] as $key => $column) { if (!empty($filters[$key])) { $where[] = 'p.' . $column . ' = ?'; $params[] = (int) $filters[$key]; } }
        foreach (['min' => '>=', 'max' => '<='] as $key => $operator) { if (isset($filters[$key]) && $filters[$key] !== '') { $where[] = 'p.price ' . $operator . ' ?'; $params[] = max(0, (int) $filters[$key]); } }
        if (!empty($filters['featured'])) { $where[] = 'p.is_featured = 1'; }
        $clause = implode(' AND ', $where);
        $count = (int) DB::execute('SELECT COUNT(*) FROM products p WHERE ' . $clause, $params)->fetchColumn();
        $sort = ['price_asc' => 'p.price ASC,p.id ASC', 'price_desc' => 'p.price DESC,p.id DESC', 'newest' => 'p.created_at DESC,p.id DESC'][$filters['sort'] ?? 'newest'] ?? 'p.id DESC';
        $page = max(1,min($page,max(1,(int)ceil($count/$limit))));
        $sql = 'SELECT p.*, b.name AS brand_name, (SELECT path FROM product_images WHERE product_id=p.id ORDER BY sort_order,id LIMIT 1) AS image FROM products p JOIN brands b ON b.id=p.brand_id WHERE ' . $clause . ' ORDER BY ' . $sort . ' LIMIT ? OFFSET ?';
        $statement = DB::connection()->prepare($sql);
        foreach (array_values($params) as $i => $value) { $statement->bindValue($i+1,$value,is_int($value)?\PDO::PARAM_INT:\PDO::PARAM_STR); }
        $statement->bindValue(count($params)+1,$limit,\PDO::PARAM_INT); $statement->bindValue(count($params)+2,($page-1)*$limit,\PDO::PARAM_INT);$statement->execute();
        return ['products'=>$statement->fetchAll(),'count'=>$count,'page'=>$page,'pages'=>(int)ceil($count/$limit)];
    }
    public static function find(int $id, bool $active = true): ?array
    {
        $product=DB::execute('SELECT p.*,b.name AS brand_name,c.name AS category_name FROM products p JOIN brands b ON b.id=p.brand_id JOIN categories c ON c.id=p.category_id WHERE p.id=?' . ($active?' AND p.is_active=1':''),[$id])->fetch();
        if (!$product) {return null;}
        $product['images']=DB::execute('SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order,id',[$id])->fetchAll();
        $product['specs']=DB::execute('SELECT * FROM product_specs WHERE product_id=? ORDER BY sort_order,id',[$id])->fetchAll();return $product;
    }
}
