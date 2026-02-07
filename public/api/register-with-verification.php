<?php
/**
 * API للتسجيل مع كود التفعيل
 * api/register.php
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
    $required_fields = ['fullname', 'username', 'email', 'phone', 'birthdate', 'gender', 'country', 'password'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("الحقل '{$field}' مطلوب");
        }
    }

    // تنظيف البيانات
    $fullname = trim($data['fullname']);
    $username = trim($data['username']);
    $email = trim($data['email']);
    $phone = trim($data['phone']);
    $birthdate = trim($data['birthdate']);
    $gender = trim($data['gender']);
    $country = trim($data['country']);
    $password = $data['password'];

    // التحقق من صحة البيانات
    if (strlen($fullname) < 3) {
        throw new Exception('الاسم الكامل يجب أن يكون 3 أحرف على الأقل');
    }

    if (strlen($username) < 3 || strlen($username) > 50) {
        throw new Exception('اسم المستخدم يجب أن يكون بين 3 و 50 حرف');
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        throw new Exception('اسم المستخدم يجب أن يحتوي على أحرف وأرقام و _ فقط');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('البريد الإلكتروني غير صحيح');
    }

    if (!preg_match('/^07\d{8}$/', $phone)) {
        throw new Exception('رقم الهاتف يجب أن يبدأ بـ 07 ويتكون من 10 أرقام');
    }

    $date = DateTime::createFromFormat('Y-m-d', $birthdate);
    if (!$date || $date->format('Y-m-d') !== $birthdate) {
        throw new Exception('تاريخ الميلاد غير صحيح');
    }

    $today = new DateTime();
    $age = $today->diff($date)->y;
    if ($age < 13) {
        throw new Exception('يجب أن يكون عمرك 13 سنة على الأقل');
    }

    if (!in_array($gender, ['male', 'female'])) {
        throw new Exception('الجنس غير صحيح');
    }

    if (strlen($password) < 8) {
        throw new Exception('كلمة المرور يجب أن تكون 8 أحرف على الأقل');
    }

    // الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }

    // التحقق من عدم وجود اسم المستخدم
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        throw new Exception('اسم المستخدم موجود مسبقاً');
    }

    // التحقق من عدم وجود البريد الإلكتروني
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('البريد الإلكتروني موجود مسبقاً');
    }

    // التحقق من عدم وجود رقم الهاتف
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        throw new Exception('رقم الهاتف موجود مسبقاً');
    }

    // تشفير كلمة المرور
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // ✅ إنشاء كود التفعيل (6 أرقام)
    $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // إدراج المستخدم الجديد (غير مفعّل)
    $sql = "INSERT INTO users (
                fullname, username, email, phone, birthdate, gender, country, password,
                is_verified, verification_code, verification_expires, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 'inactive', NOW())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $fullname,
        $username,
        $email,
        $phone,
        $birthdate,
        $gender,
        $country,
        $hashed_password,
        $verification_code,
        $verification_expires
    ]);

    if (!$result) {
        throw new Exception('فشل في إنشاء الحساب');
    }

    $user_id = $pdo->lastInsertId();

    // ========== إرسال إيميل كود التفعيل ==========
    $emailSent = false;
    
    try {
        $mailerPath = __DIR__ . '/../mail/mailer.php';
        if (file_exists($mailerPath)) {
            require_once $mailerPath;
            
            if (function_exists('sendMail')) {
                $subject = "🔐 كود تفعيل حسابك في Eco Friendy";
                
                $message = "<!DOCTYPE html><html dir='rtl'><head><meta charset='UTF-8'><style>
                    body{font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;margin:0}
                    .container{max-width:600px;margin:0 auto;background:#fff;border-radius:15px;overflow:hidden;box-shadow:0 5px 25px rgba(0,0,0,.15)}
                    .header{background:linear-gradient(135deg,#4CAF50,#2e7d32);color:#fff;padding:40px 30px;text-align:center}
                    .header h1{margin:0 0 10px;font-size:32px;font-weight:900}
                    .content{padding:40px 30px;text-align:center}
                    .welcome-box{background:#e8f5e9;border-radius:10px;padding:25px;margin:25px 0}
                    .welcome-box h2{color:#2e7d32;margin:0 0 15px;font-size:22px}
                    .code-box{background:linear-gradient(135deg,#fff3e6,#ffe0b3);border-radius:15px;padding:30px;margin:30px 0;border:3px solid #f39200}
                    .code-label{font-size:16px;color:#c77400;font-weight:600;margin-bottom:15px}
                    .code{font-size:48px;font-weight:900;color:#f39200;letter-spacing:8px;font-family:monospace;text-shadow:2px 2px 4px rgba(0,0,0,0.1)}
                    .expiry{color:#757575;font-size:14px;margin-top:15px}
                    .instructions{background:#f9f9f9;padding:20px;border-radius:10px;margin:20px 0;text-align:right}
                    .instructions h3{color:#f39200;margin-bottom:15px;font-size:18px}
                    .instructions ol{margin:10px 0;padding-right:25px;line-height:1.8}
                    .instructions li{margin:8px 0;color:#333}
                    .warning{background:#fff3cd;padding:15px;border-radius:8px;margin:20px 0;border-right:4px solid #f57c00}
                    .warning p{color:#c77400;margin:0;font-size:14px;line-height:1.6}
                    .footer{background:#f9f9f9;padding:30px;text-align:center;color:#666;font-size:14px;border-top:1px solid #eee}
                    .contact-info{margin-top:20px;padding:15px;background:#f9f9f9;border-radius:8px}
                </style></head><body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🎉 مرحباً بك في Eco Friendy</h1>
                            <p>خطوة واحدة لإكمال تسجيلك</p>
                        </div>
                        
                        <div class='content'>
                            <div class='welcome-box'>
                                <h2>مرحباً " . htmlspecialchars($fullname) . "! 👋</h2>
                                <p style='color:#2e7d32;line-height:1.8'>شكراً لانضمامك إلى عائلة Eco Friendy. نحن سعداء بوجودك معنا!</p>
                            </div>
                            
                            <div class='code-box'>
                                <div class='code-label'>كود التفعيل الخاص بك:</div>
                                <div class='code'>$verification_code</div>
                                <div class='expiry'>⏰ صالح لمدة 24 ساعة</div>
                            </div>
                            
                            <div class='instructions'>
                                <h3>📝 خطوات تفعيل حسابك:</h3>
                                <ol>
                                    <li>ارجع إلى صفحة التفعيل</li>
                                    <li>أدخل كود التفعيل المكون من 6 أرقام</li>
                                    <li>اضغط على زر \"تفعيل الحساب\"</li>
                                    <li>ابدأ بالتسوق واستمتع بمنتجاتنا الصديقة للبيئة!</li>
                                </ol>
                            </div>
                            
                            <div class='warning'>
                                <p><strong>⚠️ مهم:</strong> لم تطلب هذا الكود؟ يمكنك تجاهل هذا البريد بأمان. حسابك لن يتم تفعيله بدون إدخال الكود.</p>
                            </div>
                            
                            <div class='contact-info'>
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

                if (sendMail($email, $subject, $message)) {
                    $emailSent = true;
                    error_log("✅ Verification email sent to: $email");
                } else {
                    error_log("❌ Failed to send verification email to: $email");
                }
            }
        }
    } catch (Exception $e) {
        error_log("❌ Email error: " . $e->getMessage());
    }

    // الاستجابة
    $responseMessage = 'تم إنشاء حسابك بنجاح';
    if ($emailSent) {
        $responseMessage .= '. تم إرسال كود التفعيل إلى بريدك الإلكتروني';
    } else {
        $responseMessage .= '. فشل إرسال كود التفعيل، يرجى التواصل مع الدعم';
    }

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => $responseMessage,
        'email_sent' => $emailSent,
        'user' => [
            'id' => $user_id,
            'fullname' => $fullname,
            'username' => $username,
            'email' => $email,
            'is_verified' => false
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
