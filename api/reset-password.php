<?php
// UC003 (bước 2) - GET ?token=... : kiểm tra link | POST : đặt mật khẩu mới
require __DIR__ . '/../includes/bootstrap.php';

function find_reset(string $token) {
    if (!preg_match('/^[0-9a-f]{64}$/', $token)) return false;
    $st = db()->prepare('SELECT id, user_id FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > NOW()');
    $st->execute([hash('sha256', $token)]);
    return $st->fetch();
}
const BAD_LINK = 'Liên kết không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!find_reset((string)($_GET['token'] ?? ''))) fail(BAD_LINK, 400);
    json_out(['ok' => true]);
}
require_method('POST');

$token = field('token');
$pw    = raw('password');
$pw2   = raw('confirm_password');
$errors = [];
if (!valid_password($pw))  $errors['password'] = 'Mật khẩu từ 6 đến 72 ký tự';
elseif ($pw !== $pw2)      $errors['confirm_password'] = 'Mật khẩu xác nhận không trùng khớp';
if ($errors) fail('Vui lòng kiểm tra lại thông tin đã nhập', 422, $errors);

$r = find_reset($token);
if (!$r) fail(BAD_LINK, 400);

$pdo = db();
$pdo->beginTransaction();
$pdo->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?')
    ->execute([password_hash($pw, PASSWORD_DEFAULT), $r['user_id']]);
$pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ?')->execute([$r['user_id']]);
$pdo->commit();
json_out(['ok' => true, 'message' => 'Đặt lại mật khẩu thành công. Hãy đăng nhập bằng mật khẩu mới.']);
