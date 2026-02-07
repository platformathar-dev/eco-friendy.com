<?php
/**
 * API لإعادة إرسال كود التفعيل
 * api/resend-verification.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'طريقة الطلب غير مسموحة'
    ]);
    exit();
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['email'])) {
        throw new Exception('البريد الإلكتروني مطلوب');
    }

    $email = trim($data['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('البريد الإلكتروني غير صحيح');
    }

    // الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }

    // جلب معلومات المستخدم
    $stmt = $pdo->prepare("SELECT id, fullname, email, is_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('البريد الإلكتروني غير موجود');
    }

    if ($user['is_verified']) {
        throw new Exception('الحساب مفعّل مسبقاً');
    }

    // إنشاء كود تفعيل جديد
    $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // تحديث كود التفعيل
    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET verification_code = ?, 
            verification_expires = ?
        WHERE id = ?
    ");
    $updateStmt->execute([$verification_code, $verification_expires, $user['id']]);

    // إرسال الإيميل
    $emailSent = false;
    
    try {
        $mailerPath = __DIR__ . '/../mail/mailer.php';
        if (file_exists($mailerPath)) {
            require_once $mailerPath;
            
            if (function_exists('sendMail')) {
                $subject = "🔐 كود تفعيل جديد - Eco Friendy";
                
                $message = "<!DOCTYPE html><html dir='rtl'><head><meta charset='UTF-8'><style>
                    body{font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;margin:0}
                    .container{max-width:600px;margin:0 auto;background:#fff;border-radius:15px;overflow:hidden;box-shadow:0 5px 25px rgba(0,0,0,.15)}
                    .header{background:linear-gradient(135deg,#f39200,#e68500);color:#fff;padding:40px 30px;text-align:center}
                    .header h1{margin:0 0 10px;font-size:32px;font-weight:900}
                    .content{padding:40px 30px;text-align:center}
                    .code-box{background:linear-gradient(135deg,#fff3e6,#ffe0b3);border-radius:15px;padding:30px;margin:30px 0;border:3px solid #f39200}
                    .code-label{font-size:16px;color:#c77400;font-weight:600;margin-bottom:15px}
                    .code{font-size:48px;font-weight:900;color:#f39200;letter-spacing:8px;font-family:monospace;text-shadow:2px 2px 4px rgba(0,0,0,0.1)}
                    .expiry{color:#757575;font-size:14px;margin-top:15px}
                    .info-box{background:#e3f2fd;padding:20px;border-radius:10px;margin:20px 0;border-right:4px solid #3b82f6}
                    .info-box p{color:#1e40af;margin:0;font-size:15px;line-height:1.8}
                    .footer{background:#f9f9f9;padding:30px;text-align:center;color:#666;font-size:14px;border-top:1px solid #eee}
                </style></head><body>
                    <div class='container'>
                        <div class='header'>
                            <h1>📧 كود تفعيل جديد</h1>
                            <p>Eco Friendy</p>
                        </div>
                        
                        <div class='content'>
                            <p style='color:#333;font-size:16px;margin-bottom:25px'>مرحباً " . htmlspecialchars($user['fullname']) . "،</p>
                            <p style='color:#757575;margin-bottom:25px'>طلبت إعادة إرسال كود التفعيل. إليك الكود الجديد:</p>
                            
                            <div class='code-box'>
                                <div class='code-label'>كود التفعيل:</div>
                                <div class='code'>$verification_code</div>
                                <div class='expiry'>⏰ صالح لمدة 24 ساعة</div>
                            </div>
                            
                            <div class='info-box'>
                                <p><strong>💡 ملاحظة:</strong> الكود القديم لم يعد صالحاً. يرجى استخدام الكود الجديد أعلاه لتفعيل حسابك.</p>
                            </div>
                            
                            <div style='margin-top:30px;padding:15px;background:#f9f9f9;border-radius:8px'>
                                <p style='margin-bottom:10px;color:#333'><strong>تواصل معنا:</strong></p>
                                <p style='margin:5px 0'>📞 هاتف: <a href='tel:+962790083039' style='color:#f39200'>+962 79 008 3039</a></p>
                                <p style='margin:5px 0'>📧 بريد: <a href='mailto:info@eco-friendy.com' style='color:#f39200'>info@eco-friendy.com</a></p>
                            </div>
                        </div>
                        
                        <div class='footer'>
                            <p style='margin:0'><strong>Eco Friendy</strong> - منتجات صديقة للبيئة</p>
                            <p style='margin:5px 0'>© 2026 Eco Friendy. جميع الحقوق محفوظة.</p>
                        </div>
                    </div>
                </body></html>";

                if (sendMail($email, $subject, $message)) {
                    $emailSent = true;
                    error_log("✅ Verification code resent to: $email");
                } else {
                    error_log("❌ Failed to resend verification code to: $email");
                }
            }
        }
    } catch (Exception $e) {
        error_log("❌ Email error: " . $e->getMessage());
    }

    if (!$emailSent) {
        throw new Exception('فشل في إرسال البريد الإلكتروني');
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'تم إرسال كود تفعيل جديد إلى بريدك الإلكتروني'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
