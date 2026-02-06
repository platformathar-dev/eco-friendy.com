<?php
/**
 * اختبار إرسال البريد الإلكتروني - Hostinger
 * 
 * افتح هذا الملف في المتصفح: http://yoursite.com/api/test-email.php
 * أو: http://localhost/api/test-email.php
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';

echo "<!DOCTYPE html>";
echo "<html dir='rtl'><head><meta charset='UTF-8'>";
echo "<style>
body { font-family: Arial; padding: 20px; background: #f5f5f5; }
.box { background: white; padding: 20px; margin: 10px 0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
h1 { color: #f39200; }
h2 { color: #c77400; border-bottom: 2px solid #ffe0b3; padding-bottom: 10px; }
.code { background: #fff3e6; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 24px; text-align: center; letter-spacing: 5px; color: #f39200; margin: 15px 0; }
.info { background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3; margin: 15px 0; }
pre { background: #f9f9f9; padding: 10px; border-radius: 5px; overflow-x: auto; }
.btn { display: inline-block; padding: 10px 20px; background: #f39200; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
</style></head><body>";

echo "<h1>🧪 اختبار إرسال البريد الإلكتروني - Hostinger</h1>";

// ============================================
// 1. التحقق من PHPMailer
// ============================================
echo "<div class='box'>";
echo "<h2>1. التحقق من PHPMailer</h2>";

if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "<p class='success'>✅ PHPMailer مثبت بنجاح</p>";
} else {
    echo "<p class='error'>❌ PHPMailer غير مثبت</p>";
    echo "<div class='info'>";
    echo "<strong>الحل:</strong><br>";
    echo "1. افتح Terminal/CMD في مجلد المشروع<br>";
    echo "2. نفذ الأمر: <code>composer require phpmailer/phpmailer</code><br>";
    echo "3. أعد تحميل هذه الصفحة";
    echo "</div>";
    echo "</div></body></html>";
    exit();
}
echo "</div>";

// ============================================
// 2. إعدادات Hostinger
// ============================================
echo "<div class='box'>";
echo "<h2>2. إعدادات SMTP - Hostinger</h2>";

$config = [
    'host' => 'smtp.hostinger.com',
    'port' => 465,
    'secure' => 'ssl',
    'username' => 'info@eco-friendy.com',
    'password' => 'YOUR_EMAIL_PASSWORD', // ⚠️ ضع كلمة المرور هنا
];

echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
echo "<tr><th style='padding:10px; background:#f39200; color:white;'>الإعداد</th><th style='padding:10px; background:#f39200; color:white;'>القيمة</th></tr>";
echo "<tr><td style='padding:10px;'>SMTP Host</td><td style='padding:10px;'>{$config['host']}</td></tr>";
echo "<tr><td style='padding:10px;'>SMTP Port</td><td style='padding:10px;'>{$config['port']}</td></tr>";
echo "<tr><td style='padding:10px;'>Encryption</td><td style='padding:10px;'>{$config['secure']}</td></tr>";
echo "<tr><td style='padding:10px;'>Username</td><td style='padding:10px;'>{$config['username']}</td></tr>";
echo "<tr><td style='padding:10px;'>Password</td><td style='padding:10px;'>" . (strlen($config['password']) > 15 ? '✅ تم التعيين' : '❌ لم يتم التعيين') . "</td></tr>";
echo "</table>";

if ($config['password'] === 'YOUR_EMAIL_PASSWORD') {
    echo "<div class='info'>";
    echo "<strong>⚠️ تنبيه:</strong> يجب تعيين كلمة مرور البريد الإلكتروني في السطر 36 من هذا الملف";
    echo "</div>";
}
echo "</div>";

// ============================================
// 3. اختبار الإرسال
// ============================================
echo "<div class='box'>";
echo "<h2>3. اختبار إرسال البريد</h2>";

// توليد رمز تجريبي
$testCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

// بريد الاستلام (يمكنك تغييره)
$testEmail = isset($_GET['email']) ? $_GET['email'] : 'info@eco-friendy.com';

echo "<form method='get'>";
echo "<p>البريد المستلم: <input type='email' name='email' value='$testEmail' style='padding:5px; width:300px;'>";
echo " <button type='submit' style='padding:5px 15px; background:#f39200; color:white; border:none; border-radius:5px; cursor:pointer;'>تحديث</button></p>";
echo "</form>";

echo "<div class='code'>رمز التحقق التجريبي: $testCode</div>";

if (isset($_GET['send'])) {
    echo "<h3>⏳ جاري الإرسال...</h3>";
    
    $mail = new PHPMailer(true);
    
    try {
        // إعدادات SMTP
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['secure'];
        $mail->Port = $config['port'];
        $mail->CharSet = 'UTF-8';
        
        // تعطيل التحقق من الشهادة (مؤقتاً للاختبار)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // تفعيل Debug
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = function($str, $level) {
            echo "<div style='background:#f9f9f9; padding:5px; margin:5px 0; font-size:12px; font-family:monospace;'>";
            echo htmlspecialchars($str);
            echo "</div>";
        };
        
        // المرسل والمستقبل
        $mail->setFrom($config['username'], 'Eco Friendly Store');
        $mail->addAddress($testEmail, 'مستخدم تجريبي');
        
        // المحتوى
        $mail->isHTML(true);
        $mail->Subject = 'اختبار إرسال البريد - Eco Friendly';
        $mail->Body = "
        <div style='font-family:Arial; padding:20px; background:#f5f5f5;'>
            <div style='max-width:600px; margin:0 auto; background:white; border-radius:15px; padding:30px;'>
                <h1 style='color:#f39200; text-align:center;'>🌿 Eco Friendly Store</h1>
                <h2 style='text-align:center;'>اختبار ناجح! ✅</h2>
                <p style='text-align:center; font-size:18px;'>تم إرسال البريد الإلكتروني بنجاح من Hostinger</p>
                <div style='background:#fff3e6; padding:20px; border-radius:10px; text-align:center; margin:20px 0;'>
                    <div style='font-size:12px; color:#c77400;'>رمز التحقق التجريبي</div>
                    <div style='font-size:36px; font-weight:bold; color:#f39200; letter-spacing:8px;'>$testCode</div>
                </div>
                <p style='text-align:center; color:#666;'>إذا وصلتك هذه الرسالة، فإن نظام البريد الإلكتروني يعمل بشكل صحيح!</p>
            </div>
        </div>
        ";
        
        $mail->send();
        
        echo "<div style='background:#d4edda; color:#155724; padding:20px; border-radius:10px; margin:20px 0;'>";
        echo "<h3 style='margin:0 0 10px 0;'>✅ نجح الإرسال!</h3>";
        echo "<p>تم إرسال البريد الإلكتروني إلى: <strong>$testEmail</strong></p>";
        echo "<p>تحقق من صندوق الوارد (أو البريد المزعج)</p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background:#f8d7da; color:#721c24; padding:20px; border-radius:10px; margin:20px 0;'>";
        echo "<h3 style='margin:0 0 10px 0;'>❌ فشل الإرسال</h3>";
        echo "<p><strong>الخطأ:</strong> {$mail->ErrorInfo}</p>";
        echo "<h4>الحلول المقترحة:</h4>";
        echo "<ul>";
        echo "<li>تأكد من كلمة مرور البريد الإلكتروني صحيحة</li>";
        echo "<li>تحقق من أن البريد info@eco-friendy.com موجود في Hostinger</li>";
        echo "<li>تأكد من أن المنفذ 465 غير محظور</li>";
        echo "<li>جرب استخدام Port 587 مع TLS بدلاً من SSL</li>";
        echo "</ul>";
        echo "</div>";
    }
} else {
    echo "<p><a href='?send=1&email=$testEmail' class='btn'>📧 إرسال بريد تجريبي</a></p>";
}

echo "</div>";

// ============================================
// 4. معلومات إضافية
// ============================================
echo "<div class='box'>";
echo "<h2>4. معلومات مهمة</h2>";
echo "<ul>";
echo "<li>✅ استخدم Port 465 مع SSL (الحالي)</li>";
echo "<li>✅ أو Port 587 مع TLS (بديل)</li>";
echo "<li>✅ اسم المستخدم: البريد الإلكتروني الكامل (info@eco-friendy.com)</li>";
echo "<li>✅ كلمة المرور: نفس كلمة المرور المستخدمة لتسجيل الدخول للبريد</li>";
echo "</ul>";

echo "<h3>كيفية الحصول على كلمة المرور:</h3>";
echo "<ol>";
echo "<li>اذهب إلى لوحة تحكم Hostinger (hpanel.hostinger.com)</li>";
echo "<li>اختر 'Email' من القائمة</li>";
echo "<li>اضغط على 'Manage' بجانب info@eco-friendy.com</li>";
echo "<li>يمكنك رؤية أو إعادة تعيين كلمة المرور</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>
