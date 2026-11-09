<?php
declare(strict_types=1);
namespace App\Controllers\Front;
use App\Core\{Controller,Response,Validator};
use App\Models\Catalog;
final class CatalogController extends Controller
{
    public function home():void {$this->front('home',['title'=>'Công nghệ cho mọi khởi đầu','featured'=>Catalog::search(['featured'=>1],1,8)['products'],'latest'=>Catalog::search([],1,4)['products'],'categories'=>Catalog::categories()]);}
    public function index():void {$filters=array_filter($_GET,static fn($v)=>is_scalar($v));$data=Catalog::search($filters,max(1,(int)($filters['page']??1)));$this->front('catalog',$data+['title'=>'Khám phá sản phẩm','filters'=>$filters,'categories'=>Catalog::categories(),'brands'=>Catalog::brands()]);}
    public function detail(string $id):void {$product=Catalog::find((int)$id);if(!$product){Response::abort(404,'Sản phẩm không tồn tại.');}$this->front('product',['title'=>$product['name'],'product'=>$product]);}
    public function compare():void {
        $products=[];foreach($_SESSION['compare']??[] as $id){if($p=Catalog::find((int)$id)){$products[]=$p;}}
        $_SESSION['compare']=array_column($products,'id');$keys=[];foreach($products as $p){foreach($p['specs'] as $s){$keys[$s['spec_key']]=true;}}
        $this->front('compare',['title'=>'So sánh sản phẩm','products'=>$products,'keys'=>array_keys($keys)]);
    }
    public function updateCompare():void {try{$id=Validator::integer($_POST['product_id']??null);$ids=$_SESSION['compare']??[];if(($_POST['action']??'add')==='remove'){$ids=array_values(array_diff($ids,[$id]));}else{ $p=Catalog::find($id);if(!$p){throw new \DomainException('Sản phẩm không tồn tại.');}if(!in_array($id,$ids)){if(count($ids)>=4){throw new \DomainException('Chỉ được so sánh tối đa 4 sản phẩm.');}if($ids){$first=Catalog::find((int)$ids[0]);if($first && $first['category_id']!==$p['category_id']){throw new \DomainException('Chỉ so sánh sản phẩm cùng danh mục.');}}$ids[]=$id;}}$_SESSION['compare']=$ids;Response::json(['message'=>'Đã cập nhật danh sách so sánh.','compareCount'=>count($ids)]);}catch(\Throwable $e){$this->failure($e,'/compare');}}
}
