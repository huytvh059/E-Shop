<?php
declare(strict_types=1);
namespace App\Controllers\Admin;
use App\Core\{Controller,Auth,Response,View};
use App\Services\{LoginService,AuditService};
final class AuthController extends Controller
{
    public function form():void{View::render('admin/login',['title'=>'Đăng nhập nhân viên']);}
    public function login():void{try{LoginService::attempt($_POST,true);AuditService::record('staff.login','staff',(int)Auth::staff()['id']);Response::redirect('/admin');}catch(\Throwable $e){$this->failure($e,'/admin/login');}}
    public function logout():void{AuditService::record('staff.logout','staff',(int)Auth::staff()['id']);Auth::logout(true);Response::redirect('/admin/login');}
}
