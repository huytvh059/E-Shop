<?php
declare(strict_types=1);
namespace App\Services;
use App\Models\Catalog;
final class CartService
{
    public static function items():array {$items=[];foreach($_SESSION['cart']??[] as $id=>$quantity){$product=Catalog::find((int)$id);if(!$product){unset($_SESSION['cart'][$id]);continue;}$product['quantity']=(int)$quantity;$product['subtotal']=(int)$product['price']*(int)$quantity;$items[]=$product;}return $items;}
    public static function total(array $items):int{return array_sum(array_column($items,'subtotal'));}
}
