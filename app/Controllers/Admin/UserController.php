<?php
declare(strict_types=1);
namespace App\Controllers\Admin;
use App\Core\{Controller,Database as DB,Auth,Response,Validator};
use App\Services\AuditService;
final class UserController extends Controller
{
    public function customers():void{$this->admin('customers',['title'=>'Khách hàng','users'=>DB::execute('SELECT id,name,email,phone,is_active,created_at FROM users ORDER BY id DESC')->fetchAll()]);}
    public function toggle(string $id):void{try{DB::transaction(static function()use($id):void{DB::execute('UPDATE users SET is_active=NOT is_active WHERE id=?',[$id]);AuditService::record('customer.toggle','user',(int)$id);});Response::redirect('/admin/customers');}catch(\Throwable $e){$this->failure($e,'/admin/customers');}}
    public function staff():void{$this->admin('staff',['title'=>'Nhân viên & phân quyền','staff'=>DB::execute('SELECT s.id,s.name,s.email,s.role_id,s.is_active,r.label FROM staff s JOIN roles r ON r.id=s.role_id ORDER BY s.id')->fetchAll(),'roles'=>DB::execute('SELECT * FROM roles ORDER BY id')->fetchAll()]);}
    public function saveStaff():void{try{DB::transaction(static function():void{
        // Khóa toàn bộ tập quản trị viên để không thể đồng thời vô hiệu quản trị viên cuối.
        $admins=DB::execute("SELECT s.id FROM staff s JOIN roles r ON r.id=s.role_id WHERE r.name='admin' AND s.is_active=1 ORDER BY s.id FOR UPDATE")->fetchAll();
        $id=(int)($_POST['id']??0);$role=Validator::integer($_POST['role_id']??null);$active=isset($_POST['is_active'])?1:0;$name=Validator::text($_POST['name']??'',2,120);$email=Validator::email($_POST['email']??'');
        $roleName=DB::execute('SELECT name FROM roles WHERE id=?',[$role])->fetchColumn();if(!$roleName){throw new \DomainException('Vai trò không tồn tại.');}
        if($id){$old=DB::execute('SELECT * FROM staff WHERE id=? FOR UPDATE',[$id])->fetch();if(!$old){throw new \DomainException('Nhân viên không tồn tại.');}$adminIds=array_column($admins,'id');if(in_array($id,$adminIds)&&count($adminIds)===1&&(!$active||$roleName!=='admin')){throw new \DomainException('Không thể vô hiệu quản trị viên cuối cùng.');}DB::execute('UPDATE staff SET name=?,email=?,role_id=?,is_active=? WHERE id=?',[$name,$email,$role,$active,$id]);if(!empty($_POST['password'])){DB::execute('UPDATE staff SET password_hash=? WHERE id=?',[password_hash(Validator::text($_POST['password'],10,72),PASSWORD_DEFAULT),$id]);}}
        else{DB::execute('INSERT INTO staff(name,email,role_id,is_active,password_hash) VALUES (?,?,?,?,?)',[$name,$email,$role,$active,password_hash(Validator::text($_POST['password']??'',10,72),PASSWORD_DEFAULT)]);$id=(int)DB::connection()->lastInsertId();}AuditService::record('staff.save','staff',$id,['role'=>$roleName,'active'=>$active]);
    });Response::redirect('/admin/staff');}catch(\Throwable $e){$this->failure($e,'/admin/staff');}}
}
