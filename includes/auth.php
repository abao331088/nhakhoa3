<?php
const ROLE_HOME = [
    'patient' => 'patient/dashboard.html',
    'dentist' => 'dentist/dashboard.html',
    'admin'   => 'admin/dashboard.html',
];

function start_session() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('DCASSESS');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function verify_csrf() {
    $t = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        fail('Phiên làm việc không hợp lệ, vui lòng tải lại trang', 419, [], 'CSRF');
    }
}

function public_user(array $u): array {
    return [
        'id' => (int)$u['id'], 'full_name' => $u['full_name'], 'email' => $u['email'],
        'phone' => $u['phone'], 'role' => $u['role'], 'status' => $u['status'],
        'must_change_password' => (bool)$u['must_change_password'],
        'birthday' => $u['birthday'], 'gender' => $u['gender'],
        'avatar' => $u['avatar'] ? 'uploads/avatars/' . $u['avatar'] : null,
    ];
}

// Luôn đọc lại từ DB: khi QTV khóa tài khoản hoặc đổi vai trò thì có hiệu lực ngay.
function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    $st = db()->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$_SESSION['uid']]);
    $u = $st->fetch();
    if (!$u || $u['status'] !== 'active') { unset($_SESSION['uid']); return null; }
    return $u;
}

function require_login(bool $allowMustChange = false): array {
    $u = current_user();
    if (!$u) fail('Bạn cần đăng nhập để tiếp tục', 401, [], 'UNAUTHENTICATED');
    if ($u['must_change_password'] && !$allowMustChange) {
        fail('Bạn cần đổi mật khẩu tạm trước khi sử dụng hệ thống', 403, [], 'MUST_CHANGE_PASSWORD');
    }
    return $u;
}

// Mọi API chức năng PHẢI gọi hàm này: đây mới là nơi phân quyền thật sự.
function require_role($roles, bool $allowMustChange = false): array {
    $u = require_login($allowMustChange);
    if (!in_array($u['role'], (array)$roles, true)) fail('Bạn không có quyền thực hiện chức năng này', 403, [], 'FORBIDDEN');
    return $u;
}
