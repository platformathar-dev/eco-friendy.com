<?php
/**
 * API لتحديث حالة الطلب مع إرسال إيميل إشعار للعميل
 * api/update-order-status.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

try {
    // التحقق من تسجيل الدخول (للإدارة فقط)
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        echo json_encode([
            'success' => false,
            'message' => 'يجب تسجيل الدخول أولاً'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // قراءة البيانات
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['order_id']) || !isset($data['status'])) {
        echo json_encode([
            'success' => false,
            'message' => 'بيانات غير مكتملة'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $orderId = (int)$data['order_id'];
    $newStatus = $data['status'];

    // التحقق من صحة الحالة
    $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        echo json_encode([
            'success' => false,
            'message' => 'حالة غير صالحة'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    if (!$pdo) {
        echo json_encode([
            'success' => false,
            'message' => 'فشل الاتصال بقاعدة البيانات'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // جلب معلومات الطلب الحالية
    $stmt = $pdo->prepare("
        SELECT 
            id, order_number, user_id, customer_name, customer_email, 
            customer_phone, customer_address, total_amount, status,
            payment_method, created_at
        FROM orders 
        WHERE id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode([
            'success' => false,
            'message' => 'الطلب غير موجود'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // حفظ الحالة القديمة
    $oldStatus = $order['status'];

    // تحديث حالة الطلب
    $updateStmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $updateStmt->execute([$newStatus, $orderId]);

    // ========== إرسال إيميل للعميل عند تغيير الحالة ==========
    $emailSent = false;
    
    if (!empty($order['customer_email']) && $oldStatus !== $newStatus) {
        try {
            // تحميل PHPMailer
            $mailerPath = __DIR__ . '/../mail/mailer.php';
            if (file_exists($mailerPath)) {
                require_once $mailerPath;
                
                if (function_exists('sendMail')) {
                    // إعداد محتوى الإيميل حسب الحالة
                    $statusMessages = [
                        'pending' => [
                            'title' => '⏳ طلبك قيد الانتظار',
                            'message' => 'طلبك الآن في قائمة الانتظار وسيتم مراجعته قريباً.',
                            'color' => '#f57c00',
                            'bg_color' => '#fff3e6'
                        ],
                        'processing' => [
                            'title' => '🔄 طلبك قيد التنفيذ',
                            'message' => 'طلبك الآن قيد التنفيذ ويتم تجهيزه للشحن.',
                            'color' => '#f39200',
                            'bg_color' => '#fff8f0'
                        ],
                        'completed' => [
                            'title' => '✅ تم إكمال طلبك',
                            'message' => 'تم إكمال طلبك بنجاح! شكراً لثقتك في Eco Friendy.',
                            'color' => '#388e3c',
                            'bg_color' => '#e8f5e9'
                        ],
                        'cancelled' => [
                            'title' => '❌ تم إلغاء طلبك',
                            'message' => 'تم إلغاء طلبك. إذا كان لديك أي استفسار، يرجى التواصل معنا.',
                            'color' => '#d32f2f',
                            'bg_color' => '#ffebee'
                        ]
                    ];

                    $statusInfo = $statusMessages[$newStatus];
                    
                    // جلب المنتجات
                    $itemsStmt = $pdo->prepare("
                        SELECT oi.*, p.name as product_name 
                        FROM order_items oi 
                        LEFT JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = ?
                    ");
                    $itemsStmt->execute([$orderId]);
                    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

                    $itemsHtml = "";
                    foreach ($items as $item) {
                        $itemTotal = $item['quantity'] * $item['price'];
                        $itemsHtml .= "<tr style='border-bottom:1px solid #eee'>
                            <td style='padding:15px;text-align:right'>" . htmlspecialchars($item['product_name'] ?: 'منتج') . "</td>
                            <td style='padding:15px;text-align:center'>" . $item['quantity'] . "</td>
                            <td style='padding:15px;text-align:center'>" . number_format($item['price'], 2) . " د.أ</td>
                            <td style='padding:15px;text-align:center;font-weight:bold'>" . number_format($itemTotal, 2) . " د.أ</td>
                        </tr>";
                    }

                    $subject = $statusInfo['title'] . " #" . $order['order_number'];
                    
                    $message = "<!DOCTYPE html><html dir='rtl'><head><meta charset='UTF-8'><style>
                        body{font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;margin:0}
                        .container{max-width:650px;margin:0 auto;background:#fff;border-radius:15px;overflow:hidden;box-shadow:0 5px 25px rgba(0,0,0,.15)}
                        .header{background:linear-gradient(135deg," . $statusInfo['color'] . "," . $statusInfo['color'] . "dd);color:#fff;padding:40px 30px;text-align:center}
                        .header h1{margin:0 0 10px;font-size:32px;font-weight:900}
                        .header p{margin:0;font-size:16px;opacity:0.95}
                        .content{padding:40px 30px}
                        .status-box{background:" . $statusInfo['bg_color'] . ";border-right:5px solid " . $statusInfo['color'] . ";padding:25px;border-radius:10px;margin:25px 0}
                        .status-box h2{color:" . $statusInfo['color'] . ";margin:0 0 15px;font-size:22px}
                        .status-box p{margin:0;font-size:15px;color:#333;line-height:1.8}
                        .order-box{background:#f9f9f9;padding:20px;border-radius:10px;margin:25px 0}
                        .order-box p{margin:10px 0;font-size:15px;color:#333}
                        .items-table{width:100%;border-collapse:collapse;margin:25px 0}
                        .items-table th{background:#f5f5f5;padding:15px;text-align:right;font-weight:600;border-bottom:2px solid #ddd}
                        .items-table td{font-size:14px;color:#555}
                        .total{background:#f9f9f9;padding:20px;margin:25px 0;border-radius:8px;font-size:24px;font-weight:bold;color:" . $statusInfo['color'] . ";display:flex;justify-content:space-between}
                        .button{display:inline-block;background:" . $statusInfo['color'] . ";color:#fff!important;padding:15px 40px;text-decoration:none;border-radius:8px;margin:25px 0;font-weight:600}
                        .footer{background:#f9f9f9;padding:30px;text-align:center;color:#666;font-size:14px;border-top:1px solid #eee}
                        .contact-info{margin-top:30px;padding:20px;background:#f9f9f9;border-radius:8px}
                    </style></head><body>
                        <div class='container'>
                            <div class='header'>
                                <h1>" . $statusInfo['title'] . "</h1>
                                <p>Eco Friendy</p>
                            </div>
                            <div class='content'>
                                <div class='status-box'>
                                    <h2>تحديث حالة الطلب</h2>
                                    <p>" . $statusInfo['message'] . "</p>
                                </div>
                                
                                <div class='order-box'>
                                    <p><strong>رقم الطلب:</strong> <span style='color:" . $statusInfo['color'] . ";font-size:18px'>#" . htmlspecialchars($order['order_number']) . "</span></p>
                                    <p><strong>اسم العميل:</strong> " . htmlspecialchars($order['customer_name']) . "</p>
                                    <p><strong>رقم الهاتف:</strong> " . htmlspecialchars($order['customer_phone']) . "</p>
                                    <p><strong>العنوان:</strong> " . htmlspecialchars($order['customer_address']) . "</p>
                                    <p><strong>طريقة الدفع:</strong> " . ($order['payment_method'] === 'cod' ? 'الدفع عند الاستلام 💵' : 'تحويل بنكي CliQ 🏦') . "</p>
                                    <p><strong>تاريخ الطلب:</strong> " . $order['created_at'] . "</p>
                                </div>
                                
                                <h3 style='color:" . $statusInfo['color'] . ";font-size:20px;margin:30px 0 15px'>🛍️ المنتجات</h3>
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
                                    <span>" . number_format($order['total_amount'], 2) . " د.أ</span>
                                </div>
                                
                                <center>
                                    <a href='https://eco-friendy.com/my-orders.html' class='button'>📦 تتبع طلباتي</a>
                                </center>
                                
                                <div class='contact-info'>
                                    <p style='margin-bottom:10px;color:#333'><strong>إذا كان لديك أي استفسار:</strong></p>
                                    <p style='margin:5px 0'>📞 هاتف: <a href='tel:+962790083039' style='color:" . $statusInfo['color'] . "'>+962 79 008 3039</a></p>
                                    <p style='margin:5px 0'>📧 بريد: <a href='mailto:info@eco-friendy.com' style='color:" . $statusInfo['color'] . "'>info@eco-friendy.com</a></p>
                                    <p style='margin:5px 0'>💬 واتساب: <a href='https://wa.me/962790083039' style='color:" . $statusInfo['color'] . "'>+962 79 008 3039</a></p>
                                </div>
                            </div>
                            
                            <div class='footer'>
                                <p style='margin:0'><strong>Eco Friendy</strong> - منتجات صديقة للبيئة</p>
                                <p style='margin:5px 0'>© 2026 Eco Friendy. جميع الحقوق محفوظة.</p>
                            </div>
                        </div>
                    </body></html>";

                    // حفظ الإشعار في قاعدة البيانات
                    try {
                        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, order_id, email, type, subject, message, status, attempts, created_at) VALUES (?, ?, ?, 'status_update', ?, ?, 'pending', 0, NOW())");
                        $notifStmt->execute([
                            (int)$order['user_id'], 
                            $orderId, 
                            $order['customer_email'], 
                            $subject, 
                            $message
                        ]);
                        $notificationId = $pdo->lastInsertId();
                    } catch (Exception $e) {
                        error_log("⚠️ Could not save notification: " . $e->getMessage());
                        $notificationId = null;
                    }

                    // إرسال الإيميل
                    if (sendMail($order['customer_email'], $subject, $message)) {
                        if ($notificationId) {
                            $pdo->prepare("UPDATE notifications SET status='sent', sent_at=NOW() WHERE id=?")->execute([$notificationId]);
                        }
                        $emailSent = true;
                        error_log("✅ Status update email sent to: " . $order['customer_email']);
                    } else {
                        if ($notificationId) {
                            $pdo->prepare("UPDATE notifications SET status='failed', attempts=1, error_message='SMTP sending failed' WHERE id=?")->execute([$notificationId]);
                        }
                        error_log("❌ Failed to send status update email to: " . $order['customer_email']);
                    }
                }
            }
        } catch (Exception $e) {
            error_log("❌ Email error: " . $e->getMessage());
        }
    }

    // الاستجابة
    $responseMessage = 'تم تحديث حالة الطلب بنجاح';
    if ($emailSent) {
        $responseMessage .= ' وتم إرسال إشعار للعميل';
    } elseif (!empty($order['customer_email'])) {
        $responseMessage .= ' لكن فشل إرسال الإشعار للعميل';
    }

    echo json_encode([
        'success' => true,
        'message' => $responseMessage,
        'email_sent' => $emailSent,
        'old_status' => $oldStatus,
        'new_status' => $newStatus
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("❌ Error updating order status: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
