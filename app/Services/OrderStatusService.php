<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Auth,Database as DB};
final class OrderStatusService
{
    public const TRANSITIONS=['pending'=>['processing','cancelled'],'processing'=>['shipping','cancelled'],'shipping'=>['completed','cancelled'],'completed'=>[],'cancelled'=>[]];
    public static function change(int $id,string $next,string $note=''):void
    {
        $permission=['processing'=>'orders.confirm','shipping'=>'orders.ship','completed'=>'orders.complete','cancelled'=>'orders.cancel'][$next]??'';
        if($next==='processing'&&Auth::can('orders.prepare')){$permission='orders.prepare';}
        if(!$permission||!Auth::can($permission)){throw new \DomainException('Không có quyền đổi trạng thái này.');}
        DB::transaction(static function()use($id,$next,$note):void{
            $order=DB::execute('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id])->fetch();
            if(!$order||!in_array($next,self::TRANSITIONS[$order['status']]??[],true)){throw new \DomainException('Chuyển trạng thái không hợp lệ.');}
            if($next==='cancelled'){
                // Cùng khóa đơn và sản phẩm; chỉ trạng thái chưa hủy mới được hoàn tồn.
                $items=DB::execute('SELECT * FROM order_items WHERE order_id=? ORDER BY product_id',[$id])->fetchAll();
                foreach($items as $item){$p=DB::execute('SELECT stock FROM products WHERE id=? FOR UPDATE',[$item['product_id']])->fetch();$stock=(int)$p['stock']+(int)$item['quantity'];DB::execute('UPDATE products SET stock=? WHERE id=?',[$stock,$item['product_id']]);DB::execute('INSERT INTO stock_movements(product_id,order_id,staff_id,type,quantity,stock_after,reason) VALUES (?,?,?,?,?,?,?)',[$item['product_id'],$id,Auth::staff()['id'],'cancellation',$item['quantity'],$stock,'Hoàn kho: hủy đơn '.$order['code']]);}
            }
            DB::execute('UPDATE orders SET status=? WHERE id=?',[$next,$id]);
            DB::execute('INSERT INTO order_status_history(order_id,staff_id,from_status,to_status,note) VALUES (?,?,?,?,?)',[$id,Auth::staff()['id'],$order['status'],$next,$note]);
            AuditService::record('order.status','order',$id,['from'=>$order['status'],'to'=>$next]);
        });
    }
}
