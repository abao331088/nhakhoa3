<?php
// Tạo Quản trị viên ĐẦU TIÊN bằng mã khởi tạo (SETUP_CODE trong includes/config.php).
//  - Chỉ hoạt động khi hệ thống CHƯA có quản trị viên nào. Có rồi thì tự khóa vĩnh viễn.
//  - Sau đó chỉ QTV đã đăng nhập mới tạo được nha sĩ / QTV khác (làm ở đợt sau).
require __DIR__ . '/../includes/bootstrap.php';

function admin_exists(): bool {
    return (bool)db()->query("SELECT 1 FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') json_out(['ok' => true, 'needs_setup' => !admin_exists()]);
require_method('POST');

if (admin_exists()) fail('Hệ thống đã được thiết lập. Trang này không còn khả dụng.', 403, [], 'ALREADY_SETUP');
if (SETUP_CODE === 'DCAS-CHANGE-ME' || strlen(SETUP_CODE) < 8) {
    fail('Chưa đổi SETUP_CODE trong includes/config.php (cần ít nhất 8 ký tự, khác giá trị mặc định).', 500);
}
if (($_SESSION['setup_fail'] ?? 0) >= 5) fail('Nhập sai mã quá nhiều lần. Đóng trình duyệt và thử lại sau.', 429);

$code  = raw('setup_code');
$name  = field('full_name');
$email = strtolower(field('email'));
$phone = field('phone');
$pw    = raw('password');
$pw2   = raw('confirm_password');

$errors = [];
if ($code === '')            $errors['setup_code'] = 'Vui lòng nhập mã khởi tạo';
if (!valid_name($name))      $errors['full_name'] = 'Họ tên không được để trống và không quá 255 ký tự';
if (!valid_email($email))    $errors['email'] = 'Địa chỉ email không hợp lệ';
if ($phone !== '' && !valid_phone($phone)) $errors['phone'] = 'Số điện thoại gồm đúng 10 chữ số';
if (!valid_password($pw))    $errors['password'] = 'Mật khẩu từ 6 đến 72 ký tự';
elseif ($pw !== $pw2)        $errors['confirm_password'] = 'Mật khẩu xác nhận không trùng khớp';
if ($errors) fail('Vui lòng kiểm tra lại thông tin đã nhập', 422, $errors);

if (!hash_equals(SETUP_CODE, $code)) {
    $_SESSION['setup_fail'] = ($_SESSION['setup_fail'] ?? 0) + 1;
    fail('Mã khởi tạo không đúng', 403, ['setup_code' => 'Mã khởi tạo không đúng']);
}

$pdo = db();
try {
    $pdo->beginTransaction();
    if ($pdo->query("SELECT 1 FROM users WHERE role = 'admin' LIMIT 1 FOR UPDATE")->fetchColumn()) {
        $pdo->rollBack();
        fail('Hệ thống đã được thiết lập.', 403, [], 'ALREADY_SETUP');
    }
    $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, 'admin', 'active')")
        ->execute([$name, $email, $phone === '' ? null : $phone, password_hash($pw, PASSWORD_DEFAULT)]);
    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($e->getCode() === '23000') fail('Email này đã được dùng cho một tài khoản khác', 409, ['email' => 'Email đã tồn tại']);
    throw $e;
}
unset($_SESSION['setup_fail']);
json_out(['ok' => true, 'message' => 'Đã tạo quản trị viên đầu tiên. Hãy đăng nhập.'], 201);
