<?php
/**
 * ملف معالجة إرسال البريد التجريبي
 * send-test-email.php
 */

// منع أي إخراج قبل الهيدر
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// تسجيل الأخطاء في ملف بدلاً من عرضها
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit();
}

// دالة للرد بصيغة JSON
function sendJsonResponse($success, $message, $code = 200) {
    ob_clean(); // تنظيف أي إخراج سابق
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// التأكد من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'طريقة الطلب غير مسموحة', 405);
}

try {
    // قراءة البيانات
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        sendJsonResponse(false, 'لم يتم استلام أي بيانات');
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(false, 'خطأ في تحليل البيانات: ' . json_last_error_msg());
    }
    
    if (!$data) {
        sendJsonResponse(false, 'البيانات المستلمة غير صحيحة');
    }
    
    // التحقق من الحقول المطلوبة
    if (empty($data['email'])) {
        sendJsonResponse(false, 'البريد الإلكتروني مطلوب');
    }
    
    if (empty($data['subject'])) {
        sendJsonResponse(false, 'الموضوع مطلوب');
    }
    
    if (empty($data['message'])) {
        sendJsonResponse(false, 'الرسالة مطلوبة');
    }
    
    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        sendJsonResponse(false, 'البريد الإلكتروني غير صحيح');
    }
    
    $subject = htmlspecialchars($data['subject'], ENT_QUOTES, 'UTF-8');
    $message = $data['message'];
    
    // التحقق من وجود ملف البريد
    $mailerPath = __DIR__ . '/mail/mailer.php';
    if (!file_exists($mailerPath)) {
        // محاولة مسار بديل
        $mailerPath = dirname(__DIR__) . '/mail/mailer.php';
        if (!file_exists($mailerPath)) {
            sendJsonResponse(false, 'ملف إعدادات البريد غير موجود. يرجى التأكد من رفع ملف mail/mailer.php');
        }
    }
    
    // تحميل مكتبة البريد
    require_once $mailerPath;
    
    // التحقق من وجود دالة sendMail
    if (!function_exists('sendMail')) {
        sendJsonResponse(false, 'دالة إرسال البريد غير متوفرة. يرجى التحقق من ملف mailer.php');
    }
    
    // إنشاء محتوى HTML للبريد
    $htmlBody = "<!DOCTYPE html>
    <html dir='rtl'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: #f5f5f5;
                margin: 0;
                padding: 0;
                direction: rtl;
            }
            .email-container {
                max-width: 600px;
                margin: 30px auto;
                background: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #ffffff;
                padding: 40px 30px;
                text-align: center;
            }
            .header h1 {
                margin: 0 0 10px 0;
                font-size: 28px;
                font-weight: 700;
            }
            .header p {
                margin: 0;
                font-size: 16px;
                opacity: 0.9;
            }
            .content {
                padding: 40px 30px;
                color: #333333;
                line-height: 1.8;
            }
            .message-box {
                background: #f9f9f9;
                border-right: 4px solid #667eea;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                white-space: pre-wrap;
                word-wrap: break-word;
            }
            .footer {
                background: #f9f9f9;
                padding: 25px 30px;
                text-align: center;
                color: #999999;
                font-size: 13px;
                border-top: 1px solid #eeeeee;
            }
            .footer p {
                margin: 5px 0;
            }
            .footer a {
                color: #667eea;
                text-decoration: none;
            }
            .badge {
                display: inline-block;
                background: #4CAF50;
                color: white;
                padding: 5px 15px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                margin-top: 10px;
            }
            .info-row {
                margin: 10px 0;
                font-size: 14px;
                color: #666;
            }
            .info-row strong {
                color: #333;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>📧 " . htmlspecialchars($subject) . "</h1>
                <p>بريد من Eco Friendy</p>
            </div>
            
            <div class='content'>
                <div class='message-box'>" . nl2br(htmlspecialchars($message)) . "</div>
                
                <div style='margin-top: 30px;'>
                    <div class='info-row'>
                        <strong>📅 التاريخ:</strong> " . date('Y-m-d H:i:s') . "
                    </div>
                    <div class='info-row'>
                        <strong>🌐 الخادم:</strong> " . gethostname() . "
                    </div>
                    <div class='info-row'>
                        <strong>📍 IP:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "
                    </div>
                </div>
                
                <div style='text-align: center; margin-top: 30px;'>
                    <span class='badge'>✅ تم إرسال هذا البريد بنجاح</span>
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>Eco Friendy</strong> - منتجات صديقة للبيئة</p>
                <p>
                    📧 <a href='mailto:info@eco-friendy.com'>info@eco-friendy.com</a> | 
                    📞 <a href='tel:+962790083039'>+962 79 008 3039</a>
                </p>
                <p style='margin-top: 15px; color: #bbb;'>
                    © 2026 Eco Friendy. جميع الحقوق محفوظة.
                </p>
            </div>
        </div>
    </body>
    </html>";
    
    // إرسال البريد
    $sent = sendMail($email, $subject, $htmlBody, strip_tags($message));
    
    if ($sent) {
        sendJsonResponse(true, 'تم إرسال البريد بنجاح! ✅ يرجى التحقق من صندوق الوارد أو البريد المزعج.');
    } else {
        sendJsonResponse(false, 'فشل إرسال البريد. يرجى التحقق من:\n1. إعدادات SMTP في mailer.php\n2. كلمة مرور البريد\n3. سجل الأخطاء في error_log');
    }
    
} catch (Exception $e) {
    sendJsonResponse(false, 'حدث خطأ: ' . $e->getMessage(), 400);
} catch (Error $e) {
    sendJsonResponse(false, 'خطأ في النظام: ' . $e->getMessage(), 500);
}

// في حالة وصلنا هنا بدون استجابة
ob_end_clean();
sendJsonResponse(false, 'حدث خطأ غير متوقع');
?>
