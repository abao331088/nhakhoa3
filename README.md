# DCAS - Nha Khoa Smile (Đợt 1: Đăng nhập / Đăng ký / Tài khoản)

## Cài đặt (XAMPP)
1. Chép thư mục `DCAS` vào `C:\xampp\htdocs\`.
2. Bật **Apache** và **MySQL** trong XAMPP Control Panel.
3. Mở `http://localhost/phpmyadmin` > tab **Import** > chọn `database/dcas.sql` > Go.
4. Mở `includes/config.php`, **đổi `SETUP_CODE`** (ít nhất 8 ký tự). Nếu MySQL có mật khẩu, sửa `DB_PASS`.
5. Mở `http://localhost/DCAS/setup.html` > nhập mã khởi tạo để tạo quản trị viên đầu tiên.
6. Đăng nhập tại `http://localhost/DCAS/login.html`.

Luôn mở qua `http://localhost/DCAS/...`, không double-click file .html và không dùng Live Server.

## Đã làm trong đợt 1
UC001 Đăng nhập, UC002 Đăng ký (Bệnh nhân), UC003 Quên / đặt lại mật khẩu (link 60 phút),
UC004 Đổi mật khẩu, UC005 Cập nhật hồ sơ + ảnh đại diện, trang setup admin đầu tiên, phân quyền 3 vai trò.
Quên mật khẩu đang ở chế độ mô phỏng: link hiện trên màn hình và ghi vào `logs/mail.log`.

## Quy ước khi viết API mới (đợt sau)
```php
require __DIR__ . '/../includes/bootstrap.php';
$u = require_role('admin');   // BẮT BUỘC ở đầu mọi API chức năng
```
