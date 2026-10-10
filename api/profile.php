<?php
// UC005 - Xem / cập nhật thông tin cá nhân (mọi vai trò)
require __DIR__ . '/../includes/bootstrap.php';
$u = require_login(true);

if ($_SERVER['REQUEST_METHOD'] === 'GET') json_out(['ok' => true, 'user' => public_user($u)]);
require_method('POST');

$set = []; $val = []; $errors = [];
$body = body();

if (array_key_exists('full_name', $body)) {
    $v = field('full_name');
    if (!valid_name($v)) $errors['full_name'] = 'Họ tên không được để trống và không quá 255 ký tự';
    else { $set[] = 'full_name = ?'; $val[] = $v; }
}
if (array_key_exists('phone', $body)) {
    $v = field('phone');
    if ($v !== '' && !valid_phone($v)) $errors['phone'] = 'Số điện thoại gồm đúng 10 chữ số';
    else { $set[] = 'phone = ?'; $val[] = $v === '' ? null : $v; }
}
if (array_key_exists('birthday', $body)) {
    $v = field('birthday');
    if ($v !== '' && !valid_date($v)) $errors['birthday'] = 'Ngày sinh không hợp lệ';
    else { $set[] = 'birthday = ?'; $val[] = $v === '' ? null : $v; }
}
if (array_key_exists('gender', $body)) {
    $v = field('gender');
    if ($v !== '' && !in_array($v, ['male', 'female', 'other'], true)) $errors['gender'] = 'Giới tính không hợp lệ';
    else { $set[] = 'gender = ?'; $val[] = $v === '' ? null : $v; }
}

// Ảnh đại diện: png / jpg / jpeg, tối đa 2MB. Kiểm tra bằng nội dung file chứ không tin đuôi file.
$newAvatar = null;
if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['avatar'];
    $mime = ($f['error'] === UPLOAD_ERR_OK && is_uploaded_file($f['tmp_name'])) ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : '';
    $ext  = ['image/png' => 'png', 'image/jpeg' => 'jpg'][$mime] ?? null;
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > 2 * 1024 * 1024) $errors['avatar'] = 'Ảnh tối đa 2MB';
    elseif (!$ext || !@getimagesize($f['tmp_name']))                   $errors['avatar'] = 'Ảnh phải có định dạng png, jpg hoặc jpeg';
    else {
        $dir = __DIR__ . '/../uploads/avatars';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $newAvatar = $u['id'] . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], "$dir/$newAvatar")) fail('Không lưu được ảnh, vui lòng thử lại', 500);
        $set[] = 'avatar = ?'; $val[] = $newAvatar;
    }
}

if ($errors) {
    if ($newAvatar) @unlink(__DIR__ . '/../uploads/avatars/' . $newAvatar);
    fail('Thông tin không đúng định dạng', 422, $errors);                                   // 5a
}
if ($set) {
    $val[] = $u['id'];
    db()->prepare('UPDATE users SET ' . implode(', ', $set) . ' WHERE id = ?')->execute($val);
    if ($newAvatar && $u['avatar']) @unlink(__DIR__ . '/../uploads/avatars/' . basename($u['avatar']));
}
$st = db()->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$u['id']]);
json_out(['ok' => true, 'message' => 'Cập nhật thông tin thành công', 'user' => public_user($st->fetch())]);
