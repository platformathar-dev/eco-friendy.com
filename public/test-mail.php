<?php
require __DIR__ . '/mail/mailer.php';

if (sendMail(
    'your@email.com',
    'اختبار الإرسال',
    '<h2>نجح الإرسال 🎉</h2><p>PHPMailer شغال تمام</p>'
)) {
    echo 'EMAIL SENT SUCCESSFULLY';
} else {
    echo 'FAILED TO SEND EMAIL';
}
