# Kết quả kiểm thử

Ngày: 09–10/11/2026. Môi trường: Windows x64, PHP 8.3.36 portable chính thức (đã kiểm SHA256), MariaDB 10.11.11 portable chính thức (đã kiểm SHA256). DB chỉ listen 127.0.0.1:33077; web phát triển 127.0.0.1:8080. Không cài Windows service, không sửa firewall.

## Đã chạy thành công
- PHP native lint: 74 tệp dưới app/config/public/tests + bootstrap.php, không lỗi cú pháp.
- PHP parser bổ sung: 75 tệp, không lỗi. JavaScript node --check: app.js và các test HTTP.
- Docker Compose config --quiet: hợp lệ; không chạy container vì Docker Engine chưa hoạt động.
- tests/core.php: 8 kiểm tra CSRF, escape XSS, email, integer, route động, password hash.
- tests/database.php trên DB seed sạch độc lập: 20 kiểm tra; đủ 5 danh mục, 8 hãng, 30 sản phẩm, 5 staff, 3 khách, 5 đơn; sổ kho khớp stock, tổng đơn khớp items, password demo xác minh được, đủ thông số và rollback.
- tests/business.php: 40 kiểm tra gồm Core; đặt hàng/trừ tồn, token chống lặp, vượt tồn rollback, state machine, hủy hoàn kho một lần, vòng đời sales/warehouse, thanh toán chống lặp, chặn hoàn vượt tiền, hóa đơn duy nhất, báo cáo, phiếu kho và RBAC.
- Kiểm tranh chấp: hai tiến trình PHP riêng, hai khách, hai kết nối MariaDB tranh một sản phẩm còn 1; đúng 1 thành công, 1 bị từ chối, tồn cuối bằng 0.
- tests/http.cjs: 59 kiểm tra HTTP; login năm vai trò, allow/deny URL trực tiếp, support không đổi trạng thái, CSRF 419, tìm/lọc/chi tiết, tài khoản, quyền sở hữu đơn và đăng ký.
- tests/admin-http.cjs: 10 kiểm tra; MIME giả bị từ chối, JPEG thật được mã hóa lại và đổi tên, tạo sản phẩm/thông số, hiển thị và ngừng bán.
- tests/login-limit.cjs: chặn đăng nhập sau 5 lỗi; hiển thị thời gian chờ 15 phút.
- Trình duyệt đã mở các trang chủ desktop/mobile, catalog, chi tiết, so sánh, giỏ, checkout, xác nhận đơn, dashboard. Có ảnh/recording lưu trong artifacts của phiên làm việc.

## Lỗi tìm thấy và đã sửa
- Hash mật khẩu demo ban đầu không khớp chuỗi password: thay seed bằng hash bcrypt sinh thực, kiểm lại bằng password_verify và đăng nhập HTTP tất cả vai trò.
- Fixture PNG kiểm upload bị lỗi giải mã: thay bằng JPEG hợp lệ; không bỏ kiểm MIME hoặc giảm bảo mật upload.
- Báo cáo doanh thu chuyển từ updated_at sang thời điểm lịch sử completed để không bị lệch khi cập nhật khác.
- Chuẩn bị test tranh chấp qua InventoryService thay vì cập nhật tồn trực tiếp, giữ nguyên tắc sổ kho.

## Chưa được xác nhận trên môi trường đích
- Chưa cài mới hai máy Ubuntu riêng; chưa chạy nginx -t/PHP-FPM/TLS/UFW trên Ubuntu thật.
- Kiểm tranh chấp cục bộ chứng minh khóa dòng MariaDB; chưa phải kiểm tải hoặc chứng minh mọi tình huống mạng/deadlock.
- Không khẳng định đạt kiểm toán bảo mật độc lập. Một số màn hình quản trị (khách/nhân viên/kho) hiển thị toàn bộ tập nhỏ phù hợp đồ án; lịch sử kho/audit giới hạn số dòng gần nhất.
- CDN cần Internet phía trình duyệt; ảnh và backend chạy cục bộ.
- DB demo cục bộ chứa thêm dữ liệu do kiểm thử. Seed bàn giao vẫn là baseline sạch, số lượng quy định.

## Chạy lại
```bash
php tests/core.php
php tests/database.php
php tests/business.php
node tests/http.cjs
node tests/admin-http.cjs
node tests/login-limit.cjs
```
Chạy database.php trước business.php trên DB test mới seed. Các test business/HTTP thay đổi dữ liệu; không chạy với DB thật. HTTP tests yêu cầu web đang chạy; có thể đổi TEST_URL. Business test yêu cầu DB_NAME=eshop_test.
- Đã kiểm health DB lỗi: HTTP 503 với db=down; trang catalog trả 500 thông báo chung, không lộ SQLSTATE/host/stack trace.
