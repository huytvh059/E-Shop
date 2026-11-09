<?php
declare(strict_types=1);
namespace App\Controllers\Admin;
use App\Core\{Controller,Database as DB,Response};
use App\Services\InventoryService;
final class InventoryController extends Controller
{
    public function index():void{$this->admin('inventory',['title'=>'Quản trị kho hàng','products'=>DB::execute('SELECT id,name,sku,stock,min_stock FROM products ORDER BY stock<=min_stock DESC,id DESC')->fetchAll(),'movements'=>DB::execute('SELECT m.*,p.name AS product_name,s.name AS actor FROM stock_movements m JOIN products p ON p.id=m.product_id LEFT JOIN staff s ON s.id=m.staff_id ORDER BY m.id DESC LIMIT 200')->fetchAll()]);}
    public function move():void{try{InventoryService::move($_POST);flash('success','Đã ghi nhận phiếu kho.');Response::redirect('/admin/inventory');}catch(\Throwable $e){$this->failure($e,'/admin/inventory');}}
}
