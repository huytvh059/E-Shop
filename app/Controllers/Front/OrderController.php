<?php
declare(strict_types=1);
namespace App\Controllers\Front;
use App\Core\{Controller,Database as DB,Auth,Response};
final class OrderController extends Controller
{
    public function index():void{$this->front('orders',['title'=>'Lịch sử mua hàng','orders'=>DB::execute('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC',[Auth::user()['id']])->fetchAll()]);}
    public function detail(string $id):void{$order=DB::execute('SELECT * FROM orders WHERE id=? AND user_id=?',[$id,Auth::user()['id']])->fetch();if(!$order){Response::abort(404,'Không tìm thấy đơn hàng.');}$this->front('order',['title'=>'Đơn hàng '.$order['code'],'order'=>$order,'items'=>DB::execute('SELECT * FROM order_items WHERE order_id=?',[$id])->fetchAll(),'history'=>DB::execute('SELECT * FROM order_status_history WHERE order_id=? ORDER BY id',[$id])->fetchAll(),'payments'=>DB::execute('SELECT * FROM payments WHERE order_id=? ORDER BY id',[$id])->fetchAll()]);}
}
