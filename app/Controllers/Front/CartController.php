<?php
declare(strict_types=1);
namespace App\Controllers\Front;
use App\Core\{Controller,Response,Validator,Auth,Database as DB};
use App\Models\Catalog;
use App\Services\{CartService,OrderService};
final class CartController extends Controller
{
    public function index():void{$items=CartService::items();$this->front('cart',['title'=>'Giỏ hàng của bạn','items'=>$items,'total'=>CartService::total($items)]);}
    public function update():void{try{$id=Validator::integer($_POST['product_id']??null);$action=$_POST['action']??'add';$qty=Validator::integer($_POST['quantity']??1,0,999);if($action==='remove'||$qty===0){unset($_SESSION['cart'][$id]);}else{$product=Catalog::find($id);if(!$product){throw new \DomainException('Không tìm thấy sản phẩm.');}$new=$action==='add'?($_SESSION['cart'][$id]??0)+$qty:$qty;if($new>(int)$product['stock']){throw new \DomainException('Số lượng vượt quá tồn kho hiện tại.');}$_SESSION['cart'][$id]=$new;}Response::json(['message'=>'Đã cập nhật giỏ hàng.','count'=>array_sum($_SESSION['cart']??[])]);}catch(\Throwable $e){$this->failure($e,'/cart');}}
    public function checkout():void{$items=CartService::items();if(!$items){Response::redirect('/cart');}$_SESSION['checkout_token']??=bin2hex(random_bytes(32));$this->front('checkout',['title'=>'Thông tin thanh toán','items'=>$items,'total'=>CartService::total($items),'user'=>Auth::user(),'addresses'=>DB::execute('SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC,id', [Auth::user()['id']])->fetchAll()]);}
    public function place():void{try{$token=(string)($_POST['checkout_token']??'');$user=Auth::user();$existing=DB::execute('SELECT id FROM orders WHERE checkout_token=? AND user_id=?',[$token,$user['id']])->fetchColumn();if($existing){Response::redirect('/orders/'.$existing);}if(!isset($_SESSION['checkout_token'])||!hash_equals($_SESSION['checkout_token'],$token)){throw new \DomainException('Phiên đặt hàng đã hết hạn.');}$order=OrderService::place((int)$user['id'],$_SESSION['cart']??[],$_POST,$token);unset($_SESSION['cart'],$_SESSION['checkout_token']);flash('success','Đặt hàng thành công! Mã đơn: '.$order['code']);Response::redirect('/orders/'.$order['id']);}catch(\Throwable $e){$this->failure($e,'/checkout');}}
}
