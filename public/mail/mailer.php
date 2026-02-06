<?php
// استدعاء مكتبات PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// مسارات ملفات PHPMailer
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';


/**
 * دالة إرسال إيميل
 * @param string $to      البريد المستلم
 * @param string $subject عنوان الإيميل
 * @param string $message محتوى الإيميل (HTML)
 * @return bool
 */
function sendMail($to, $subject, $message)
{
    $mail = new PHPMailer(true);

    try {
        // إعداد SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@eco-friendy.com';      // إيميلك
        $mail->Password   = 'Abdullah@#$%27887';   // 🔴 كلمة السر (غيّرها)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        // المرسل
        $mail->setFrom('info@eco-friendy.com', 'Eco Friendy');

        // المستلم
        $mail->addAddress($to);

        // محتوى الإيميل
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        // إرسال
        $mail->send();
        return true;

    } catch (Exception $e) {
        // في حال الخطأ
        // error_log($mail->ErrorInfo); // يمكنك تفعيله للتشخيص
        return false;
    }
}
