<?php
declare(strict_types=1);
namespace App\Services;
use App\Core\{Database as DB,Validator};
final class ReportService
{
    public static function data(array $filters):array
    {
        $start=(string)($filters['start']??date('Y-m-01'));$end=(string)($filters['end']??date('Y-m-d'));
        foreach([$start,$end] as $date){$d=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);if(!$d||$d->format('Y-m-d')!==$date){throw new \DomainException('Ngày báo cáo không hợp lệ.');}}
        if($start>$end||strtotime($end)-strtotime($start)>366*86400){throw new \DomainException('Khoảng báo cáo tối đa 366 ngày.');}
        $month=($filters['group']??'day')==='month';$format=$month?'%Y-%m':'%Y-%m-%d';$status=$filters['payment_status']??'';
        if(!in_array($status,['','unpaid','paid','refunded'],true)){throw new \DomainException('Trạng thái thanh toán không hợp lệ.');}
        $params=[$start,$end.' 23:59:59'];$extra='';if($status!==''){$extra=' AND status=?';$params[]=$status;}
        // Tách tổng tiền thanh toán và doanh thu đơn để không nhân bản khi JOIN nhiều chi tiết.
        $payments=DB::execute("SELECT DATE_FORMAT(COALESCE(paid_at,created_at),'$format') AS period,SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) AS received,SUM(CASE WHEN status='refunded' THEN amount ELSE 0 END) AS refunded,SUM(CASE WHEN status='unpaid' THEN amount ELSE 0 END) AS unpaid FROM payments WHERE COALESCE(paid_at,created_at) BETWEEN ? AND ?".$extra.' GROUP BY period ORDER BY period',$params)->fetchAll();
        $orders=DB::execute("SELECT DATE_FORMAT((SELECT MAX(h.created_at) FROM order_status_history h WHERE h.order_id=orders.id AND h.to_status='completed'),'$format') AS period,SUM(total) AS revenue,COUNT(*) AS completed FROM orders WHERE status='completed' AND (SELECT MAX(h.created_at) FROM order_status_history h WHERE h.order_id=orders.id AND h.to_status='completed') BETWEEN ? AND ? GROUP BY period ORDER BY period",[$start,$end.' 23:59:59'])->fetchAll();
        $map=[];foreach($payments as $p){$map[$p['period']]=$p+['revenue'=>0,'completed'=>0];}foreach($orders as $o){$map[$o['period']]=array_merge($map[$o['period']]??['period'=>$o['period'],'received'=>0,'refunded'=>0,'unpaid'=>0],$o);}ksort($map);
        return ['rows'=>array_values($map),'start'=>$start,'end'=>$end,'group'=>$month?'month':'day','payment_status'=>$status];
    }
}
