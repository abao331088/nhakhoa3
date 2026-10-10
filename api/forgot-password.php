<?php
// UC003 (bước 1) - Yêu cầu liên kết đặt lại mật khẩu
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/mailer.php';
require_method('POST');

$email = strtolower(field('email'));
if ($email === '' || !valid_email($email)) fail('Vui lòng nhập email hợp lệ', 422, ['email' => 'Vui lòng nhập email hợp lệ']);

$pdo = db();
$st = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ? AND status = 'active'");
$st->execute([$email]);
$u = $st->fetch();

$okMsg = 'Nếu email đã đăng ký, liên kết đặt lại mật khẩu đã được gửi. Liên kết có hiệu lực ' . RESET_TTL_MIN . ' phút.';
if (!$u) {
    if (RESET_REVEAL_EMAIL_EXISTS) fail('Email không tồn tại trong hệ thống', 404, ['email' => 'Email không tồn tại trong hệ thống']); // 5a (đúng SRS)
    json_out(['ok' => true, 'message' => $okMsg]);   // chế độ an toàn: không lộ email có tồn tại hay không
}

// Giới hạn 3 yêu cầu / giờ / tài khoản
$st = $pdo->prepare('SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at > (NOW() - INTERVAL 1 HOUR)');
$st->execute([$u['id']]);
if ((int)$st->fetchColumn() >= 3) fail('Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau.', 429);

$pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0')->execute([$u['id']]); // vô hiệu hóa link cũ
$token = bin2hex(random_bytes(32));
$pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ' . (int)RESET_TTL_MIN . ' MINUTE))')
    ->execute([$u['id'], hash('sha256', $token)]);        // chỉ lưu hash của token

$link = base_url() . '/reset-password.html?token=' . $token;
send_mail($email, 'Đặt lại mật khẩu - Nha Khoa Smile',
    "Xin chào {$u['full_name']},\nNhấn vào liên kết sau để đặt lại mật khẩu (hiệu lực " . RESET_TTL_MIN . " phút):\n$link\n");

$res = ['ok' => true, 'message' => $okMsg];
if (MAIL_MODE === 'simulate') $res['reset_link'] = $link;   // chỉ để demo khi chưa có SMTP
json_out($res);
