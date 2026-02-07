<?php
/**
 * ملف API لإضافة طلب جديد مع نظام البريد الإلكتروني المحسّن
 * api/place-order.php
 * 
 * ✅ يعمل مع Hostinger SMTP
 * ✅ نظام إيميلات مدمج ومحسّن
 * ✅ تسجيل تفصيلي للأخطاء
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

// معالجة طلبات OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// تضمين ملف الإعدادات
require_once '../config.php';

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التأكد من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'طريقة الطلب غير مسموحة'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// ========== وظائف مساعدة ==========

/**
 * تسجيل الأخطاء بشكل تفصيلي
 */
function logError($message, $context = []) {
    $logMessage = date('[Y-m-d H:i:s] ') . $message;
    if (!empty($context)) {
        $logMessage .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    error_log($logMessage);
}

/**
 * إنشاء محتوى HTML للبريد الإلكتروني
 */
function createCustomerEmailHTML($orderData, $items) {
    $itemsHtml = "";
    foreach ($items as $item) {
        $itemTotal = $item['quantity'] * $item['price'];
        $itemsHtml .= "<tr>
            <td style='padding:12px;border-bottom:1px solid #eee'>" . htmlspecialchars($item['product_name']) . "</td>
            <td style='padding:12px;border-bottom:1px solid #eee;text-align:center'>{$item['quantity']}</td>
            <td style='padding:12px;border-bottom:1px solid #eee;text-align:center'>" . number_format($item['price'], 2) . " د.أ</td>
            <td style='padding:12px;border-bottom:1px solid #eee;text-align:center;font-weight:bold'>" . number_format($itemTotal, 2) . " د.أ</td>
        </tr>";
    }
    
    return "<!DOCTYPE html>
    <html dir='rtl'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
            .container { max-width: 650px; margin: 30px auto; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
            .header { background: linear-gradient(135deg, #4CAF50 0%, #2e7d32 100%); color: #ffffff; padding: 40px 30px; text-align: center; }
            .header h1 { margin: 0 0 10px 0; font-size: 32px; font-weight: 700; }
            .header p { margin: 0; font-size: 16px; opacity: 0.9; }
            .content { padding: 40px 30px; }
            .order-box { background: #f0f8f0; border-right: 5px solid #4CAF50; padding: 25px; margin: 25px 0; border-radius: 8px; }
            .order-box h2 { color: #4CAF50; margin: 0 0 15px 0; font-size: 22px; }
            .order-box p { margin: 8px 0; font-size: 15px; color: #333; }
            .order-box strong { color: #2e7d32; }
            .order-number { color: #4CAF50; font-size: 20px; font-weight: 700; }
            .items-table { width: 100%; border-collapse: collapse; margin: 25px 0; }
            .items-table th { background: #f5f5f5; padding: 15px; text-align: right; font-weight: 600; border-bottom: 2px solid #ddd; font-size: 14px; }
            .items-table td { font-size: 14px; color: #555; }
            .total-section { background: #f9f9f9; padding: 20px; margin: 25px 0; border-radius: 8px; }
            .total-row { display: flex; justify-content: space-between; align-items: center; margin: 10px 0; }
            .total-label { font-size: 16px; color: #666; }
            .total-value { font-size: 18px; font-weight: 700; color: #2e7d32; }
            .grand-total { border-top: 2px solid #4CAF50; padding-top: 15px; margin-top: 15px; }
            .grand-total .total-label { font-size: 20px; color: #2e7d32; font-weight: 700; }
            .grand-total .total-value { font-size: 24px; color: #4CAF50; }
            .button { display: inline-block; background: #4CAF50; color: #ffffff !important; padding: 15px 40px; text-decoration: none; border-radius: 8px; margin: 25px 0; font-weight: 600; font-size: 16px; transition: background 0.3s; }
            .button:hover { background: #45a049; }
            .contact-info { margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 8px; font-size: 14px; color: #666; }
            .contact-info a { color: #4CAF50; text-decoration: none; }
            .footer { background: #f9f9f9; padding: 30px; text-align: center; color: #999; font-size: 13px; border-top: 1px solid #eee; }
            .footer p { margin: 5px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✅ تم تأكيد طلبك بنجاح</h1>
                <p>شكراً لثقتك في Eco Friendy</p>
            </div>
            
            <div class='content'>
                <div class='order-box'>
                    <h2>📋 تفاصيل الطلب</h2>
                    <p><strong>رقم الطلب:</strong> <span class='order-number'>#{$orderData['order_number']}</span></p>
                    <p><strong>اسم العميل:</strong> {$orderData['customer_name']}</p>
                    <p><strong>رقم الهاتف:</strong> {$orderData['customer_phone']}</p>
                    <p><strong>العنوان:</strong> {$orderData['customer_address']}</p>
                    <p><strong>طريقة الدفع:</strong> " . ($orderData['payment_method'] === 'cod' ? 'الدفع عند الاستلام 💵' : 'تحويل بنكي CliQ 🏦') . "</p>
                    <p><strong>التاريخ:</strong> " . date('Y-m-d H:i') . "</p>
                </div>
                
                <h3 style='color: #2e7d32; font-size: 20px; margin: 30px 0 15px;'>🛍️ المنتجات المطلوبة</h3>
                <table class='items-table'>
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th style='text-align:center'>الكمية</th>
                            <th style='text-align:center'>السعر</th>
                            <th style='text-align:center'>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsHtml}
                    </tbody>
                </table>
                
                <div class='total-section'>
                    <div class='total-row grand-total'>
                        <span class='total-label'>المجموع الكلي:</span>
                        <span class='total-value'>" . number_format($orderData['total_amount'], 2) . " د.أ</span>
                    </div>
                </div>
                
                <center>
                    <a href='https://eco-friendy.com/my-orders.html' class='button'>📦 تتبع طلباتي</a>
                </center>
                
                <div class='contact-info'>
                    <p style='margin-bottom:10px;'><strong>إذا كان لديك أي استفسار:</strong></p>
                    <p>📞 هاتف: <a href='tel:+962790083039'>+962 79 008 3039</a></p>
                    <p>📧 بريد: <a href='mailto:info@eco-friendy.com'>info@eco-friendy.com</a></p>
                    <p>💬 واتساب: <a href='https://wa.me/962790083039'>+962 79 008 3039</a></p>
                </div>
            </div>
            
            <div class='footer'>
                <p><strong>Eco Friendy</strong> - منتجات صديقة للبيئة</p>
                <p>© 2026 Eco Friendy. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * إنشاء محتوى HTML للإدارة
 */
function createAdminEmailHTML($orderData, $items) {
    $itemsList = "";
    foreach ($items as $item) {
        $itemTotal = $item['quantity'] * $item['price'];
        $itemsList .= "<li style='margin:8px 0;'>{$item['product_name']} - الكمية: {$item['quantity']} - السعر: " . number_format($itemTotal, 2) . " د.أ</li>";
    }
    
    return "<!DOCTYPE html>
    <html dir='rtl'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; margin: 0; }
            .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,.1); }
            .header { background: #ff9800; color: #fff; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { padding: 30px; }
            .alert-box { background: #fff3cd; border-right: 4px solid #ff9800; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .alert-box h2 { color: #ff9800; margin: 0 0 15px 0; font-size: 20px; }
            .info-row { margin: 10px 0; font-size: 15px; }
            .info-row strong { color: #333; }
            .amount { color: #4CAF50; font-weight: bold; font-size: 20px; }
            .items-list { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 15px 0; }
            .items-list ul { margin: 0; padding-right: 20px; }
            .footer { background: #f9f9f9; padding: 20px; text-align: center; color: #666; font-size: 13px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔔 طلب جديد يحتاج للمراجعة</h1>
            </div>
            
            <div class='content'>
                <div class='alert-box'>
                    <h2>⚠️ تنبيه: طلب جديد</h2>
                    <div class='info-row'><strong>رقم الطلب:</strong> #{$orderData['order_number']}</div>
                    <div class='info-row'><strong>اسم العميل:</strong> {$orderData['customer_name']}</div>
                    <div class='info-row'><strong>رقم الهاتف:</strong> {$orderData['customer_phone']}</div>
                    <div class='info-row'><strong>البريد الإلكتروني:</strong> {$orderData['customer_email']}</div>
                    <div class='info-row'><strong>العنوان:</strong> {$orderData['customer_address']}</div>
                    <div class='info-row'><strong>طريقة الدفع:</strong> " . ($orderData['payment_method'] === 'cod' ? 'الدفع عند الاستلام' : 'تحويل بنكي CliQ') . "</div>
                    <div class='info-row'><strong>المبلغ الإجمالي:</strong> <span class='amount'>" . number_format($orderData['total_amount'], 2) . " د.أ</span></div>
                    <div class='info-row'><strong>التاريخ:</strong> " . date('Y-m-d H:i:s') . "</div>
                </div>
                
                <div class='items-list'>
                    <strong style='display:block;margin-bottom:10px;'>المنتجات:</strong>
                    <ul>{$itemsList}</ul>
                </div>
                
                " . (!empty($orderData['notes']) ? "<div style='background:#e3f2fd;padding:15px;border-radius:5px;margin:15px 0;'><strong>ملاحظات:</strong><p style='margin:5px 0 0;'>{$orderData['notes']}</p></div>" : "") . "
            </div>
            
            <div class='footer'>
                <p>Eco Friendy Admin Panel</p>
            </div>
        </div>
    </body>
    </html>";
}

// ========== المعالجة الرئيسية ==========

try {
    // التحقق من تسجيل الدخول
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'يجب تسجيل الدخول أولاً'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // قراءة البيانات
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('لم يتم استلام بيانات صحيحة');
    }
    
    // التحقق من الحقول المطلوبة
    $requiredFields = ['user_id', 'customer_name', 'customer_phone', 'customer_address', 'payment_method', 'items', 'total_amount'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '') || (is_array($data[$field]) && empty($data[$field]))) {
            throw new Exception("الحقل المطلوب مفقود أو فارغ: $field");
        }
    }
    
    // التحقق من الصلاحيات
    if ((int)$data['user_id'] !== (int)$_SESSION['user_id']) {
        throw new Exception('غير مصرح لك بإنشاء طلب لمستخدم آخر');
    }
    
    // الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    // بدء المعاملة
    $pdo->beginTransaction();
    
    // إنشاء رقم طلب فريد
    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    $customerEmail = $_SESSION['user_email'] ?? '';
    
    // إدراج الطلب في قاعدة البيانات
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
    logError("✅ Order created successfully", ['order_id' => $orderId, 'order_number' => $orderNumber]);
    
    // إدراج منتجات الطلب
    $orderItems = [];
    if (!empty($data['items']) && is_array($data['items'])) {
        $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $itemStmt = $pdo->prepare($itemSql);
        
        foreach ($data['items'] as $item) {
            $quantity = (int)$item['quantity'];
            $price = (float)$item['price'];
            
            $itemStmt->execute([
                $orderId,
                (int)$item['product_id'],
                $quantity,
                $price
            ]);
            
            $orderItems[] = [
                'product_name' => $item['product_name'] ?? 'منتج',
                'quantity' => $quantity,
                'price' => $price
            ];
        }
        
        logError("✅ Order items added", ['count' => count($orderItems)]);
    } else {
        throw new Exception('يجب إضافة منتج واحد على الأقل');
    }
    
    // تأكيد المعاملة
    $pdo->commit();
    logError("✅ Database transaction committed successfully");
    
    // ========== إرسال البريد الإلكتروني ==========
    $emailSent = false;
    $adminNotified = false;
    $emailError = null;
    
    if (!empty($customerEmail)) {
        try {
            // تحميل مكتبة البريد
            require_once __DIR__ . '/../mail/mailer.php';
            
            // إعداد بيانات الطلب للبريد
            $orderData = [
                'order_number' => $orderNumber,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $customerEmail,
                'customer_address' => $data['customer_address'],
                'payment_method' => $data['payment_method'],
                'total_amount' => $data['total_amount'],
                'notes' => $data['notes'] ?? ''
            ];
            
            // إنشاء محتوى البريد للعميل
            $subject = "تأكيد طلبك #$orderNumber - Eco Friendy 📦";
            $body = createCustomerEmailHTML($orderData, $orderItems);
            
            // حفظ الإشعار في قاعدة البيانات
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, order_id, email, type, subject, message, status, attempts, created_at) VALUES (?, ?, ?, 'new_order', ?, ?, 'pending', 0, NOW())");
            $notifStmt->execute([(int)$data['user_id'], $orderId, $customerEmail, $subject, $body]);
            $notificationId = $pdo->lastInsertId();
            
            logError("📧 Attempting to send customer email", ['to' => $customerEmail, 'notification_id' => $notificationId]);
            
            // إرسال البريد
            if (sendMail($customerEmail, $subject, $body)) {
                $pdo->prepare("UPDATE notifications SET status='sent', sent_at=NOW() WHERE id=?")->execute([$notificationId]);
                $emailSent = true;
                logError("✅ Customer email sent successfully");
            } else {
                $pdo->prepare("UPDATE notifications SET status='failed', attempts=1, error_message='SMTP sending failed' WHERE id=?")->execute([$notificationId]);
                $emailError = "فشل إرسال البريد الإلكتروني";
                logError("❌ Customer email failed to send");
            }
            
            // إرسال بريد للإدارة
            try {
                $adminSubject = "🔔 طلب جديد #$orderNumber";
                $adminBody = createAdminEmailHTML($orderData, $orderItems);
                
                logError("📧 Attempting to send admin email");
                
                if (sendMail('info@eco-friendy.com', $adminSubject, $adminBody)) {
                    $adminNotified = true;
                    logError("✅ Admin email sent successfully");
                } else {
                    logError("❌ Admin email failed to send");
                }
            } catch (Exception $e) {
                logError("❌ Admin email exception", ['error' => $e->getMessage()]);
            }
            
        } catch (Exception $e) {
            $emailError = $e->getMessage();
            logError("❌ Email sending exception", ['error' => $e->getMessage()]);
        }
    } else {
        logError("⚠️ No customer email provided, skipping email notification");
    }
    
    // ========== الاستجابة النهائية ==========
    $message = 'تم إنشاء الطلب بنجاح';
    if ($emailSent) {
        $message .= ' ✅ وتم إرسال إيميل التأكيد';
    } elseif (!empty($customerEmail) && !$emailSent) {
        $message .= ' ⚠️ لكن فشل إرسال إيميل التأكيد';
    }
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'payment_method' => $data['payment_method'],
        'total_amount' => $data['total_amount'],
        'email_sent' => $emailSent,
        'admin_notified' => $adminNotified,
        'email_error' => $emailError
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // التراجع عن المعاملة في حالة الخطأ
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        logError("🔄 Database transaction rolled back");
    }
    
    logError("❌ Order creation failed", ['error' => $e->getMessage()]);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
