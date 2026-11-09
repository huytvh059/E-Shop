<?php
declare(strict_types=1);
namespace App\Controllers\Admin;
use App\Core\Controller;
use App\Services\ReportService;
final class ReportController extends Controller
{
    public function index():void{try{$this->admin('reports',ReportService::data($_GET)+['title'=>'Đối chiếu doanh thu']);}catch(\Throwable $e){$this->failure($e,'/admin');}}
}
