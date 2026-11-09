<?php
declare(strict_types=1);
namespace App\Controllers\Admin;
use App\Core\{Controller,Database as DB};
final class SystemController extends Controller
{
    public function index():void
    {
        $start=microtime(true);$db=false;$version='Không khả dụng';try{$version=(string)DB::execute('SELECT VERSION()')->fetchColumn();$db=true;}catch(\Throwable){}
        $latency=round((microtime(true)-$start)*1000,2);$lines=[];$file=BASE_PATH.'/storage/logs/app.log';
        // Đọc từ cuối tệp, giới hạn 64 KB để log lớn không làm cạn bộ nhớ.
        if(is_file($file)){$handle=fopen($file,'rb');if($handle){$size=filesize($file);fseek($handle,max(0,$size-65536));$text=stream_get_contents($handle);fclose($handle);$lines=array_slice(explode("\n",trim($text)),-50);}}
        $this->admin('system',['title'=>'Giám sát hệ thống','db'=>$db,'version'=>$version,'latency'=>$latency,'free'=>disk_free_space(BASE_PATH),'total'=>disk_total_space(BASE_PATH),'lines'=>$lines]);
    }
    public function audit():void{$this->admin('audit',['title'=>'Nhật ký kiểm toán','logs'=>DB::execute('SELECT a.*,s.name AS actor FROM audit_logs a LEFT JOIN staff s ON s.id=a.staff_id ORDER BY a.id DESC LIMIT 300')->fetchAll()]);}
}
