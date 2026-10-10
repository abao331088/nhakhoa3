<?php
// ---------- Trả JSON ----------
function json_out(array $data, int $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function fail(string $message, int $status = 400, array $errors = [], string $code = '') {
    json_out(['ok' => false, 'message' => $message, 'errors' => (object)$errors, 'code' => $code], $status);
}
function require_method(string $m) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $m) fail('Phương thức không được hỗ trợ', 405);
}

// ---------- Đọc dữ liệu gửi lên (JSON hoặc form/multipart) ----------
function body(): array {
    static $b = null;
    if ($b !== null) return $b;
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $d = json_decode(file_get_contents('php://input'), true);
        $b = is_array($d) ? $d : [];
    } else {
        $b = $_POST;
    }
    return $b;
}
function raw(string $k): string  { $v = body()[$k] ?? ''; return is_string($v) ? $v : ''; }   // giữ nguyên (mật khẩu)
function field(string $k): string { return trim(raw($k)); }                                   // cắt khoảng trắng

// ---------- Kiểm tra dữ liệu (theo SRS) ----------
function valid_email(string $e): bool    { return strlen($e) <= 190 && filter_var($e, FILTER_VALIDATE_EMAIL) !== false; }
function valid_phone(string $p): bool    { return (bool)preg_match('/^\d{10}$/', $p); }          // 10 chữ số
function valid_password(string $p): bool { return strlen($p) >= 6 && strlen($p) <= 72; }          // >= 6 ký tự (SRS); 72 = giới hạn bcrypt
function valid_name(string $n): bool     { return $n !== '' && mb_strlen($n) <= 255; }
function valid_date(string $d): bool {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return false;
    return $d <= date('Y-m-d') && (int)$m[1] >= 1900;
}

function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? ''; }

function base_url(): string {
    if (APP_URL !== '') return rtrim(APP_URL, '/');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $root = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $root;
}
