<?php
/**
 * ملف API لإضافة طلب جديد مع نظام الإيميلات المدمج
 * api/place-order.php
 * 
 * ✅ نظام الإيميلات مدمج بالكامل
 * ✅ معالجة أخطاء محسّنة
 * ✅ يعمل مع Hostinger SMTP
 */

// منع أي إخراج قبل الهيدر
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// إخفاء الأخطاء من الإخراج
ini_set('display_errors', 0);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit();
}

require_once '../config.php';

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// دالة للرد بصيغة JSON
function sendJsonResponse($success, $message, $data = [], $code = 200) {
    ob_clean();
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data), JSON_UNESCAPED_UNICODE);
    exit();
}

// التأكد من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'طريقة الطلب غير مسموحة', [], 405);
}

try {
    // التحقق من تسجيل الدخول
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        sendJsonResponse(false, 'يجب تسجيل الدخول أولاً', [], 401);
    }
    
    // قراءة البيانات
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        sendJsonResponse(false, 'لم يتم استلام بيانات صحيحة');
    }
    
    // التحقق من الحقول المطلوبة
    $requiredFields = ['user_id', 'customer_name', 'customer_phone', 'customer_address', 'payment_method', 'items', 'total_amount'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '') || (is_array($data[$field]) && empty($data[$field]))) {
            sendJsonResponse(false, "الحقل المطلوب مفقود أو فارغ: $field");
        }
    }
    
    // التحقق من الصلاحيات
    if ((int)$data['user_id'] !== (int)$_SESSION['user_id']) {
        sendJsonResponse(false, 'غير مصرح لك بإنشاء طلب لمستخدم آخر', [], 403);
    }
    
    // الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    if (!$pdo) {
        sendJsonResponse(false, 'فشل الاتصال بقاعدة البيانات', [], 500);
    }
    
    // بدء المعاملة
    $pdo->beginTransaction();
    
    // إنشاء رقم طلب فريد
    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    $customerEmail = $_SESSION['user_email'] ?? '';
    
    // إدراج الطلب
    $sql = "INSERT INTO orders (
                user_id, order_number, customer_name, customer_phone, customer_email,
                customer_address, shipping_address, notes, payment_method, 
                status, total_amount, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        (int)$data['user_id'],
        $orderNumber,
        $data['customer_name'],
        $data['customer_phone'],
        $customerEmail,
        $data['customer_address'],
        $data['shipping_address'] ?? $data['customer_address'],
        $data['notes'] ?? '',
        $data['payment_method'],
        $data['status'] ?? 'pending',
        (float)$data['total_amount']
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    // إدراج المنتجات
    $orderItems = [];
    if (!empty($data['items']) && is_array($data['items'])) {
        $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $itemStmt = $pdo->prepare($itemSql);
        
        foreach ($data['items'] as $item) {
            $quantity = (int)$item['quantity'];
            $price = (float)$item['price'];
            
            // جلب اسم المنتج من قاعدة البيانات
            $productName = 'منتج';
            if (isset($item['product_id'])) {
                $productQuery = $pdo->prepare("SELECT name FROM products WHERE id = ?");
                $productQuery->execute([(int)$item['product_id']]);
                $product = $productQuery->fetch(PDO::FETCH_ASSOC);
                if ($product) {
                    $productName = $product['name'];
                }
            }
            
            $itemStmt->execute([
                $orderId,
                (int)$item['product_id'],
                $quantity,
                $price
            ]);
            
            $orderItems[] = [
                'product_name' => $productName,
                'quantity' => $quantity,
                'price' => $price
            ];
        }
    } else {
        throw new Exception('يجب إضافة منتج واحد على الأقل');
    }
    
    // تأكيد المعاملة
    $pdo->commit();
    
    // ========== إرسال الإيميلات ==========
    $emailSent = false;
    $adminNotified = false;
    $emailError = null;
    
    if (!empty($customerEmail)) {
        try {
            // التحقق من وجود ملف mailer.php
            $mailerPath = __DIR__ . '/../mail/mailer.php';
            if (!file_exists($mailerPath)) {
                throw new Exception('ملف mailer.php غير موجود');
            }
            
            // تحميل PHPMailer
            require_once $mailerPath;
            
            // التحقق من وجود دالة sendMail
            if (!function_exists('sendMail')) {
                throw new Exception('دالة sendMail غير متوفرة');
            }
            
            // إنشاء محتوى HTML للعميل
            $itemsHtml = "";
            foreach ($orderItems as $item) {
                $itemTotal = $item['quantity'] * $item['price'];
                $itemsHtml .= "<tr>
                    <td style='padding:12px;border-bottom:1px solid #eee'>" . htmlspecialchars($item['product_name']) . "</td>
                    <td style='padding:12px;border-bottom:1px solid #eee;text-align:center'>{$item['quantity']}</td>
                    <td style='padding:12px;border-bottom:1px solid #eee;text-align:center'>" . number_format($item['price'], 2) . " د.أ</td>
                    <td style='padding:12px;border-bottom:1px solid #eee;text-align:center;font-weight:bold'>" . number_format($itemTotal, 2) . " د.أ</td>
                </tr>";
            }
            
            $subject = "تأكيد طلبك #$orderNumber - Eco Friendy 📦";
            $message = "<!DOCTYPE html><html dir='rtl'><head><meta charset='UTF-8'><style>
                body{font-family:'Segoe UI',Tahoma,sans-serif;background:#f5f5f5;margin:0;padding:0}
                .container{max-width:650px;margin:30px auto;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,.1);overflow:hidden}
                .header{background:linear-gradient(135deg,#4CAF50 0%,#2e7d32 100%);color:#fff;padding:40px 30px;text-align:center}
                .header h1{margin:0 0 10px 0;font-size:32px}
                .header p{margin:0;font-size:16px;opacity:0.9}
                .content{padding:40px 30px}
                .order-box{background:#f0f8f0;border-right:5px solid #4CAF50;padding:25px;margin:25px 0;border-radius:8px}
                .order-box h2{color:#4CAF50;margin:0 0 15px 0;font-size:22px}
                .order-box p{margin:8px 0;font-size:15px}
                .items-table{width:100%;border-collapse:collapse;margin:25px 0}
                .items-table th{background:#f5f5f5;padding:15px;text-align:right;font-weight:600;border-bottom:2px solid #ddd}
                .items-table td{font-size:14px;color:#555}
                .total{background:#f9f9f9;padding:20px;margin:25px 0;border-radius:8px;font-size:24px;font-weight:bold;color:#2e7d32;display:flex;justify-content:space-between}
                .button{display:inline-block;background:#4CAF50;color:#fff!important;padding:15px 40px;text-decoration:none;border-radius:8px;margin:25px 0;font-weight:600}
                .footer{background:#f9f9f9;padding:30px;text-align:center;color:#666;font-size:14px;border-top:1px solid #eee}
                .contact-info{margin-top:30px;padding:20px;background:#f9f9f9;border-radius:8px}
            </style></head><body>
                <div class='container'>
                    <div class='header'>
                        <h1>✅ تم تأكيد طلبك</h1>
                        <p>شكراً لثقتك في Eco Friendy</p>
                    </div>
                    <div class='content'>
                        <div class='order-box'>
                            <h2>📋 تفاصيل الطلب</h2>
                            <p><strong>رقم الطلب:</strong> <span style='color:#4CAF50;font-size:18px'>#" . htmlspecialchars($orderNumber) . "</span></p>
                            <p><strong>اسم العميل:</strong> " . htmlspecialchars($data['customer_name']) . "</p>
                            <p><strong>رقم الهاتف:</strong> " . htmlspecialchars($data['customer_phone']) . "</p>
                            <p><strong>العنوان:</strong> " . htmlspecialchars($data['customer_address']) . "</p>
                            <p><strong>طريقة الدفع:</strong> " . ($data['payment_method'] === 'cod' ? 'الدفع عند الاستلام 💵' : 'تحويل بنكي CliQ 🏦') . "</p>
                            <p><strong>التاريخ:</strong> " . date('Y-m-d H:i') . "</p>
                        </div>
                        
                        <h3 style='color:#2e7d32;font-size:20px;margin:30px 0 15px'>🛍️ المنتجات المطلوبة</h3>
                        <table class='items-table'>
                            <thead>
                                <tr>
                                    <th>المنتج</th>
                                    <th style='text-align:center'>الكمية</th>
                                    <th style='text-align:center'>السعر</th>
                                    <th style='text-align:center'>الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>$itemsHtml</tbody>
                        </table>
                        
                        <div class='total'>
                            <span>المجموع الكلي:</span>
                            <span>" . number_format($data['total_amount'], 2) . " د.أ</span>
                        </div>
                        
                        <center>
                            <a href='https://eco-friendy.com/my-orders.html' class='button'>📦 تتبع طلباتي</a>
                        </center>
                        
                        <div class='contact-info'>
                            <p style='margin-bottom:10px;color:#333'><strong>إذا كان لديك أي استفسار:</strong></p>
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
            
            // حفظ الإشعار في قاعدة البيانات
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, order_id, email, type, subject, message, status, attempts, created_at) VALUES (?, ?, ?, 'new_order', ?, ?, 'pending', 0, NOW())");
            $notifStmt->execute([(int)$data['user_id'], $orderId, $customerEmail, $subject, $message]);
            $notificationId = $pdo->lastInsertId();
            
            // إرسال البريد للعميل
            if (sendMail($customerEmail, $subject, $message)) {
                $pdo->prepare("UPDATE notifications SET status='sent', sent_at=NOW() WHERE id=?")->execute([$notificationId]);
                $emailSent = true;
                error_log("✅ Customer email sent to: $customerEmail");
            } else {
                $pdo->prepare("UPDATE notifications SET status='failed', attempts=1, error_message='SMTP sending failed' WHERE id=?")->execute([$notificationId]);
                error_log("❌ Failed to send customer email to: $customerEmail");
            }
            
            // إرسال إشعار للإدارة
            try {
                $itemsList = "";
                foreach ($orderItems as $item) {
                    $itemTotal = $item['quantity'] * $item['price'];
                    $itemsList .= "<li style='margin:8px 0'>{$item['product_name']} - الكمية: {$item['quantity']} - السعر: " . number_format($itemTotal, 2) . " د.أ</li>";
                }
                
                $adminSubject = "🔔 طلب جديد #$orderNumber";
                $adminMessage = "<!DOCTYPE html><html dir='rtl'><head><meta charset='UTF-8'><style>
                    body{font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;margin:0}
                    .container{max-width:600px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.1)}
                    .header{background:#ff9800;color:#fff;padding:30px;text-align:center}
                    .header h1{margin:0;font-size:28px}
                    .content{padding:30px}
                    .alert-box{background:#fff3cd;border-right:4px solid #ff9800;padding:20px;border-radius:5px;margin:20px 0}
                    .alert-box h2{color:#ff9800;margin:0 0 15px 0}
                    .info-row{margin:10px 0;font-size:15px}
                    .amount{color:#4CAF50;font-weight:bold;font-size:20px}
                    .items-list{background:#f9f9f9;padding:15px;border-radius:5px;margin:15px 0}
                    .footer{background:#f9f9f9;padding:20px;text-align:center;color:#666;font-size:13px}
                </style></head><body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🔔 طلب جديد يحتاج للمراجعة</h1>
                        </div>
                        
                        <div class='content'>
                            <div class='alert-box'>
                                <h2>⚠️ تنبيه: طلب جديد</h2>
                                <div class='info-row'><strong>رقم الطلب:</strong> #" . htmlspecialchars($orderNumber) . "</div>
                                <div class='info-row'><strong>اسم العميل:</strong> " . htmlspecialchars($data['customer_name']) . "</div>
                                <div class='info-row'><strong>رقم الهاتف:</strong> " . htmlspecialchars($data['customer_phone']) . "</div>
                                <div class='info-row'><strong>البريد الإلكتروني:</strong> " . htmlspecialchars($customerEmail) . "</div>
                                <div class='info-row'><strong>العنوان:</strong> " . htmlspecialchars($data['customer_address']) . "</div>
                                <div class='info-row'><strong>طريقة الدفع:</strong> " . ($data['payment_method'] === 'cod' ? 'الدفع عند الاستلام' : 'تحويل بنكي CliQ') . "</div>
                                <div class='info-row'><strong>المبلغ الإجمالي:</strong> <span class='amount'>" . number_format($data['total_amount'], 2) . " د.أ</span></div>
                                <div class='info-row'><strong>التاريخ:</strong> " . date('Y-m-d H:i:s') . "</div>
                            </div>
                            
                            <div class='items-list'>
                                <strong style='display:block;margin-bottom:10px'>المنتجات:</strong>
                                <ul style='margin:0;padding-right:20px'>$itemsList</ul>
                            </div>
                            
                            " . (!empty($data['notes']) ? "<div style='background:#e3f2fd;padding:15px;border-radius:5px;margin:15px 0'><strong>ملاحظات:</strong><p style='margin:5px 0 0'>" . htmlspecialchars($data['notes']) . "</p></div>" : "") . "
                        </div>
                        
                        <div class='footer'>
                            <p>Eco Friendy Admin Panel</p>
                        </div>
                    </div>
                </body></html>";
                
                if (sendMail('info@eco-friendy.com', $adminSubject, $adminMessage)) {
                    $adminNotified = true;
                    error_log("✅ Admin notification sent");
                } else {
                    error_log("❌ Failed to send admin notification");
                }
            } catch (Exception $e) {
                error_log("❌ Admin email error: " . $e->getMessage());
            }
            
        } catch (Exception $e) {
            $emailError = $e->getMessage();
            error_log("❌ Email system error: " . $e->getMessage());
        }
    }
    
    // الاستجابة النهائية
    $message = 'تم إنشاء الطلب بنجاح';
    if ($emailSent) {
        $message .= ' ✅ وتم إرسال إيميل التأكيد';
    } elseif (!empty($customerEmail) && !$emailSent) {
        $message .= ' ⚠️ لكن فشل إرسال إيميل التأكيد';
    }
    
    sendJsonResponse(true, $message, [
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'payment_method' => $data['payment_method'],
        'total_amount' => $data['total_amount'],
        'email_sent' => $emailSent,
        'admin_notified' => $adminNotified
    ], 201);
    
} catch (Exception $e) {
    // التراجع عن المعاملة
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("❌ Order creation failed: " . $e->getMessage());
    sendJsonResponse(false, $e->getMessage(), [], 400);
}
?>
