<?php
declare(strict_types=1);
namespace App\Controllers\Admin;
use App\Core\{Controller,Database as DB,Auth};
final class DashboardController extends Controller
{
    public function index():void
    {
        $stats=['products'=>(int)DB::execute('SELECT COUNT(*) FROM products WHERE is_active=1')->fetchColumn(),'pending'=>(int)DB::execute("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn(),'low'=>(int)DB::execute('SELECT COUNT(*) FROM products WHERE is_active=1 AND stock<=min_stock')->fetchColumn()];
        if(Auth::can('reports.view')){$stats['revenue']=(int)DB::execute("SELECT COALESCE(SUM(CASE WHEN status='paid' THEN amount WHEN status='refunded' THEN -amount ELSE 0 END),0) FROM payments")->fetchColumn();}
        $low=Auth::can('inventory.view')?DB::execute('SELECT id,name,sku,stock,min_stock FROM products WHERE is_active=1 AND stock<=min_stock ORDER BY stock,id LIMIT 20')->fetchAll():[];
        $orders=Auth::can('orders.view')?DB::execute('SELECT * FROM orders ORDER BY id DESC LIMIT 8')->fetchAll():[];
        $this->admin('dashboard',['title'=>'Tổng quan hệ thống','stats'=>$stats,'low'=>$low,'orders'=>$orders]);
    }
}
