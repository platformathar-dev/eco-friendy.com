<?php
/**
 * PHPMailer Configuration for Hostinger
 * يدعم كلاً من Composer والتثبيت اليدوي
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// محاولة تحميل PHPMailer - يدعم طريقتين:
// 1. عبر Composer (الطريقة الموصى بها)
// 2. التثبيت اليدوي في مجلد PHPMailer

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    // ✅ تحميل عبر Composer
    require __DIR__ . '/../vendor/autoload.php';
    
} elseif (file_exists(__DIR__ . '/../public/PHPMailer/src/PHPMailer.php')) {
    // ✅ تحميل يدوي من المسار الكامل
    require __DIR__ . '/../public/PHPMailer/src/PHPMailer.php';
    require __DIR__ . '/../public/PHPMailer/src/SMTP.php';
    require __DIR__ . '/../public/PHPMailer/src/Exception.php';
    
} elseif (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
    // ✅ تحميل يدوي - مسار بديل
    require __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require __DIR__ . '/PHPMailer/src/SMTP.php';
    require __DIR__ . '/PHPMailer/src/Exception.php';
    
} elseif (file_exists(dirname(__DIR__) . '/PHPMailer/src/PHPMailer.php')) {
    // ✅ تحميل يدوي - مسار في الجذر
    require dirname(__DIR__) . '/PHPMailer/src/PHPMailer.php';
    require dirname(__DIR__) . '/PHPMailer/src/SMTP.php';
    require dirname(__DIR__) . '/PHPMailer/src/Exception.php';
    
} else {
    throw new Exception('
        ❌ PHPMailer library not found!
        
        الرجاء تثبيت PHPMailer بإحدى الطرق التالية:
        
        1️⃣ عبر Composer:
           composer require phpmailer/phpmailer
        
        2️⃣ تحميل يدوي:
           - حمّل PHPMailer من: https://github.com/PHPMailer/PHPMailer/archive/master.zip
           - استخرج المجلد واسمه "PHPMailer"
           - ضعه في: public_html/public/PHPMailer/
           
        البنية المطلوبة:
        public_html/
        ├── mail/
        │   └── mailer.php (هذا الملف)
        └── public/
            └── PHPMailer/
                └── src/
                    ├── PHPMailer.php
                    ├── SMTP.php
                    └── Exception.php
    ');
}

/**
 * إرسال بريد إلكتروني باستخدام إعدادات Hostinger
 * 
 * @param string $to عنوان البريد المستقبل
 * @param string $subject موضوع الرسالة
 * @param string $body محتوى الرسالة HTML
 * @param string $altBody محتوى نصي بديل (اختياري)
 * @param array $attachments مرفقات (اختياري)
 * @return bool نجاح أو فشل الإرسال
 */
function sendMail($to, $subject, $body, $altBody = '', $attachments = []) {
    $mail = new PHPMailer(true);
    
    try {
        // ========== إعدادات الخادم ==========
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'info@eco-friendy.com';
        $mail->Password = 'Abdullah@#$%27887';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL على منفذ 465
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';
        
        // تعطيل التحقق من الشهادة (للتطوير فقط)
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // ========== معلومات المرسل ==========
        $mail->setFrom('info@eco-friendy.com', 'Eco Friendy');
        $mail->addReplyTo('info@eco-friendy.com', 'Eco Friendy Support');
        
        // ========== معلومات المستقبل ==========
        $mail->addAddress($to);
        
        // ========== المحتوى ==========
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        if (!empty($altBody)) {
            $mail->AltBody = $altBody;
        } else {
            // إنشاء نص بديل تلقائياً من HTML
            $mail->AltBody = strip_tags($body);
        }
        
        // ========== المرفقات ==========
        if (!empty($attachments) && is_array($attachments)) {
            foreach ($attachments as $file) {
                if (is_array($file)) {
                    // مع اسم مخصص: ['path' => '...', 'name' => '...']
                    $mail->addAttachment($file['path'], $file['name'] ?? '');
                } else {
                    // مسار فقط
                    $mail->addAttachment($file);
                }
            }
        }
        
        // ========== إرسال البريد ==========
        $result = $mail->send();
        
        if ($result) {
            error_log("✅ Email sent successfully to: $to");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("❌ Email sending failed: {$mail->ErrorInfo}");
        error_log("Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * إرسال بريد إلكتروني بسيط (نصي)
 */
function sendSimpleMail($to, $subject, $message) {
    $htmlBody = "<!DOCTYPE html>
    <html dir='rtl'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .message { background: #f9f9f9; padding: 20px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='message'>" . nl2br(htmlspecialchars($message)) . "</div>
        </div>
    </body>
    </html>";
    
    return sendMail($to, $subject, $htmlBody, $message);
}

/**
 * اختبار الاتصال بخادم البريد
 */
function testMailConnection() {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'info@eco-friendy.com';
        $mail->Password = 'Abdullah@#$%27887';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
        
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // محاولة الاتصال فقط
        $mail->smtpConnect();
        $mail->smtpClose();
        
        return ['success' => true, 'message' => 'اتصال ناجح بخادم البريد'];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'فشل الاتصال',
            'error' => $e->getMessage()
        ];
    }
}
