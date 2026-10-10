<?php
// UC001 - Đăng nhập
require __DIR__ . '/../includes/bootstrap.php';
require_method('POST');

$email = strtolower(field('email'));
$pw    = raw('password');

$errors = [];
if ($email === '') $errors['email'] = 'Vui lòng nhập email';
if ($pw === '')    $errors['password'] = 'Vui lòng nhập mật khẩu';
if ($errors) fail('Cần nhập đầy đủ các trường bắt buộc', 422, $errors);                    // 5a
if (!valid_email($email)) fail('Email không đúng định dạng', 422, ['email' => 'Email không đúng định dạng']);

$pdo = db();
$pdo->exec("DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 1 DAY)");

// Chống dò mật khẩu: tối đa 5 lần sai / email / 15 phút (và 30 lần / IP)
$st = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE email = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)");
$st->execute([$email]);
$byEmail = (int)$st->fetchColumn();
$st = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > (NOW() - INTERVAL 15 MINUTE)");
$st->execute([client_ip()]);
if ($byEmail >= 5 || (int)$st->fetchColumn() >= 30) {
    fail('Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau 15 phút.', 429);
}

$st = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$st->execute([$email]);
$u = $st->fetch();

// Luôn chạy password_verify (kể cả khi email không tồn tại) để thời gian phản hồi không lộ email nào có tài khoản
$hash = $u ? $u['password_hash'] : password_hash('dcas-dummy', PASSWORD_DEFAULT);
if (!password_verify($pw, $hash) || !$u) {
    $pdo->prepare('INSERT INTO login_attempts (email, ip) VALUES (?, ?)')->execute([$email, client_ip()]);
    fail('Email và/hoặc mật khẩu không đúng', 401);                                         // 6a
}

// Mật khẩu đúng -> mới báo trạng thái tài khoản
if ($u['status'] === 'pending') fail('Tài khoản đang chờ quản trị viên xác nhận. Vui lòng quay lại sau.', 403, [], 'PENDING');
if ($u['status'] === 'locked')  fail('Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.', 403, [], 'LOCKED');

if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($pw, PASSWORD_DEFAULT), $u['id']]);
}
$pdo->prepare('DELETE FROM login_attempts WHERE email = ?')->execute([$email]);

session_regenerate_id(true);                         // chống session fixation
$_SESSION['uid']  = (int)$u['id'];
$_SESSION['csrf'] = bin2hex(random_bytes(32));       // đổi CSRF token sau khi đăng nhập

$redirect = $u['must_change_password'] ? $u['role'] . '/profile.html?force=1' : ROLE_HOME[$u['role']];
json_out(['ok' => true, 'message' => 'Đăng nhập thành công', 'user' => public_user($u), 'redirect' => $redirect, 'csrf' => $_SESSION['csrf']]);
