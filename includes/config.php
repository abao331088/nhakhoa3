<?php
// Cấu hình DCAS. File này KHÔNG được đưa lên GitHub (đã có trong .gitignore).
const DB_HOST = '127.0.0.1';
const DB_NAME = 'dcas';
const DB_USER = 'root';
const DB_PASS = '';                 // XAMPP mặc định để trống

// Mã khởi tạo để tạo Quản trị viên ĐẦU TIÊN (trang setup.html).
// BẮT BUỘC đổi trước khi dùng - nếu để nguyên giá trị mặc định, setup sẽ bị từ chối.
const SETUP_CODE = 'DCAS-CHANGE-ME';

const MAIL_MODE = 'simulate';       // 'simulate': ghi ra logs/mail.log và hiện link trên màn hình
const APP_URL   = '';               // vd 'http://localhost/DCAS'. Để trống = tự đoán (chỉ dùng khi phát triển)
const RESET_TTL_MIN = 60;           // UC003: liên kết đặt lại mật khẩu có hiệu lực 60 phút
const RESET_REVEAL_EMAIL_EXISTS = true; // true = đúng SRS (báo lỗi nếu email không tồn tại); false = an toàn hơn
const DEBUG = true;                 // đặt false khi chạy thật

date_default_timezone_set('Asia/Ho_Chi_Minh');
