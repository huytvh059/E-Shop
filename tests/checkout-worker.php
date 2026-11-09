<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
try{$order=App\Services\OrderService::place((int)$argv[1],[30=>1],['recipient'=>'Concurrency Test','phone'=>'0900000000','address_line'=>'Test address concurrency only','payment_method'=>'cod'],hash('sha256',$argv[2]));echo json_encode(['ok'=>true,'id'=>$order['id']]);}catch(DomainException $e){echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);}
