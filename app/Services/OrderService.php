<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database as DB,Validator};
final class OrderService
{
    public static function place(int $userId,array $cart,array $input,string $token):array
    {
        if(!preg_match('/^[a-f0-9]{64}$/D',$token)){throw new \DomainException('Phiên đặt hàng không hợp lệ.');}
        return DB::transaction(static function()use($userId,$cart,$input,$token):array{
            // Khóa người mua và từng sản phẩm theo ID cố định; tránh bán vượt tồn và giảm nguy cơ deadlock.
            $user=DB::execute('SELECT id FROM users WHERE id=? AND is_active=1 FOR UPDATE',[$userId])->fetch();if(!$user){throw new \DomainException('Tài khoản không khả dụng.');}
            $existing=DB::execute('SELECT * FROM orders WHERE checkout_token=? AND user_id=?',[$token,$userId])->fetch();if($existing){return $existing;}
            if(!$cart){throw new \DomainException('Giỏ hàng đang trống.');}
            ksort($cart,SORT_NUMERIC);$items=[];$total=0;
            foreach($cart as $id=>$quantity){$quantity=Validator::integer($quantity,1,999);$p=DB::execute('SELECT * FROM products WHERE id=? FOR UPDATE',[(int)$id])->fetch();if(!$p||!$p['is_active']||(int)$p['stock']<$quantity){throw new \DomainException('Sản phẩm không còn đủ tồn kho. Vui lòng cập nhật giỏ hàng.');}$total+=(int)$p['price']*$quantity;$items[]=['product'=>$p,'quantity'=>$quantity];}
            if(!empty($input['address_id'])){$address=DB::execute('SELECT * FROM addresses WHERE id=? AND user_id=?',[(int)$input['address_id'],$userId])->fetch();if(!$address){throw new \DomainException('Địa chỉ giao hàng không hợp lệ.');}}
            else{$address=['recipient'=>Validator::text($input['recipient']??'',2,120),'phone'=>Validator::text($input['phone']??'',8,20),'address_line'=>Validator::text($input['address_line']??'',10,500)];}
            $method=$input['payment_method']??'';if(!in_array($method,['cod','bank_transfer'],true)){throw new \DomainException('Phương thức thanh toán không hợp lệ.');}
            $code='ESH-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(4)));
            DB::execute('INSERT INTO orders(code,user_id,checkout_token,recipient,phone,address_line,note,payment_method,total) VALUES (?,?,?,?,?,?,?,?,?)',[$code,$userId,$token,$address['recipient'],$address['phone'],$address['address_line'],Validator::text($input['note']??'',0,1000),$method,$total]);$orderId=(int)DB::connection()->lastInsertId();
            foreach($items as $item){$p=$item['product'];$qty=$item['quantity'];DB::execute('INSERT INTO order_items(order_id,product_id,product_name,sku,unit_price,quantity) VALUES (?,?,?,?,?,?)',[$orderId,$p['id'],$p['name'],$p['sku'],$p['price'],$qty]);DB::execute('UPDATE products SET stock=stock-? WHERE id=?',[$qty,$p['id']]);DB::execute('INSERT INTO stock_movements(product_id,order_id,type,quantity,stock_after,reason) VALUES (?,?,?,?,?,?)',[$p['id'],$orderId,'sale',-$qty,(int)$p['stock']-$qty,'Xuất theo đơn '.$code]);}
            DB::execute('INSERT INTO order_status_history(order_id,to_status,note) VALUES (?,?,?)',[$orderId,'pending','Khách hàng đặt đơn']);
            return ['id'=>$orderId,'code'=>$code];
        });
    }
}
