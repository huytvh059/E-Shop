<?php
declare(strict_types=1);
function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function money(int|string $value): string { return number_format((int) $value, 0, ',', '.') . ' ₫'; }
function flash(string $key, ?string $value = null): ?string { if ($value !== null) { $_SESSION['_flash'][$key] = $value; return null; } $result = $_SESSION['_flash'][$key] ?? null; unset($_SESSION['_flash'][$key]); return $result; }

function status_label(string $value): string { return ['pending'=>'Chờ xác nhận','processing'=>'Đang xử lý','shipping'=>'Đang giao hàng','completed'=>'Đã hoàn thành','cancelled'=>'Đã hủy','unpaid'=>'Chưa thanh toán','paid'=>'Đã thanh toán','refunded'=>'Hoàn tiền','opening'=>'Tồn đầu kỳ','receipt'=>'Nhập kho','issue'=>'Xuất kho','sale'=>'Xuất theo đơn','cancellation'=>'Hoàn kho hủy đơn'][$value] ?? $value; }
