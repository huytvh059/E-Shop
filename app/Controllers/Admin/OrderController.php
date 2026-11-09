<?php
declare(strict_types=1);
namespace App\Controllers\Admin;
use App\Core\{Controller,Database as DB,Response,Validator};
use App\Services\{OrderStatusService,PaymentService,AuditService};
final class OrderController extends Controller
{
    public function index():void
    {
        $filters=array_filter($_GET,static fn($v)=>is_scalar($v));$where=['1=1'];$params=[];
        if(!empty($filters['q'])){$where[]='o.code LIKE ?';$params[]='%'.mb_substr((string)$filters['q'],0,50).'%';}
        if(in_array($filters['status']??'',array_keys(OrderStatusService::TRANSITIONS),true)){$where[]='o.status=?';$params[]=$filters['status'];}
        foreach(['start'=>'>=','end'=>'<='] as $key=>$op){if(preg_match('/^\d{4}-\d{2}-\d{2}$/D',(string)($filters[$key]??''))){$where[]='o.created_at '.$op.' ?';$params[]=$filters[$key].($key==='end'?' 23:59:59':' 00:00:00');}}
        $clause=implode(' AND ',$where);$count=(int)DB::execute('SELECT COUNT(*) FROM orders o WHERE '.$clause,$params)->fetchColumn();$page=max(1,(int)($filters['page']??1));$stmt=DB::connection()->prepare('SELECT o.*,u.name AS customer_name FROM orders o JOIN users u ON u.id=o.user_id WHERE '.$clause.' ORDER BY o.id DESC LIMIT 20 OFFSET ?');foreach($params as $i=>$value){$stmt->bindValue($i+1,$value);}$stmt->bindValue(count($params)+1,($page-1)*20,\PDO::PARAM_INT);$stmt->execute();
        $this->admin('orders',['title'=>'Quản lý đơn hàng','orders'=>$stmt->fetchAll(),'filters'=>$filters,'page'=>$page,'pages'=>(int)ceil($count/20)]);
    }
    public function detail(string $id):void{$order=DB::execute('SELECT * FROM orders WHERE id=?',[$id])->fetch();if(!$order){Response::abort(404,'Đơn hàng không tồn tại.');}$this->admin('order',['title'=>'Đơn hàng '.$order['code'],'order'=>$order,'items'=>DB::execute('SELECT * FROM order_items WHERE order_id=?',[$id])->fetchAll(),'history'=>DB::execute('SELECT h.*,s.name AS actor FROM order_status_history h LEFT JOIN staff s ON s.id=h.staff_id WHERE h.order_id=? ORDER BY h.id',[$id])->fetchAll(),'payments'=>DB::execute('SELECT * FROM payments WHERE order_id=? ORDER BY id',[$id])->fetchAll(),'notes'=>DB::execute('SELECT n.*,s.name AS actor FROM support_notes n JOIN staff s ON s.id=n.staff_id WHERE n.order_id=? ORDER BY n.id DESC',[$id])->fetchAll(),'invoice'=>DB::execute('SELECT * FROM invoices WHERE order_id=?',[$id])->fetch()]);}
    public function status(string $id):void{try{OrderStatusService::change((int)$id,(string)($_POST['status']??''),Validator::text($_POST['note']??'',0,500));Response::redirect('/admin/orders/'.$id);}catch(\Throwable $e){$this->failure($e,'/admin/orders/'.$id);}}
    public function payment(string $id):void{try{PaymentService::record((int)$id,$_POST);flash('success','Đã ghi nhận thanh toán.');Response::redirect('/admin/orders/'.$id);}catch(\Throwable $e){$this->failure($e,'/admin/orders/'.$id);}}
    public function invoice(string $id):void{try{$invoice=PaymentService::invoice((int)$id);Response::redirect('/admin/invoices/'.$invoice);}catch(\Throwable $e){$this->failure($e,'/admin/orders/'.$id);}}
    public function printInvoice(string $id):void{$invoice=DB::execute('SELECT i.*,o.code AS order_code,o.recipient,o.phone,o.address_line,o.total,o.payment_method FROM invoices i JOIN orders o ON o.id=i.order_id WHERE i.id=?',[$id])->fetch();if(!$invoice){Response::abort(404,'Hóa đơn không tồn tại.');}$this->admin('invoice',['title'=>'Hóa đơn '.$invoice['code'],'invoice'=>$invoice,'items'=>DB::execute('SELECT * FROM order_items WHERE order_id=?',[$invoice['order_id']])->fetchAll()]);}
    public function note(string $id):void{try{DB::transaction(static function()use($id):void{DB::execute('INSERT INTO support_notes(order_id,staff_id,note) VALUES (?,?,?)',[$id,\App\Core\Auth::staff()['id'],Validator::text($_POST['note']??'',2,2000)]);AuditService::record('support.note','order',(int)$id);});Response::redirect('/admin/orders/'.$id);}catch(\Throwable $e){$this->failure($e,'/admin/orders/'.$id);}}
}
