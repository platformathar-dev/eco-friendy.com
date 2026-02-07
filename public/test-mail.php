<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/mail/mailer.php';

if (sendMail(
    'rezak.abazid@gmail.com',
    'اختبار الإرسال',
    '<h2>نجح الإرسال 🎉</h2>'
)) {
    echo 'EMAIL SENT';
} else {
    echo 'FAILED';
}
