<?php
/**
 * API للتحقق من كود التفعيل
 * api/verify-email.php
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

    if (!$data) {
        throw new Exception('لم يتم استلام بيانات صحيحة');
    }

    // التحقق من الحقول المطلوبة
    if (!isset($data['email']) || !isset($data['code'])) {
        throw new Exception('البريد الإلكتروني وكود التفعيل مطلوبان');
    }

    $email = trim($data['email']);
    $code = trim($data['code']);

    // التحقق من صحة البريد الإلكتروني
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('البريد الإلكتروني غير صحيح');
    }

    // التحقق من كود التفعيل (6 أرقام)
    if (!preg_match('/^\d{6}$/', $code)) {
        throw new Exception('كود التفعيل يجب أن يكون 6 أرقام');
    }

    // الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }

    // جلب معلومات المستخدم
    $stmt = $pdo->prepare("
        SELECT id, fullname, username, email, verification_code, verification_expires, is_verified 
        FROM users 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('البريد الإلكتروني غير موجود');
    }

    // التحقق من أن الحساب غير مفعّل
    if ($user['is_verified']) {
        throw new Exception('الحساب مفعّل مسبقاً');
    }

    // التحقق من انتهاء صلاحية الكود
    if (strtotime($user['verification_expires']) < time()) {
        throw new Exception('انتهت صلاحية كود التفعيل. يرجى طلب كود جديد');
    }

    // التحقق من تطابق الكود
    if ($user['verification_code'] !== $code) {
        throw new Exception('كود التفعيل غير صحيح');
    }

    // تفعيل الحساب
    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET is_verified = 1, 
            status = 'active',
            verification_code = NULL,
            verification_expires = NULL,
            verified_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$user['id']]);

    // ========== إرسال إيميل ترحيبي ==========
    try {
        $mailerPath = __DIR__ . '/../mail/mailer.php';
        if (file_exists($mailerPath)) {
            require_once $mailerPath;
            
            if (function_exists('sendMail')) {
                $subject = "🎉 مرحباً بك في Eco Friendy!";
                
                $message = "<!DOCTYPE html><html dir='rtl'><head><meta charset='UTF-8'><style>
                    body{font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;margin:0}
                    .container{max-width:600px;margin:0 auto;background:#fff;border-radius:15px;overflow:hidden;box-shadow:0 5px 25px rgba(0,0,0,.15)}
                    .header{background:linear-gradient(135deg,#4CAF50,#2e7d32);color:#fff;padding:40px 30px;text-align:center}
                    .header h1{margin:0 0 10px;font-size:32px;font-weight:900}
                    .content{padding:40px 30px}
                    .success-box{background:#e8f5e9;border-radius:10px;padding:30px;margin:25px 0;text-align:center}
                    .success-icon{font-size:64px;margin-bottom:15px}
                    .success-box h2{color:#2e7d32;margin:0 0 15px;font-size:24px}
                    .success-box p{color:#1b5e20;line-height:1.8}
                    .benefits{background:#f9f9f9;padding:25px;border-radius:10px;margin:25px 0}
                    .benefits h3{color:#f39200;margin-bottom:20px;font-size:20px}
                    .benefit-item{display:flex;align-items:start;gap:15px;margin:15px 0;padding:15px;background:white;border-radius:8px}
                    .benefit-icon{font-size:32px;min-width:40px}
                    .benefit-text h4{color:#2e7d32;margin:0 0 5px;font-size:16px}
                    .benefit-text p{color:#555;margin:0;font-size:14px;line-height:1.6}
                    .cta-button{display:inline-block;background:linear-gradient(135deg,#f39200,#e68500);color:#fff!important;padding:18px 45px;text-decoration:none;border-radius:12px;margin:25px 0;font-weight:700;font-size:16px;box-shadow:0 4px 15px rgba(243,146,0,0.3)}
                    .footer{background:#f9f9f9;padding:30px;text-align:center;color:#666;font-size:14px;border-top:1px solid #eee}
                </style></head><body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🎊 تم تفعيل حسابك بنجاح!</h1>
                            <p>مرحباً بك في عائلة Eco Friendy</p>
                        </div>
                        
                        <div class='content'>
                            <div class='success-box'>
                                <div class='success-icon'>✅</div>
                                <h2>أهلاً " . htmlspecialchars($user['fullname']) . "!</h2>
                                <p>حسابك الآن مفعّل وجاهز للاستخدام. يمكنك البدء بالتسوق والاستمتاع بمنتجاتنا الصديقة للبيئة.</p>
                            </div>
                            
                            <div class='benefits'>
                                <h3>🌟 ما الذي يمكنك فعله الآن؟</h3>
                                
                                <div class='benefit-item'>
                                    <div class='benefit-icon'>🛍️</div>
                                    <div class='benefit-text'>
                                        <h4>تصفح منتجاتنا</h4>
                                        <p>اكتشف مجموعة واسعة من المنتجات الصديقة للبيئة</p>
                                    </div>
                                </div>
                                
                                <div class='benefit-item'>
                                    <div class='benefit-icon'>🎁</div>
                                    <div class='benefit-text'>
                                        <h4>عروض حصرية</h4>
                                        <p>احصل على خصومات وعروض خاصة للأعضاء</p>
                                    </div>
                                </div>
                                
                                <div class='benefit-item'>
                                    <div class='benefit-icon'>📦</div>
                                    <div class='benefit-text'>
                                        <h4>تتبع طلباتك</h4>
                                        <p>راقب حالة طلباتك وتاريخ الشراء بسهولة</p>
                                    </div>
                                </div>
                                
                                <div class='benefit-item'>
                                    <div class='benefit-icon'>🌱</div>
                                    <div class='benefit-text'>
                                        <h4>ساهم في حماية البيئة</h4>
                                        <p>كل عملية شراء تساهم في مستقبل أكثر استدامة</p>
                                    </div>
                                </div>
                            </div>
                            
                            <center>
                                <a href='https://eco-friendy.com/index.html' class='cta-button'>
                                    🛒 ابدأ التسوق الآن
                                </a>
                            </center>
                            
                            <div style='background:#fff3e6;padding:20px;border-radius:10px;margin:25px 0;border-right:4px solid #f39200'>
                                <p style='margin:0;color:#c77400;line-height:1.8'>
                                    <strong>💡 نصيحة:</strong> احفظ هذا البريد للرجوع إليه في المستقبل. يمكنك دائماً تسجيل الدخول باستخدام اسم المستخدم (<strong>" . htmlspecialchars($user['username']) . "</strong>) أو بريدك الإلكتروني.
                                </p>
                            </div>
                            
                            <div style='padding:15px;background:#f9f9f9;border-radius:8px;margin-top:20px'>
                                <p style='margin-bottom:10px;color:#333'><strong>تواصل معنا:</strong></p>
                                <p style='margin:5px 0'>📞 هاتف: <a href='tel:+962790083039' style='color:#4CAF50'>+962 79 008 3039</a></p>
                                <p style='margin:5px 0'>📧 بريد: <a href='mailto:info@eco-friendy.com' style='color:#4CAF50'>info@eco-friendy.com</a></p>
                                <p style='margin:5px 0'>💬 واتساب: <a href='https://wa.me/962790083039' style='color:#4CAF50'>+962 79 008 3039</a></p>
                            </div>
                        </div>
                        
                        <div class='footer'>
                            <p style='margin:0'><strong>Eco Friendy</strong> - منتجات صديقة للبيئة</p>
                            <p style='margin:5px 0'>© 2026 Eco Friendy. جميع الحقوق محفوظة.</p>
                        </div>
                    </div>
                </body></html>";

                sendMail($user['email'], $subject, $message);
            }
        }
    } catch (Exception $e) {
        error_log("❌ Welcome email error: " . $e->getMessage());
    }

    // الاستجابة
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'تم تفعيل حسابك بنجاح! يمكنك الآن تسجيل الدخول',
        'user' => [
            'id' => $user['id'],
            'fullname' => $user['fullname'],
            'username' => $user['username'],
            'email' => $user['email']
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
