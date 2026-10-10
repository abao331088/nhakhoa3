<?php
// Gửi mail. Chế độ 'simulate': ghi vào logs/mail.log (không cần SMTP).
// Khi muốn gửi thật: cài PHPMailer (composer require phpmailer/phpmailer) và viết nhánh else bên dưới.
function send_mail(string $to, string $subject, string $text): bool {
    if (MAIL_MODE === 'simulate') {
        $line = '[' . date('Y-m-d H:i:s') . "] TO: $to | $subject\n$text\n" . str_repeat('-', 60) . "\n";
        @file_put_contents(__DIR__ . '/../logs/mail.log', $line, FILE_APPEND | LOCK_EX);
        return true;
    }
    return false; // TODO: PHPMailer + SMTP
}
