<?php
declare(strict_types=1);
require __DIR__.'/core.php';
use App\Core\{Database as DB,Env,Auth};
use App\Services\{OrderService,OrderStatusService,InventoryService,PaymentService,ReportService};
if(Env::get('DB_NAME')!=='eshop_test'){throw new RuntimeException('Use eshop_test only.');}
$_SESSION['staff_id']=1;
$input=['recipient'=>'Test Customer','phone'=>'0900000000','address_line'=>'Test address for transaction','payment_method'=>'cod'];
$token=bin2hex(random_bytes(32));$before=(int)DB::execute('SELECT stock FROM products WHERE id=1')->fetchColumn();
$order=OrderService::place(1,[1=>2],$input,$token);
check((int)DB::execute('SELECT stock FROM products WHERE id=1')->fetchColumn()===$before-2,'checkout stock decrement');
$again=OrderService::place(1,[1=>2],$input,$token);check($order['id']===$again['id'],'checkout idempotency');
try{OrderService::place(1,[1=>999],$input,bin2hex(random_bytes(32)));throw new RuntimeException('oversell allowed');}catch(DomainException){echo "PASS: oversell rejected\n";}
check((int)DB::execute('SELECT stock FROM products WHERE id=1')->fetchColumn()===$before-2,'failed checkout rollback');
try{OrderStatusService::change((int)$order['id'],'completed');throw new RuntimeException('invalid transition accepted');}catch(DomainException){echo "PASS: invalid transition rejected\n";}
OrderStatusService::change((int)$order['id'],'cancelled');
check((int)DB::execute('SELECT stock FROM products WHERE id=1')->fetchColumn()===$before,'cancel restores stock');
try{OrderStatusService::change((int)$order['id'],'cancelled');throw new RuntimeException('double cancel accepted');}catch(DomainException){echo "PASS: duplicate cancellation rejected\n";}
check((int)DB::execute('SELECT stock FROM products WHERE id=1')->fetchColumn()===$before,'no duplicate stock restoration');
$lifecycle=OrderService::place(1,[2=>1],$input,bin2hex(random_bytes(32)));
$_SESSION['staff_id']=2;OrderStatusService::change((int)$lifecycle['id'],'processing');
$_SESSION['staff_id']=3;OrderStatusService::change((int)$lifecycle['id'],'shipping');
try{OrderStatusService::change((int)$lifecycle['id'],'completed');throw new RuntimeException('warehouse completed allowed');}catch(DomainException){echo "PASS: warehouse cannot complete\n";}
$_SESSION['staff_id']=2;OrderStatusService::change((int)$lifecycle['id'],'completed');
check(DB::execute('SELECT status FROM orders WHERE id=?',[$lifecycle['id']])->fetchColumn()==='completed','full order lifecycle');
$_SESSION['staff_id']=4;$payment=['amount'=>100000,'status'=>'paid','method'=>'cod','request_token'=>bin2hex(random_bytes(32)),'paid_at'=>date('Y-m-d\TH:i')];PaymentService::record((int)$lifecycle['id'],$payment);PaymentService::record((int)$lifecycle['id'],$payment);
check((int)DB::execute('SELECT COUNT(*) FROM payments WHERE order_id=?',[$lifecycle['id']])->fetchColumn()===1,'payment idempotency');
try{PaymentService::record((int)$lifecycle['id'],array_merge($payment,['amount'=>100001,'status'=>'refunded','request_token'=>bin2hex(random_bytes(32))]));throw new RuntimeException('overrefund allowed');}catch(DomainException){echo "PASS: excessive refund rejected\n";}
$invoice=PaymentService::invoice((int)$lifecycle['id']);check($invoice===PaymentService::invoice((int)$lifecycle['id']),'unique invoice');
$report=ReportService::data(['start'=>date('Y-m-01'),'end'=>date('Y-m-d')]);check(array_sum(array_column($report['rows'],'received'))>=100000,'revenue report receipt');
$_SESSION['staff_id']=3;$stock=(int)DB::execute('SELECT stock FROM products WHERE id=3')->fetchColumn();$movement=['product_id'=>3,'quantity'=>2,'type'=>'receipt','reason'=>'Integration test receipt','request_token'=>bin2hex(random_bytes(32))];InventoryService::move($movement);InventoryService::move($movement);check((int)DB::execute('SELECT stock FROM products WHERE id=3')->fetchColumn()===$stock+2,'stock movement idempotency');
try{InventoryService::move(array_merge($movement,['quantity'=>1000000,'type'=>'issue','request_token'=>bin2hex(random_bytes(32))]));throw new RuntimeException('negative stock allowed');}catch(DomainException){echo "PASS: excessive stock issue rejected\n";}
foreach([2=>['orders.confirm','orders.complete'],3=>['inventory.manage','orders.ship'],4=>['payments.manage','reports.view'],5=>['support.manage','customers.view']] as $staff=>$permissions){$_SESSION['staff_id']=$staff;foreach($permissions as $permission){check(Auth::can($permission),'RBAC allow '.$staff.' '.$permission);}check(!Auth::can('staff.manage'),'RBAC deny staff management '.$staff);}
$_SESSION['staff_id']=1;
// Hai tiến trình PHP, hai người mua và hai kết nối DB thật cùng tranh 1 sản phẩm tồn.
$available=(int)DB::execute('SELECT stock FROM products WHERE id=30')->fetchColumn();if($available!==1){InventoryService::move(['product_id'=>30,'quantity'=>abs($available-1),'type'=>$available>1?'issue':'receipt','reason'=>'Chuẩn bị kiểm thử tranh chấp tồn kho','request_token'=>bin2hex(random_bytes(32))]);}$suffix=bin2hex(random_bytes(6));$processes=[];$pipes=[];
foreach([1,2] as $index){$command=[PHP_BINARY,__DIR__.'/checkout-worker.php',(string)$index,$suffix.'-'.$index];$processes[$index]=proc_open($command,[1=>['pipe','w'],2=>['pipe','w']],$pipes[$index],BASE_PATH);}
$success=0;$failed=0;foreach($processes as $index=>$process){$out=stream_get_contents($pipes[$index][1]);$err=stream_get_contents($pipes[$index][2]);fclose($pipes[$index][1]);fclose($pipes[$index][2]);$exit=proc_close($process);$result=json_decode($out,true);check($exit===0&&is_array($result),'concurrent worker '.$index.' '.$err);if($result['ok']){$success++;}else{$failed++;}}
check($success===1&&$failed===1,'concurrent checkout exactly one succeeds');check((int)DB::execute('SELECT stock FROM products WHERE id=30')->fetchColumn()===0,'concurrent checkout stock never negative');
echo "Business tests complete. Test DB now contains modified fixtures; reinitialize before re-running seed baseline tests.\n";
