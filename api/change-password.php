<?php
// UC004 - Thay đổi mật khẩu (đã đăng nhập; cho phép cả khi đang dùng mật khẩu tạm)
require __DIR__ . '/../includes/bootstrap.php';
require_method('POST');
$u = require_login(true);

$old = raw('old_password');
$new = raw('new_password');
$cfm = raw('confirm_password');

$errors = [];
if ($old === '') $errors['old_password'] = 'Vui lòng nhập mật khẩu cũ';
if (!valid_password($new))  $errors['new_password'] = 'Mật khẩu mới từ 6 đến 72 ký tự';
elseif ($new === $old)      $errors['new_password'] = 'Mật khẩu mới phải khác mật khẩu cũ';
elseif ($new !== $cfm)      $errors['confirm_password'] = 'Mật khẩu xác nhận không trùng khớp';
if ($errors) fail('Vui lòng kiểm tra lại thông tin đã nhập', 422, $errors);

if (!password_verify($old, $u['password_hash'])) {
    fail('Mật khẩu cũ không đúng', 422, ['old_password' => 'Mật khẩu cũ không đúng']);   // 5a
}
db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?')
    ->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
session_regenerate_id(true);
json_out(['ok' => true, 'message' => 'Đổi mật khẩu thành công']);
