# Cài E-Shop trên hai Ubuntu Server 24.04

## 0. Quy hoạch và điều kiện
- Web Server: ví dụ 10.10.10.10; Database Server: ví dụ 10.10.10.20. Thay toàn bộ IP ví dụ theo mạng thực tế.
- Hai máy thông mạng nội bộ, đồng bộ giờ (timedatectl), DNS/tên miền trỏ về Web Server.
- Tài khoản sudo; tối thiểu 2 GB RAM/máy. PHP 8.3, MariaDB 10.11 thuộc Ubuntu 24.04 repositories.
- Lệnh import chỉ dành DB mới. Sao lưu trước khi cập nhật hệ thống có dữ liệu.
- Cho phép SSH trước khi bật UFW để tránh mất quyền truy cập.

## 1. Database Server — 10.10.10.20
~~~bash
sudo apt update
sudo apt install -y mariadb-server ufw
sudo systemctl enable --now mariadb
sudo mariadb-secure-installation
~~~

Sửa /etc/mysql/mariadb.conf.d/50-server.cnf, trong [mysqld]:
~~~ini
bind-address = 10.10.10.20
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
~~~

Không bind public hoặc 0.0.0.0 nếu không cần. Restart:
~~~bash
sudo systemctl restart mariadb
sudo mariadb
~~~

Tại MariaDB, tự thay REPLACE_WITH_A_RANDOM_SECRET bằng mật khẩu mạnh duy nhất (không dùng chuỗi ví dụ):
~~~sql
CREATE DATABASE eshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'eshop_app'@'10.10.10.10' IDENTIFIED BY 'REPLACE_WITH_A_RANDOM_SECRET';
GRANT SELECT, INSERT, UPDATE, DELETE ON eshop.* TO 'eshop_app'@'10.10.10.10';
SHOW GRANTS FOR 'eshop_app'@'10.10.10.10';
EXIT;
~~~

Ứng dụng không có quyền DDL, GRANT hoặc quyền trên database khác. Root chỉ dùng quản trị cục bộ khi import, không dùng trong website.

Firewall (xóa rule 3306 mở rộng nếu đã tồn tại; kiểm sudo ufw status numbered):
~~~bash
sudo ufw allow OpenSSH
sudo ufw allow from 10.10.10.10 to 10.10.10.20 port 3306 proto tcp
sudo ufw enable
sudo ufw status verbose
~~~

Chuyển thư mục database từ mã nguồn sang DB Server qua scp. Tại DB Server:
~~~bash
sudo mariadb eshop < database/schema.sql
sudo mariadb eshop < database/seed.sql
sudo mariadb eshop -e 'SELECT COUNT(*) AS products FROM products;'
~~~
Kết quả phải có 30 sản phẩm. Không import seed vào production có khách thật; thay/khóa toàn bộ tài khoản demo.

## 2. Web Server — 10.10.10.10
~~~bash
sudo apt update
sudo apt install -y nginx php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-gd php8.3-intl php8.3-curl php8.3-xml php8.3-zip mariadb-client unzip ufw
sudo systemctl enable --now nginx php8.3-fpm
sudo mkdir -p /var/www/eshop
~~~

Copy toàn bộ mã nguồn vào /var/www/eshop, **không copy .tools**. Không có bước npm/Composer/build frontend.
~~~bash
sudo cp /var/www/eshop/.env.example /var/www/eshop/.env
sudo nano /var/www/eshop/.env
~~~

Nội dung (thay secret bằng mật khẩu DB đã tạo):
~~~dotenv
APP_URL=http://eshop.example.com
APP_DEBUG=false
DB_HOST=10.10.10.20
DB_PORT=3306
DB_NAME=eshop
DB_USER=eshop_app
DB_PASS=REPLACE_WITH_THE_SECRET_CREATED_ON_DB_SERVER
~~~
Không thêm khoảng trắng trước dấu =; secret có khoảng trắng có thể bọc nháy. Không commit .env.

Phân quyền mã chỉ đọc cho PHP; chỉ logs/uploads được ghi:
~~~bash
sudo chown -R root:www-data /var/www/eshop
sudo find /var/www/eshop -type d -exec chmod 750 {} \;
sudo find /var/www/eshop -type f -exec chmod 640 {} \;
sudo chown -R www-data:www-data /var/www/eshop/storage/logs /var/www/eshop/public/uploads
sudo chmod 640 /var/www/eshop/.env
~~~

