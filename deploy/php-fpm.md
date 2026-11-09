# PHP-FPM 8.3

Ubuntu 24.04 dùng /etc/php/8.3/fpm/php.ini:

~~~ini
display_errors = Off
log_errors = On
expose_php = Off
upload_max_filesize = 5M
post_max_size = 42M
max_file_uploads = 8
memory_limit = 256M
max_execution_time = 60
session.use_strict_mode = 1
session.use_only_cookies = 1
session.cookie_httponly = 1
session.cookie_samesite = Lax
date.timezone = Asia/Ho_Chi_Minh
~~~

Pool /etc/php/8.3/fpm/pool.d/www.conf:

~~~ini
user = www-data
group = www-data
listen = /run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 2
pm.max_spare_servers = 4
~~~

Điều chỉnh pm.max_children theo RAM và bộ nhớ trung bình mỗi worker. Không cấp quyền ghi toàn mã nguồn cho www-data.
Ứng dụng đọc .env trực tiếp; không cần bật clear_env=no.
Restart: sudo systemctl restart php8.3-fpm.
