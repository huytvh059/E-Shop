<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Auth,Database as DB,Validator};
final class PaymentService
{
    public static function record(int $id,array $input):void
    {
        $amount=Validator::integer($input['amount']??null,1,999999999999);$status=$input['status']??'';$method=$input['method']??'';$token=Validator::text($input['request_token']??'',64,64);
        if(!in_array($status,['unpaid','paid','refunded'],true)||!in_array($method,['cod','bank_transfer'],true)||!ctype_xdigit($token)){throw new \DomainException('Thông tin thanh toán không hợp lệ.');}
        $transaction=Validator::text($input['transaction_code']??'',0,120);
        $paidAt=null;if($status!=='unpaid'){$raw=(string)($input['paid_at']??'');$date=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$raw);if(!$date||$date->format('Y-m-d\TH:i')!==$raw){throw new \DomainException('Ngày thanh toán không hợp lệ.');}$paidAt=$date->format('Y-m-d H:i:s');}
        DB::transaction(static function()use($id,$amount,$status,$method,$token,$transaction,$paidAt):void{
            $order=DB::execute('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id])->fetch();if(!$order){throw new \DomainException('Đơn hàng không tồn tại.');}
            if(DB::execute('SELECT id FROM payments WHERE request_token=?',[$token])->fetch()){return;}
            $totals=DB::execute("SELECT COALESCE(SUM(CASE WHEN status='paid' THEN amount ELSE 0 END),0) AS received,COALESCE(SUM(CASE WHEN status='refunded' THEN amount ELSE 0 END),0) AS refunded FROM payments WHERE order_id=?",[$id])->fetch();
            if($status==='paid'&&($order['status']==='cancelled'||(int)$totals['received']+$amount>(int)$order['total'])){throw new \DomainException('Không thể thu tiền đơn đã hủy hoặc thu vượt tổng đơn.');}
            if($status==='refunded'&&$amount>(int)$totals['received']-(int)$totals['refunded']){throw new \DomainException('Số tiền hoàn vượt số tiền đã thu ròng.');}
            if($status==='unpaid'&&$amount>(int)$order['total']){throw new \DomainException('Số tiền chưa thanh toán vượt tổng đơn.');}
            DB::execute('INSERT INTO payments(order_id,staff_id,amount,method,status,transaction_code,request_token,paid_at) VALUES (?,?,?,?,?,?,?,?)',[$id,Auth::staff()['id'],$amount,$method,$status,$transaction?:null,$token,$paidAt]);AuditService::record('payment.'.$status,'order',$id,['amount'=>$amount,'method'=>$method]);
        });
    }
    public static function invoice(int $id):int
    {
        return DB::transaction(static function()use($id):int{
            $o=DB::execute('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id])->fetch();if(!$o||$o['status']==='cancelled'){throw new \DomainException('Không thể xuất hóa đơn cho đơn này.');}
            $existing=DB::execute('SELECT id FROM invoices WHERE order_id=?',[$id])->fetchColumn();if($existing){return (int)$existing;}
            DB::execute('INSERT INTO invoices(order_id,code,staff_id) VALUES (?,?,?)',[$id,'INV-'.date('Ymd').'-'.$id,Auth::staff()['id']]);$invoice=(int)DB::connection()->lastInsertId();AuditService::record('invoice.create','invoice',$invoice);return $invoice;
        });
    }
}
