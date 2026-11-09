<?php
declare(strict_types=1);
namespace App\Controllers\Admin;
use App\Core\{Controller,Database as DB,Response,Validator};
use App\Models\Catalog;
use App\Services\{AuditService,UploadService};
final class ProductController extends Controller
{
    public function index():void{$q=mb_substr((string)($_GET['q']??''),0,100);$page=max(1,(int)($_GET['page']??1));$count=(int)DB::execute('SELECT COUNT(*) FROM products WHERE name LIKE ?',['%'.$q.'%'])->fetchColumn();$offset=($page-1)*20;$stmt=DB::connection()->prepare('SELECT p.*,c.name AS category_name,b.name AS brand_name FROM products p JOIN categories c ON c.id=p.category_id JOIN brands b ON b.id=p.brand_id WHERE p.name LIKE ? ORDER BY p.id DESC LIMIT 20 OFFSET ?');$stmt->bindValue(1,'%'.$q.'%');$stmt->bindValue(2,$offset,\PDO::PARAM_INT);$stmt->execute();$this->admin('products',['title'=>'Quản lý sản phẩm','products'=>$stmt->fetchAll(),'q'=>$q,'page'=>$page,'pages'=>(int)ceil($count/20)]);}
    public function form(?string $id=null):void{$product=$id?Catalog::find((int)$id,false):null;if($id&&!$product){Response::abort(404,'Không tìm thấy sản phẩm.');}$this->admin('product-form',['title'=>$id?'Chỉnh sửa sản phẩm':'Thêm sản phẩm','product'=>$product,'categories'=>Catalog::categories(),'brands'=>Catalog::brands()]);}
    public function save(?string $id=null):void
    {
        $uploaded=[];try{
            $data=[Validator::integer($_POST['category_id']??null),Validator::integer($_POST['brand_id']??null),Validator::text($_POST['sku']??'',2,60),Validator::text($_POST['name']??'',2,200),Validator::text($_POST['slug']??'',2,220),Validator::text($_POST['description']??'',0,20000),Validator::integer($_POST['price']??null,0,999999999999),Validator::integer($_POST['min_stock']??5,0,1000000),isset($_POST['is_featured'])?1:0,isset($_POST['is_active'])?1:0];
            if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$data[4])){throw new \DomainException('Slug chỉ nhận chữ thường, số và dấu gạch ngang.');}
            $uploaded=UploadService::images($_FILES['images']??[]);
            DB::transaction(static function()use($id,$data,$uploaded):void{
                if($id){if(!DB::execute('SELECT id FROM products WHERE id=? FOR UPDATE',[$id])->fetch()){throw new \DomainException('Sản phẩm không tồn tại.');}DB::execute('UPDATE products SET category_id=?,brand_id=?,sku=?,name=?,slug=?,description=?,price=?,min_stock=?,is_featured=?,is_active=? WHERE id=?',[...$data,$id]);$productId=(int)$id;}
                else{DB::execute('INSERT INTO products(category_id,brand_id,sku,name,slug,description,price,min_stock,is_featured,is_active) VALUES (?,?,?,?,?,?,?,?,?,?)',$data);$productId=(int)DB::connection()->lastInsertId();}
                DB::execute('DELETE FROM product_specs WHERE product_id=?',[$productId]);$keys=$_POST['spec_keys']??[];$values=$_POST['spec_values']??[];if(!is_array($keys)||!is_array($values)||count($keys)>60){throw new \DomainException('Thông số không hợp lệ, tối đa 60 dòng.');}$seen=[];foreach($keys as $i=>$key){if(trim((string)$key)===''){continue;}$key=Validator::text($key,1,100);if(isset($seen[$key])){throw new \DomainException('Tên thông số bị trùng.');}$seen[$key]=true;DB::execute('INSERT INTO product_specs(product_id,spec_key,spec_value,sort_order) VALUES (?,?,?,?)',[$productId,$key,Validator::text($values[$i]??'',1,500),$i]);}
                foreach($uploaded as $i=>$path){DB::execute('INSERT INTO product_images(product_id,path,sort_order) VALUES (?,?,?)',[$productId,$path,$i+100]);}
                foreach((array)($_POST['remove_images']??[]) as $imageId){DB::execute('DELETE FROM product_images WHERE id=? AND product_id=?',[(int)$imageId,$productId]);}
                AuditService::record($id?'product.update':'product.create','product',$productId);
            });flash('success','Đã lưu sản phẩm. Tồn kho được cập nhật bằng phiếu kho.');Response::redirect('/admin/products');
        }catch(\Throwable $e){foreach($uploaded as $path){if(is_file(BASE_PATH.'/public'.$path)){unlink(BASE_PATH.'/public'.$path);}}$this->failure($e,$id?'/admin/products/'.$id.'/edit':'/admin/products/create');}
    }
    public function delete(string $id):void{try{DB::transaction(static function()use($id):void{DB::execute('UPDATE products SET is_active=0 WHERE id=?',[$id]);AuditService::record('product.deactivate','product',(int)$id);});flash('success','Đã ngừng bán sản phẩm, lịch sử giao dịch được giữ nguyên.');Response::redirect('/admin/products');}catch(\Throwable $e){$this->failure($e,'/admin/products');}}
    public function taxonomy(string $type):void{$table=$type==='categories'?'categories':'brands';$this->admin('taxonomy',['title'=>$type==='categories'?'Danh mục':'Thương hiệu','type'=>$table,'records'=>DB::execute('SELECT * FROM '.$table.' ORDER BY id')->fetchAll()]);}
    public function taxonomySave(string $type):void{try{$table=$type==='categories'?'categories':'brands';$id=(int)($_POST['id']??0);DB::transaction(static function()use($table,$id):void{if(($_POST['action']??'')==='delete'){DB::execute('DELETE FROM '.$table.' WHERE id=?',[$id]);AuditService::record($table.'.delete',$table,$id);return;}$name=Validator::text($_POST['name']??'',1,100);$slug=Validator::text($_POST['slug']??'',1,120);if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)){throw new \DomainException('Slug không hợp lệ.');}if($id){DB::execute('UPDATE '.$table.' SET name=?,slug=? WHERE id=?',[$name,$slug,$id]);}else{DB::execute('INSERT INTO '.$table.'(name,slug) VALUES (?,?)',[$name,$slug]);}$newId=$id?: (int)DB::connection()->lastInsertId();AuditService::record($table.'.save',$table,$newId);});Response::redirect('/admin/'.$type);}catch(\Throwable $e){$this->failure($e,'/admin/'.$type);}}
}
