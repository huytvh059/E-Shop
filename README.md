# E-Shop

Website bán thiết bị điện tử, phục vụ đồ án triển khai **Web Server và Database Server riêng biệt**.

**Công nghệ:** PHP 8.3 MVC thuần · PDO · MariaDB 10.11 · Nginx/PHP-FPM · Bootstrap 5 · JavaScript · Chart.js.

## Chức năng
- Catalog, tìm kiếm/lọc, so sánh 2–4 sản phẩm, tài khoản và địa chỉ nhận hàng.
- Giỏ AJAX, checkout COD/chuyển khoản; transaction khóa dòng chống bán vượt tồn.
- Quản trị sản phẩm, đơn hàng, kho, thanh toán, hóa đơn và báo cáo.
- RBAC 5 vai trò, audit log, giám sát hệ thống và endpoint `/health`.

## Triển khai Ubuntu

Yêu cầu Ubuntu 24.04, PHP 8.3 với `pdo_mysql`, `mbstring`, `gd`, `intl`, `fileinfo`; MariaDB 10.11 trên máy DB riêng.

```bash
git clone https://github.com/huytvh059/E-Shop.git /var/www/eshop
cp /var/www/eshop/.env.example /var/www/eshop/.env
```

1. Trên DB Server: tạo database/user giới hạn IP Web Server; import [schema](database/schema.sql), [seed demo](database/seed.sql) vào DB mới.
2. Điền `.env`: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_URL`, `APP_DEBUG=false`.
3. Nginx trỏ root vào `public/`; chỉ cấp quyền ghi cho `storage/logs/` và `public/uploads/`.
4. Bật PHP-FPM, kiểm tra `/health`; dùng HTTPS trước khi vận hành dữ liệu thật.

**Hướng dẫn từng bước:** [Cài đặt hai máy Ubuntu](deploy/install.md) · [Nginx](deploy/nginx-eshop.conf) · [PHP-FPM](deploy/php-fpm.md).

Không cần Composer/npm để chạy. Bootstrap, Chart.js và Google Fonts dùng CDN.

## Tài khoản demo

Mật khẩu chung: `password`. Nhân viên đăng nhập tại `/admin/login`, khách tại `/login`.

| Vai trò | Email |
|---|---|
| Admin | admin@eshop.test |
| Sales | sales@eshop.test |
| Warehouse | warehouse@eshop.test |
| Accountant | accountant@eshop.test |
| Support | support@eshop.test |
| Khách hàng | customer1@eshop.test |

Seed gồm 30 sản phẩm, 5 danh mục, 8 thương hiệu, 5 nhân viên và 3 khách hàng. Chi tiết: [DEMO_ACCESS.md](DEMO_ACCESS.md).

> **Chỉ dùng tài khoản demo trong lab. Thay mật khẩu/khóa tài khoản trước khi công khai. Không đưa `.env` hoặc bản sao lưu DB lên Git.**

## Kiểm thử

```bash
php tests/core.php
php tests/database.php
php tests/business.php
```

Dùng DB test mới import seed; chạy kiểm baseline trước kiểm nghiệp vụ. Test nghiệp vụ/HTTP thay đổi dữ liệu, **không chạy trên DB thật**.

[Kết quả và giới hạn kiểm chứng](tests/RESULTS.md) · [Giả định nghiệp vụ](docs/ASSUMPTIONS.md). Đã kiểm PHP/MariaDB cục bộ; chưa nghiệm thu cài sạch hai Ubuntu/Nginx.

## Quy tắc Git

Không commit `.env`, `.tools/`, log, ảnh người dùng upload, backup, dependencies hoặc cấu hình IDE. Giữ `.env.example`, schema/seed, ảnh mẫu và các tệp `.gitkeep`. Dữ liệu DB/uploads phải được backup, chuyển riêng; clone repo không khôi phục dữ liệu đang vận hành.
