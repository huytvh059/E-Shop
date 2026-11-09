<?php
declare(strict_types=1);
use App\Controllers\Front\{CatalogController,AccountController,CartController,OrderController,HealthController};
use App\Controllers\Admin;
use App\Middleware\{AuthMiddleware,RoleMiddleware};
$router->add('GET','/health',[HealthController::class,'index']);
foreach([['/','home'],['/products','index'],['/products/{id}','detail'],['/compare','compare']] as [$path,$method]){$router->add('GET',$path,[CatalogController::class,$method]);}
$router->add('POST','/compare/update',[CatalogController::class,'updateCompare']);
foreach([['GET','/login','loginForm'],['POST','/login','login'],['GET','/register','registerForm'],['POST','/register','register'],['POST','/logout','logout']] as [$verb,$path,$method]){$router->add($verb,$path,[AccountController::class,$method]);}
$customer=[static fn()=>AuthMiddleware::customer()];
foreach([['GET','/account','profile'],['POST','/account','update'],['POST','/account/password','password'],['POST','/account/addresses','address'],['POST','/account/addresses/{id}','addressAction']] as [$verb,$path,$method]){$router->add($verb,$path,[AccountController::class,$method],$customer);}
$router->add('GET','/cart',[CartController::class,'index']);$router->add('POST','/cart/update',[CartController::class,'update']);
$router->add('GET','/checkout',[CartController::class,'checkout'],$customer);$router->add('POST','/checkout',[CartController::class,'place'],$customer);
$router->add('GET','/orders',[OrderController::class,'index'],$customer);$router->add('GET','/orders/{id}',[OrderController::class,'detail'],$customer);
$router->add('GET','/admin/login',[Admin\AuthController::class,'form']);$router->add('POST','/admin/login',[Admin\AuthController::class,'login']);
$router->add('POST','/admin/logout',[Admin\AuthController::class,'logout'],[static fn()=>AuthMiddleware::staff()]);
$admin=static function(string $verb,string $path,string $class,string $method,string $permission)use($router):void{$router->add($verb,$path,[$class,$method],[static fn()=>RoleMiddleware::require($permission)]);};
$admin('GET','/admin',Admin\DashboardController::class,'index','dashboard');
foreach([['GET','/admin/products','index'],['GET','/admin/products/create','form'],['POST','/admin/products/create','save'],['GET','/admin/products/{id}/edit','form'],['POST','/admin/products/{id}/edit','save'],['POST','/admin/products/{id}/delete','delete']] as [$verb,$path,$method]){$admin($verb,$path,Admin\ProductController::class,$method,'products.manage');}
foreach(['categories','brands'] as $type){$router->add('GET','/admin/'.$type,static function()use($type):void{(new Admin\ProductController())->taxonomy($type);},[static fn()=>RoleMiddleware::require('products.manage')]);$router->add('POST','/admin/'.$type,static function()use($type):void{(new Admin\ProductController())->taxonomySave($type);},[static fn()=>RoleMiddleware::require('products.manage')]);}
$admin('GET','/admin/orders',Admin\OrderController::class,'index','orders.view');$admin('GET','/admin/orders/{id}',Admin\OrderController::class,'detail','orders.view');
// Service kiểm tra thêm quyền cụ thể của trạng thái được yêu cầu.
$admin('POST','/admin/orders/{id}/status',Admin\OrderController::class,'status','orders.view');
$admin('POST','/admin/orders/{id}/payments',Admin\OrderController::class,'payment','payments.manage');$admin('POST','/admin/orders/{id}/invoice',Admin\OrderController::class,'invoice','invoices.manage');$admin('GET','/admin/invoices/{id}',Admin\OrderController::class,'printInvoice','invoices.manage');$admin('POST','/admin/orders/{id}/notes',Admin\OrderController::class,'note','support.manage');
$admin('GET','/admin/inventory',Admin\InventoryController::class,'index','inventory.view');$admin('POST','/admin/inventory',Admin\InventoryController::class,'move','inventory.manage');$admin('GET','/admin/reports',Admin\ReportController::class,'index','reports.view');
$admin('GET','/admin/customers',Admin\UserController::class,'customers','customers.view');$admin('POST','/admin/customers/{id}/toggle',Admin\UserController::class,'toggle','customers.manage');$admin('GET','/admin/staff',Admin\UserController::class,'staff','staff.manage');$admin('POST','/admin/staff',Admin\UserController::class,'saveStaff','staff.manage');
$admin('GET','/admin/system',Admin\SystemController::class,'index','system.view');$admin('GET','/admin/audit',Admin\SystemController::class,'audit','system.view');
