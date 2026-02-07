<?php
// ملف API لإضافة طلب جديد مع نظام الإيميلات
// api/place-order.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// معالجة طلبات OPTIONS (لـ CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';
require_once '../mail/mailer.php';  // ⭐ نظام الإيميلات

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ⭐⭐⭐ دالة إرسال إيميل تأكيد للعميل ⭐⭐⭐
function sendOrderConfirmationEmail($pdo, $orderId, $userId, $email, $customerName, $orderNumber, $totalAmount, $items) {
    $subject = "تأكيد طلبك #$orderNumber - Eco Friendy 📦";
    
    // بناء قائمة المنتجات
    $itemsHtml = "";
    foreach ($items as $item) {
        $itemTotal = $item['quantity'] * $item['price'];
        $itemsHtml .= "
        <tr>
            <td style='padding: 12px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($item['product_name']) . "</td>
            <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: center;'>" . $item['quantity'] . "</td>
            <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: center;'>" . number_format($item['price'], 2) . " JOD</td>
            <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: center; font-weight: bold;'>" . number_format($itemTotal, 2) . " JOD</td>
        </tr>";
    }
    
    $message = "
    <!DOCTYPE html>
    <html dir='rtl' lang='ar'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
            .container { max-width: 650px; margin: 30px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #4CAF50 0%, #2e7d32 100%); color: white; padding: 40px 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 32px; }
            .content { padding: 40px 30px; }
            .order-box { background: #f0f8f0; border-right: 5px solid #4CAF50; padding: 25px; margin: 25px 0; border-radius: 8px; }
            .items-table { width: 100%; border-collapse: collapse; margin: 25px 0; }
            .items-table th { background: #f5f5f5; padding: 15px; text-align: right; font-weight: 600; border-bottom: 2px solid #ddd; }
            .total-section { background: #f9f9f9; padding: 20px; margin: 25px 0; border-radius: 8px; text-align: left; }
            .total-row { font-size: 24px; font-weight: bold; color: #2e7d32; display: flex; justify-content: space-between; }
            .button { display: inline-block; background: #4CAF50; color: white !important; padding: 15px 40px; text-decoration: none; border-radius: 8px; margin: 25px 0; font-weight: 600; }
            .footer { background: #f9f9f9; padding: 30px; text-align: center; color: #666; font-size: 14px; border-top: 1px solid #eee; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✅ تم تأكيد طلبك</h1>
                <p>شكراً لثقتك في Eco Friendy</p>
            </div>
            <div class='content'>
                <div class='order-box'>
                    <h2 style='color: #4CAF50; margin-top: 0;'>تفاصيل الطلب</h2>
                    <p><strong>رقم الطلب:</strong> <span style='color: #4CAF50; font-size: 18px;'>#" . htmlspecialchars($orderNumber) . "</span></p>
                    <p><strong>اسم العميل:</strong> " . htmlspecialchars($customerName) . "</p>
                    <p><strong>التاريخ:</strong> " . date('Y-m-d H:i') . "</p>
                </div>
                
                <h3>المنتجات المطلوبة:</h3>
                <table class='items-table'>
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th style='text-align: center;'>الكمية</th>
                            <th style='text-align: center;'>السعر</th>
                            <th style='text-align: center;'>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>$itemsHtml</tbody>
                </table>
                
                <div class='total-section'>
                    <div class='total-row'>
                        <span>المجموع الكلي:</span>
                        <span>" . number_format($totalAmount, 2) . " JOD</span>
                    </div>
                </div>
                
                <center><a href='https://eco-friendy.com/orders' class='button'>تتبع الطلب</a></center>
                
                <p style='margin-top: 30px; color: #666; font-size: 14px;'>
                    إذا كان لديك أي استفسار، لا تتردد في التواصل:<br>
                    📧 <a href='mailto:info@eco-friendy.com' style='color: #4CAF50;'>info@eco-friendy.com</a>
                </p>
            </div>
            <div class='footer'>
                <p style='margin: 0;'>© 2026 Eco Friendy. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </body>
    </html>";
    
    try {
        // حفظ الإشعار في قاعدة البيانات
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, order_id, email, type, subject, message, status, attempts, created_at) 
            VALUES (?, ?, ?, 'new_order', ?, ?, 'pending', 0, NOW())
        ");
        $stmt->execute([$userId, $orderId, $email, $subject, $message]);
        $notificationId = $pdo->lastInsertId();
        
        // محاولة إرسال البريد
        if (sendMail($email, $subject, $message)) {
            // تحديث الحالة إلى "مرسل"
            $updateStmt = $pdo->prepare("UPDATE notifications SET status = 'sent', sent_at = NOW() WHERE id = ?");
            $updateStmt->execute([$notificationId]);
            error_log("✅ Order email sent to: $email");
            return true;
        } else {
            // تحديث الحالة إلى "فشل"
            $updateStmt = $pdo->prepare("UPDATE notifications SET status = 'failed', attempts = 1, error_message = 'SMTP failed' WHERE id = ?");
            $updateStmt->execute([$notificationId]);
            error_log("❌ Failed to send order email to: $email");
            return false;
        }
    } catch (Exception $e) {
        error_log("❌ Email exception: " . $e->getMessage());
        return false;
    }
}

// ⭐⭐⭐ دالة إرسال إشعار للإدارة ⭐⭐⭐
function sendAdminNotification($adminEmail, $orderNumber, $customerName, $totalAmount) {
    $subject = "🔔 طلب جديد #$orderNumber يحتاج للمراجعة";
    
    $message = "
    <!DOCTYPE html>
    <html dir='rtl' lang='ar'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial; background: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: #ff9800; color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .alert-box { background: #fff3cd; border-right: 4px solid #ff9800; padding: 20px; margin: 20px 0; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔔 طلب جديد</h1>
            </div>
            <div class='content'>
                <div class='alert-box'>
                    <h2 style='margin-top: 0; color: #ff9800;'>تنبيه: طلب يحتاج للمراجعة</h2>
                    <p><strong>رقم الطلب:</strong> #" . htmlspecialchars($orderNumber) . "</p>
                    <p><strong>اسم العميل:</strong> " . htmlspecialchars($customerName) . "</p>
                    <p><strong>قيمة الطلب:</strong> <span style='color: #4CAF50; font-weight: bold;'>" . number_format($totalAmount, 2) . " JOD</span></p>
                    <p><strong>التاريخ:</strong> " . date('Y-m-d H:i:s') . "</p>
                </div>
            </div>
        </div>
    </body>
    </html>";
    
    try {
        return sendMail($adminEmail, $subject, $message);
    } catch (Exception $e) {
        error_log("Failed to send admin notification: " . $e->getMessage());
        return false;
    }
}

// ============================================
// المعالجة الرئيسية للطلب
// ============================================

// التأكد من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'طريقة الطلب غير مسموحة'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

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
    
    // قراءة البيانات من الطلب
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // التحقق من وجود البيانات
    if (!$data) {
        throw new Exception('لم يتم استلام بيانات صحيحة');
    }
    
    // التحقق من الحقول المطلوبة
    $requiredFields = ['user_id', 'customer_name', 'customer_phone', 'customer_address', 'payment_method', 'items', 'total_amount'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("الحقل المطلوب مفقود: $field");
        }
    }
    
    // التحقق من أن المستخدم يطلب لنفسه
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
    
    // إدراج الطلب الرئيسي
    $sql = "INSERT INTO orders (
                user_id, 
                order_number, 
                customer_name, 
                customer_phone, 
                customer_email,
                customer_address, 
                shipping_address,
                notes,
                payment_method, 
                status, 
                total_amount,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
    
    $customerEmail = $_SESSION['user_email'] ?? '';
    
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
    
    // إدراج منتجات الطلب
    $orderItems = [];
    if (!empty($data['items']) && is_array($data['items'])) {
        $itemSql = "INSERT INTO order_items (
                        order_id, 
                        product_id, 
                        quantity, 
                        price
                    ) VALUES (?, ?, ?, ?)";
        
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
            
            // حفظ تفاصيل المنتج للإيميل
            $orderItems[] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'] ?? 'منتج',
                'quantity' => $quantity,
                'price' => $price
            ];
        }
    } else {
        throw new Exception('يجب إضافة منتج واحد على الأقل');
    }
    
    // تأكيد المعاملة
    $pdo->commit();
    
    // ⭐⭐⭐ إرسال الإيميلات ⭐⭐⭐
    $emailSent = false;
    $adminNotified = false;
    
    if (!empty($customerEmail)) {
        try {
            // إرسال إيميل للعميل
            $emailSent = sendOrderConfirmationEmail(
                $pdo,
                $orderId,
                (int)$data['user_id'],
                $customerEmail,
                $data['customer_name'],
                $orderNumber,
                (float)$data['total_amount'],
                $orderItems
            );
            
            // إرسال إيميل للإدارة
            $adminNotified = sendAdminNotification(
                'info@eco-friendy.com',
                $orderNumber,
                $data['customer_name'],
                (float)$data['total_amount']
            );
            
        } catch (Exception $emailEx) {
            // تسجيل الخطأ لكن لا نفشل الطلب
            error_log("❌ Email error: " . $emailEx->getMessage());
        }
    }
    
    // إرجاع استجابة نجاح
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'تم إنشاء الطلب بنجاح' . ($emailSent ? ' ✅ وتم إرسال إيميل التأكيد' : ''),
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'payment_method' => $data['payment_method'],
        'total_amount' => $data['total_amount'],
        'email_sent' => $emailSent,
        'admin_notified' => $adminNotified
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // التراجع عن المعاملة في حالة الخطأ
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
