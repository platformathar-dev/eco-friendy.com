<?php
/**
 * ملف API لإضافة طلب جديد مع نظام الإيميلات المدمج
 * api/place-order.php
 * 
 * ✅ لا يحتاج لملفات hooks منفصلة
 * ✅ يعمل مع PDO مباشرة
 * ✅ نظام الإيميلات مدمج بالكامل
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

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التأكد من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مسموحة'], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // التحقق من تسجيل الدخول
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً'], JSON_UNESCAPED_UNICODE);
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
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("الحقل المطلوب مفقود: $field");
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
    } else {
        throw new Exception('يجب إضافة منتج واحد على الأقل');
    }
    
    // تأكيد المعاملة
    $pdo->commit();
    
    // ===== إرسال الإيميلات =====
    $emailSent = false;
    $adminNotified = false;
    
    if (!empty($customerEmail)) {
        // تحميل PHPMailer
        require_once '../mail/mailer.php';
        
        // إنشاء إيميل العميل
        $itemsHtml = "";
        foreach ($orderItems as $item) {
            $itemTotal = $item['quantity'] * $item['price'];
            $itemsHtml .= "<tr>
                <td style='padding:12px;border-bottom:1px solid #eee'>" . htmlspecialchars($item['product_name']) . "</td>
                <td style='padding:12px;border-bottom:1px solid #eee;text-align:center'>" . $item['quantity'] . "</td>
                <td style='padding:12px;border-bottom:1px solid #eee;text-align:center'>" . number_format($item['price'], 2) . " JOD</td>
                <td style='padding:12px;border-bottom:1px solid #eee;text-align:center;font-weight:bold'>" . number_format($itemTotal, 2) . " JOD</td>
            </tr>";
        }
        
        $subject = "تأكيد طلبك #$orderNumber - Eco Friendy 📦";
        $message = "<!DOCTYPE html><html dir='rtl'><head><meta charset='UTF-8'><style>
            body{font-family:'Segoe UI',sans-serif;background:#f5f5f5;margin:0;padding:0}
            .container{max-width:650px;margin:30px auto;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,.1)}
            .header{background:linear-gradient(135deg,#4CAF50 0%,#2e7d32 100%);color:#fff;padding:40px 30px;text-align:center}
            .header h1{margin:0;font-size:32px}
            .content{padding:40px 30px}
            .order-box{background:#f0f8f0;border-right:5px solid #4CAF50;padding:25px;margin:25px 0;border-radius:8px}
            .items-table{width:100%;border-collapse:collapse;margin:25px 0}
            .items-table th{background:#f5f5f5;padding:15px;text-align:right;font-weight:600;border-bottom:2px solid #ddd}
            .total{background:#f9f9f9;padding:20px;margin:25px 0;border-radius:8px;font-size:24px;font-weight:bold;color:#2e7d32;display:flex;justify-content:space-between}
            .button{display:inline-block;background:#4CAF50;color:#fff!important;padding:15px 40px;text-decoration:none;border-radius:8px;margin:25px 0;font-weight:600}
            .footer{background:#f9f9f9;padding:30px;text-align:center;color:#666;font-size:14px;border-top:1px solid #eee}
        </style></head><body>
            <div class='container'>
                <div class='header'><h1>✅ تم تأكيد طلبك</h1><p>شكراً لثقتك في Eco Friendy</p></div>
                <div class='content'>
                    <div class='order-box'>
                        <h2 style='color:#4CAF50;margin-top:0'>تفاصيل الطلب</h2>
                        <p><strong>رقم الطلب:</strong> <span style='color:#4CAF50;font-size:18px'>#" . htmlspecialchars($orderNumber) . "</span></p>
                        <p><strong>اسم العميل:</strong> " . htmlspecialchars($data['customer_name']) . "</p>
                        <p><strong>التاريخ:</strong> " . date('Y-m-d H:i') . "</p>
                    </div>
                    <h3>المنتجات المطلوبة:</h3>
                    <table class='items-table'>
                        <thead><tr><th>المنتج</th><th style='text-align:center'>الكمية</th><th style='text-align:center'>السعر</th><th style='text-align:center'>الإجمالي</th></tr></thead>
                        <tbody>$itemsHtml</tbody>
                    </table>
                    <div class='total'><span>المجموع الكلي:</span><span>" . number_format($data['total_amount'], 2) . " JOD</span></div>
                    <center><a href='https://eco-friendy.com/orders' class='button'>تتبع الطلب</a></center>
                    <p style='margin-top:30px;color:#666;font-size:14px'>إذا كان لديك أي استفسار:<br>📧 <a href='mailto:info@eco-friendy.com' style='color:#4CAF50'>info@eco-friendy.com</a></p>
                </div>
                <div class='footer'><p style='margin:0'>© 2026 Eco Friendy. جميع الحقوق محفوظة.</p></div>
            </div>
        </body></html>";
        
        // حفظ في قاعدة البيانات
        try {
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, order_id, email, type, subject, message, status, attempts, created_at) VALUES (?, ?, ?, 'new_order', ?, ?, 'pending', 0, NOW())");
            $notifStmt->execute([(int)$data['user_id'], $orderId, $customerEmail, $subject, $message]);
            $notificationId = $pdo->lastInsertId();
            
            // إرسال البريد
            if (sendMail($customerEmail, $subject, $message)) {
                $pdo->prepare("UPDATE notifications SET status='sent', sent_at=NOW() WHERE id=?")->execute([$notificationId]);
                $emailSent = true;
            } else {
                $pdo->prepare("UPDATE notifications SET status='failed', attempts=1, error_message='SMTP failed' WHERE id=?")->execute([$notificationId]);
            }
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
        }
        
        // إرسال إيميل للإدارة
        try {
            $adminSubject = "🔔 طلب جديد #$orderNumber";
            $adminMessage = "<!DOCTYPE html><html dir='rtl'><head><meta charset='UTF-8'></head><body style='font-family:Arial;background:#f4f4f4;padding:20px'>
                <div style='max-width:600px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.1)'>
                    <div style='background:#ff9800;color:#fff;padding:30px;text-align:center'><h1 style='margin:0'>🔔 طلب جديد</h1></div>
                    <div style='padding:30px'>
                        <div style='background:#fff3cd;border-right:4px solid #ff9800;padding:20px;border-radius:5px'>
                            <h2 style='color:#ff9800;margin-top:0'>تنبيه: طلب يحتاج للمراجعة</h2>
                            <p><strong>رقم الطلب:</strong> #" . htmlspecialchars($orderNumber) . "</p>
                            <p><strong>العميل:</strong> " . htmlspecialchars($data['customer_name']) . "</p>
                            <p><strong>المبلغ:</strong> <span style='color:#4CAF50;font-weight:bold'>" . number_format($data['total_amount'], 2) . " JOD</span></p>
                            <p><strong>التاريخ:</strong> " . date('Y-m-d H:i:s') . "</p>
                        </div>
                    </div>
                </div>
            </body></html>";
            
            $adminNotified = sendMail('info@eco-friendy.com', $adminSubject, $adminMessage);
        } catch (Exception $e) {
            error_log("Admin email error: " . $e->getMessage());
        }
    }
    
    // الاستجابة
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
