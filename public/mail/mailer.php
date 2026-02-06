<?php
// استخدام PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// تضمين ملفات PHPMailer من نفس المجلد
require __DIR__ . '/PHPMailer.php';
require __DIR__ . '/SMTP.php';
require __DIR__ . '/Exception.php';

/**
 * دالة إرسال البريد الإلكتروني
 * @param string $to      البريد المستلم
 * @param string $subject عنوان الرسالة
 * @param string $message محتوى الرسالة بصيغة HTML
 * @return bool           true إذا تم الإرسال، false إذا فشل
 */
function sendMail($to, $subject, $message)
{
    $mail = new PHPMailer(true);

    try {
        // إعدادات SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@eco-friendy.com';  // بريدك
        $mail->Password   = 'Abdullah@#$%27887';     // كلمة المرور
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        // المرسل
        $mail->setFrom('info@eco-friendy.com', 'Eco Friendy');

        // المستلم
        $mail->addAddress($to);

        // محتوى البريد
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        // إرسال البريد
        $mail->send();
        return true;

    } catch (Exception $e) {
        // يمكنك تفعيل السطر التالي أثناء التطوير لمعرفة الخطأ
        // error_log($mail->ErrorInfo);
        return false;
    }
}
