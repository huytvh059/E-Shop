<?php
declare(strict_types=1);
namespace App\Core;
class Controller
{
    protected function front(string $view,array $data=[]): void { View::render('front/'.$view,$data,'front'); }
    protected function admin(string $view,array $data=[]): void { View::render('admin/'.$view,$data,'admin'); }
    protected function input(): array { return $_POST; }
    protected function failure(\Throwable $error,string $redirect): never
    {
        $message = $error instanceof \InvalidArgumentException || $error instanceof \DomainException ? $error->getMessage() : 'Không thể xử lý yêu cầu. Vui lòng thử lại.';
        if (!($error instanceof \InvalidArgumentException) && !($error instanceof \DomainException)) {Logger::error('Operation failed',['type'=>get_class($error),'line'=>$error->getLine(),'file'=>basename($error->getFile())]);}
        if (str_contains($_SERVER['HTTP_ACCEPT']??'','application/json')) {Response::json(['error'=>$message],422);}
        flash('error',$message); Response::redirect($redirect);
    }
}
