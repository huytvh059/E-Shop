# E-Shop — Đường dẫn và tài khoản demo

## Website đang chạy

- **Cửa hàng:** http://localhost:8080
- **Đăng nhập khách hàng:** http://localhost:8080/login
- **Đăng nhập nhân viên / quản trị:** http://localhost:8080/admin/login
- **Trang quản trị:** http://localhost:8080/admin
- **Kiểm tra hệ thống:** http://localhost:8080/health

> Các đường dẫn localhost chỉ truy cập trên máy đang chạy website. Cần giữ PHP và MariaDB hoạt động.

## Tài khoản demo

| Vai trò | Email đăng nhập | Mật khẩu |
|---|---|---|
| Quản trị viên | admin@eshop.test | password |
| Nhân viên bán hàng | sales@eshop.test | password |
| Nhân viên kho | warehouse@eshop.test | password |
| Nhân viên kế toán | accountant@eshop.test | password |
| Nhân viên hỗ trợ | support@eshop.test | password |
| Khách hàng 1 | customer1@eshop.test | password |
| Khách hàng 2 | customer2@eshop.test | password |
| Khách hàng 3 | customer3@eshop.test | password |

Khách hàng đăng nhập tại `/login`. Nhân viên đăng nhập tại `/admin/login`.

## Mở lại website

- Thư mục dự án: `D:\TTTN\eshop`.
- Chạy script `start-local.ps1` (chỉ có trên máy phát triển, không nằm trong repo) bằng PowerShell để mở lại môi trường portable đã chuẩn bị trên máy này.
- Hướng dẫn đầy đủ: [README.md](README.md).
- Triển khai hai máy Ubuntu: [deploy/install.md](deploy/install.md).

> **Bảo mật:** Tài khoản và mật khẩu trên chỉ dùng demo/lab. Thay mật khẩu hoặc khóa tài khoản demo trước khi công khai website. File này không chứa mật khẩu database.
