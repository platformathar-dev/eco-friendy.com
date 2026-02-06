<?php
/**
 * مكتبة إرسال البريد الإلكتروني - Hostinger
 * 
 * يستخدم PHPMailer مع إعدادات SMTP الخاصة بـ Hostinger
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php'; // مكتبة PHPMailer عبر Composer
require_once 'email_config_hostinger.php';

/**
 * إرسال رمز التحقق عبر البريد الإلكتروني
 * 
 * @param string $email البريد الإلكتروني
 * @param string $name اسم المستخدم
 * @param string $code رمز التحقق
 * @return bool نجاح أو فشل الإرسال
 */
function sendVerificationEmail($email, $name, $code) {
    $mail = new PHPMailer(true);
    
    try {
        // إعدادات SMTP - Hostinger
        $mail->isSMTP();
        $mail->Host = SMTP_HOST; // smtp.hostinger.com
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME; // info@eco-friendy.com
        $mail->Password = SMTP_PASSWORD; // كلمة مرور البريد
        $mail->SMTPSecure = SMTP_SECURE; // SSL
        $mail->Port = SMTP_PORT; // 465
        $mail->CharSet = 'UTF-8';
        
        // تعطيل التحقق من الشهادة (في حالة مشاكل SSL)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // المرسل
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // المستقبل
        $mail->addAddress($email, $name);
        
        // المحتوى
        $mail->isHTML(true);
        $mail->Subject = 'رمز التحقق من البريد الإلكتروني - Eco Friendly Store';
        
        // قالب البريد الإلكتروني
        $mail->Body = getVerificationEmailTemplate($name, $code);
        $mail->AltBody = "مرحباً $name،\n\nرمز التحقق الخاص بك هو: $code\n\nهذا الرمز صالح لمدة 15 دقيقة.\n\nإذا لم تقم بإنشاء حساب، يرجى تجاهل هذه الرسالة.\n\nشكراً،\nفريق Eco Friendly Store";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // تسجيل الخطأ
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * قالب البريد الإلكتروني لرمز التحقق
 */
function getVerificationEmailTemplate($name, $code) {
    return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #f39200 0%, #e68500 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 40px 30px;
            text-align: center;
        }
        .greeting {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .code-container {
            background: linear-gradient(135deg, #fff3e6 0%, #ffe0b3 100%);
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
        }
        .code-label {
            font-size: 14px;
            color: #c77400;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .code {
            font-size: 42px;
            font-weight: bold;
            color: #f39200;
            letter-spacing: 8px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }
        .expiry {
            font-size: 13px;
            color: #999;
            margin-top: 15px;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
            font-size: 14px;
        }
        .footer {
            background: #f9f9f9;
            padding: 30px;
            text-align: center;
            color: #999;
            font-size: 13px;
            border-top: 1px solid #eee;
        }
        .footer a {
            color: #f39200;
            text-decoration: none;
        }
        .logo {
            font-size: 48px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🌿</div>
            <h1>Eco Friendly Store</h1>
        </div>
        
        <div class="content">
            <div class="greeting">مرحباً $name! 👋</div>
            
            <div class="message">
                شكراً لتسجيلك في متجر Eco Friendly! نحن سعداء بانضمامك إلينا.<br>
                للتحقق من بريدك الإلكتروني وتفعيل حسابك، يرجى استخدام الرمز التالي:
            </div>
            
            <div class="code-container">
                <div class="code-label">رمز التحقق</div>
                <div class="code">$code</div>
                <div class="expiry">⏱️ صالح لمدة <strong>15 دقيقة</strong></div>
            </div>
            
            <div class="warning">
                ⚠️ <strong>تنبيه أمني:</strong> إذا لم تقم بإنشاء حساب في موقعنا، يرجى تجاهل هذه الرسالة وعدم مشاركة هذا الرمز مع أي شخص.
            </div>
        </div>
        
        <div class="footer">
            هذه رسالة تلقائية من <strong>Eco Friendly Store</strong><br>
            للمساعدة، تواصل معنا على: <a href="mailto:info@eco-friendy.com">info@eco-friendy.com</a><br>
            <br>
            © 2024 Eco Friendly Store. جميع الحقوق محفوظة.
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * إرسال رسالة ترحيب بعد التحقق
 */
function sendWelcomeEmail($email, $name) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $name);
        
        $mail->isHTML(true);
        $mail->Subject = 'مرحباً بك في Eco Friendly Store! 🎉';
        $mail->Body = getWelcomeEmailTemplate($name);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * قالب رسالة الترحيب
 */
function getWelcomeEmailTemplate($name) {
    return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%); color: white; padding: 40px 20px; text-align: center; }
        .content { padding: 40px 30px; text-align: center; }
        .success-icon { font-size: 64px; margin-bottom: 20px; }
        h1 { color: #388e3c; margin: 0 0 20px 0; }
        p { color: #666; line-height: 1.6; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #f39200 0%, #e68500 100%); color: white; text-decoration: none; border-radius: 10px; margin: 20px 0; font-weight: bold; }
        .footer { background: #f9f9f9; padding: 30px; text-align: center; color: #999; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌿 Eco Friendly Store</h1>
        </div>
        <div class="content">
            <div class="success-icon">✅</div>
            <h1>تم تفعيل حسابك بنجاح!</h1>
            <p>مرحباً <strong>$name</strong>،</p>
            <p>نحن سعداء جداً بانضمامك إلى عائلة Eco Friendly! حسابك الآن مفعل بالكامل ويمكنك البدء بالتسوق.</p>
            <p>استمتع بتصفح منتجاتنا الصديقة للبيئة وابدأ رحلتك نحو حياة أكثر استدامة.</p>
            <a href="https://eco-friendy.com/user-dashboard.html" class="btn">ابدأ التسوق الآن 🛍️</a>
        </div>
        <div class="footer">
            © 2024 Eco Friendly Store. جميع الحقوق محفوظة.
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * توليد رمز تحقق عشوائي (6 أرقام)
 */
function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * اختبار إرسال البريد
 */
function testEmail($toEmail = 'test@example.com') {
    $testCode = generateVerificationCode();
    $result = sendVerificationEmail($toEmail, 'مستخدم تجريبي', $testCode);
    
    if ($result) {
        return "✅ تم إرسال البريد بنجاح! الرمز: $testCode";
    } else {
        return "❌ فشل إرسال البريد. تحقق من الإعدادات.";
    }
}
?>
