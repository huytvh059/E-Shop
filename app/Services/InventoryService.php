<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Auth,Database as DB,Validator};
final class InventoryService
{
    public static function move(array $input):void
    {
        $id=Validator::integer($input['product_id']??null);$quantity=Validator::integer($input['quantity']??null,1,1000000);$type=$input['type']??'';$reason=Validator::text($input['reason']??'',5,500);$token=Validator::text($input['request_token']??'',64,64);
        if(!in_array($type,['receipt','issue'],true)||!ctype_xdigit($token)){throw new \DomainException('Phiếu kho không hợp lệ.');}
        DB::transaction(static function()use($id,$quantity,$type,$reason,$token):void{
            $p=DB::execute('SELECT * FROM products WHERE id=? FOR UPDATE',[$id])->fetch();if(!$p){throw new \DomainException('Sản phẩm không tồn tại.');}
            if(DB::execute('SELECT id FROM stock_movements WHERE request_token=?',[$token])->fetch()){return;}
            $delta=$type==='receipt'?$quantity:-$quantity;$stock=(int)$p['stock']+$delta;if($stock<0){throw new \DomainException('Không thể xuất vượt số lượng tồn kho.');}
            DB::execute('UPDATE products SET stock=? WHERE id=?',[$stock,$id]);DB::execute('INSERT INTO stock_movements(product_id,staff_id,type,quantity,stock_after,reason,request_token) VALUES (?,?,?,?,?,?,?)',[$id,Auth::staff()['id'],$type,$delta,$stock,$reason,$token]);AuditService::record('inventory.'.$type,'product',$id,['quantity'=>$delta,'stock'=>$stock]);
        });
    }
}