Cấu hình PHP theo deploy/php-fpm.md. Kiểm extensions:
~~~bash
php -m | grep -E 'PDO|pdo_mysql|mbstring|gd|intl|fileinfo'
~~~

## 3. Nginx
~~~bash
sudo cp /var/www/eshop/deploy/nginx-eshop.conf /etc/nginx/sites-available/eshop
sudo nano /etc/nginx/sites-available/eshop
sudo ln -s /etc/nginx/sites-available/eshop /etc/nginx/sites-enabled/eshop
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl restart php8.3-fpm
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
~~~

Thay server_name và socket theo thực tế. Chỉ bỏ default site nếu đã xác minh không phục vụ website khác. Khi truy cập bằng IP, dùng server_name phù hợp hoặc Host header.

## 4. Kiểm kết nối từ Web Server
~~~bash
mariadb --host=10.10.10.20 --user=eshop_app --password eshop -e 'SELECT 1;'
php /var/www/eshop/tests/core.php
curl -i -H 'Host: eshop.example.com' http://127.0.0.1/health
curl -I -H 'Host: eshop.example.com' http://127.0.0.1/.env
curl -I -H 'Host: eshop.example.com' http://127.0.0.1/uploads/test.php
~~~

Health phải 200 + status ok/db up/time; .env và uploads/test.php phải 403/404. Trên máy khác không phải Web Server, TCP 3306 phải bị chặn.
Nếu DB lỗi: kiểm route nội bộ, ss -lntp trên DB, UFW, SHOW GRANTS và .env; không mở DB cho mọi IP để chữa lỗi.

## 5. HTTPS
Cần DNS public đúng và cổng 80/443 được phép trước khi cấp chứng chỉ:
~~~bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d eshop.example.com
sudo certbot renew --dry-run
~~~
Với mạng lab không có DNS public, dùng CA nội bộ/chứng chỉ tin cậy của trường. Khối HTTPS mẫu nằm trong nginx-eshop.conf.
Sau khi HTTPS hoạt động, đổi APP_URL=https://eshop.example.com. Cookie Secure chỉ bật với APP_URL HTTPS. Chỉ bật HSTS khi TLS đã ổn định.

## 6. Nghiệm thu chức năng
1. Mở /, /products; tìm kiếm, lọc hãng/giá/danh mục, phân trang.
2. Đăng ký tài khoản mới; thêm hai sản phẩm cùng danh mục vào so sánh; thử khác loại phải bị từ chối.
3. Thêm giỏ, đổi số lượng; vượt tồn phải bị chặn. Checkout COD/chuyển khoản, lưu mã đơn.
4. Đăng nhập /admin/login bằng tài khoản demo từng vai trò theo README. Thử trực tiếp URL chức năng không được phép: phải 403.
5. Sales xác nhận, warehouse xử lý/giao, sales hoàn thành; admin hủy đơn khác: tồn phục hồi đúng một lần.
6. Accountant ghi nhận tiền, hóa đơn và báo cáo. Thu vượt tổng/hoàn vượt tiền đã thu phải bị từ chối.
7. Warehouse nhập/xuất thủ công, kiểm dashboard cảnh báo. Admin xem audit/giám sát.
8. Kiểm /health khi tạm dừng DB trong lab, phải 503; khôi phục DB, phải 200. Không làm bài test này trên production đang hoạt động.

## 7. Kiểm thử tự động — DB test riêng
Không chạy import/test tranh chấp trên DB thật. Với Docker Engine ở máy phát triển, xem README để tạo DB test tmpfs.
Lệnh PHP lint:
~~~bash
find /var/www/eshop/app /var/www/eshop/config /var/www/eshop/public /var/www/eshop/tests -name '*.php' -exec php -l {} \;
~~~

## 8. Vận hành / sao lưu
- Sao lưu DB bằng mariadb-dump từ máy DB; sao lưu uploads và .env vào nơi riêng có bảo vệ. Kiểm thử restore định kỳ vào DB khác.
- Không expose storage hoặc thư mục gốc qua Nginx. APP_DEBUG=false.
- Thiết lập logrotate cho storage/logs/*.log; chỉ admin website xem log đã giới hạn.
- Cập nhật security packages. Khóa/thay toàn bộ mật khẩu demo trước khi công khai.
- Bootstrap/Chart.js/font tải CDN cần Internet ở trình duyệt; ảnh demo lưu cục bộ.
- DB trên mạng lab được firewall giới hạn nguồn. Với mạng không tin cậy, triển khai VPN hoặc TLS MariaDB và cấu hình PDO chứng chỉ trước khi vận hành.
