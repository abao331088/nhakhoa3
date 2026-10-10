<?php
// Trả về người dùng đang đăng nhập (nếu có) + CSRF token. Frontend gọi đầu tiên ở mọi trang.
require __DIR__ . '/../includes/bootstrap.php';
$u = current_user();
json_out([
    'ok' => true, 'logged_in' => (bool)$u,
    'user' => $u ? public_user($u) : null,
    'home' => $u ? ROLE_HOME[$u['role']] : null,
    'csrf' => $_SESSION['csrf'],
]);
