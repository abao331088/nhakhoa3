<?php
// UC002 - Đăng ký (chỉ tạo tài khoản BỆNH NHÂN; nha sĩ / quản trị viên do QTV tạo)
require __DIR__ . '/../includes/bootstrap.php';
require_method('POST');

$name  = field('full_name');
$email = strtolower(field('email'));
$phone = field('phone');
$pw    = raw('password');
$pw2   = raw('confirm_password');

$errors = [];
if ($name === '' || $email === '' || $phone === '' || $pw === '' || $pw2 === '') {
    foreach (['full_name' => $name, 'email' => $email, 'phone' => $phone, 'password' => $pw, 'confirm_password' => $pw2] as $k => $v) {
        if ($v === '') $errors[$k] = 'Trường này là bắt buộc';
    }
    fail('Cần nhập đầy đủ các trường bắt buộc', 422, $errors);                              // 5a
}
if (!valid_name($name))      $errors['full_name'] = 'Họ tên không quá 255 ký tự';
if (!valid_email($email))    $errors['email'] = 'Địa chỉ email không hợp lệ';                // 6a
if (!valid_phone($phone))    $errors['phone'] = 'Số điện thoại gồm đúng 10 chữ số';
if (!valid_password($pw))    $errors['password'] = 'Mật khẩu từ 6 đến 72 ký tự';
elseif ($pw !== $pw2)        $errors['confirm_password'] = 'Mật khẩu xác nhận không trùng khớp'; // 7a
if ($errors) fail('Vui lòng kiểm tra lại thông tin đã nhập', 422, $errors);

$pdo = db();
$st = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
$st->execute([$email]);
if ($st->fetchColumn()) fail('Địa chỉ email đã tồn tại', 409, ['email' => 'Địa chỉ email đã tồn tại']);   // 6a

try {
    $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, 'patient', 'active')")
        ->execute([$name, $email, $phone, password_hash($pw, PASSWORD_DEFAULT)]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') fail('Địa chỉ email đã tồn tại', 409, ['email' => 'Địa chỉ email đã tồn tại']); // 2 người đăng ký cùng lúc
    throw $e;
}
json_out(['ok' => true, 'message' => 'Đăng ký thành công. Bạn có thể đăng nhập ngay.'], 201);
