<?php
// Mọi file trong api/ bắt đầu bằng: require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/config.php';
require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

set_exception_handler(function (Throwable $e) {
    error_log((string)$e);
    http_response_code(500);
    echo json_encode([
        'ok' => false, 'errors' => new stdClass,
        'message' => DEBUG ? 'Lỗi máy chủ: ' . $e->getMessage() : 'Lỗi máy chủ, vui lòng thử lại sau',
    ], JSON_UNESCAPED_UNICODE);
});

start_session();
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD', 'OPTIONS'], true)) verify_csrf();
