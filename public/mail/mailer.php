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
        $mail->Username   = 'info@eco-friendy.com';
        $mail->Password   = 'Abdullah@#$%27887';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        
        // ⭐ تفعيل Debug للتطوير (يمكنك تعطيله لاحقاً)
        // $mail->SMTPDebug = 2; // 0 = off, 1 = client, 2 = client and server
        
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
        
        // ⭐ تسجيل النجاح
        error_log("✅ Email sent successfully to: $to - Subject: $subject");
        return true;

    } catch (Exception $e) {
        // ⭐⭐⭐ تفعيل تسجيل الأخطاء (مهم جداً!)
        error_log("❌ Email sending failed to: $to");
        error_log("Error: " . $mail->ErrorInfo);
        error_log("Exception: " . $e->getMessage());
        return false;
    }
}
?>
