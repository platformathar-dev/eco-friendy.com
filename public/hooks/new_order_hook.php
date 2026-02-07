<?php
/**
 * Hook لإرسال إيميل عند إنشاء طلب جديد
 * يتم إرسال إيميل للعميل وللإدارة
 */

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../mail/mailer.php';

/**
 * دالة إرسال إيميل تأكيد الطلب للعميل
 */
function sendNewOrderEmailToCustomer($orderId, $userId, $email, $username, $orderNumber, $totalAmount, $items = []) {
    global $conn;
    
    if (!$conn) {
        error_log("Database connection failed in sendNewOrderEmailToCustomer");
        return false;
    }
    
    $subject = "تأكيد طلبك #$orderNumber - Eco Friendy 📦";
    
    // بناء قائمة المنتجات
    $itemsHtml = "";
    $itemsTotal = 0;
    if (!empty($items)) {
        foreach ($items as $item) {
            $itemTotal = $item['quantity'] * $item['price'];
            $itemsTotal += $itemTotal;
            $itemsHtml .= "
            <tr>
                <td style='padding: 12px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($item['product_name'] ?? 'منتج') . "</td>
                <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: center;'>" . $item['quantity'] . "</td>
                <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: center;'>" . number_format($item['price'], 2) . " JOD</td>
                <td style='padding: 12px; border-bottom: 1px solid #eee; text-align: center; font-weight: bold;'>" . number_format($itemTotal, 2) . " JOD</td>
            </tr>";
        }
    }
    
    $message = "
    <!DOCTYPE html>
    <html dir='rtl' lang='ar'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5; margin: 0; padding: 0; }
            .container { max-width: 650px; margin: 30px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #4CAF50 0%, #2e7d32 100%); color: white; padding: 40px 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 32px; font-weight: 600; }
            .header p { margin: 10px 0 0 0; opacity: 0.95; font-size: 16px; }
            .content { padding: 40px 30px; }
            .order-box { background: #f0f8f0; border-right: 5px solid #4CAF50; padding: 25px; margin: 25px 0; border-radius: 8px; }
            .order-box h2 { margin: 0 0 15px 0; color: #2e7d32; font-size: 22px; }
            .order-info { display: table; width: 100%; margin: 15px 0; }
            .order-info-row { display: table-row; }
            .order-info-row div { display: table-cell; padding: 8px 0; }
            .order-info-label { font-weight: 600; color: #555; width: 40%; }
            .order-info-value { color: #333; }
            .items-table { width: 100%; border-collapse: collapse; margin: 25px 0; background: #fff; }
            .items-table th { background: #f5f5f5; padding: 15px; text-align: right; font-weight: 600; color: #333; border-bottom: 2px solid #ddd; }
            .items-table td { padding: 12px; color: #555; }
            .total-section { background: #f9f9f9; padding: 20px; margin: 25px 0; border-radius: 8px; text-align: left; }
            .total-row { display: flex; justify-content: space-between; padding: 10px 0; font-size: 18px; }
            .total-row.grand { font-size: 24px; font-weight: bold; color: #2e7d32; border-top: 2px solid #ddd; padding-top: 15px; margin-top: 10px; }
            .status-badge { display: inline-block; background: #fff3cd; color: #856404; padding: 8px 20px; border-radius: 20px; font-size: 14px; font-weight: 600; margin: 10px 0; }
            .button { display: inline-block; background: #4CAF50; color: white !important; padding: 15px 40px; text-decoration: none; border-radius: 8px; margin: 25px 0; font-weight: 600; font-size: 16px; }
            .button:hover { background: #45a049; }
            .footer { background: #f9f9f9; padding: 30px; text-align: center; color: #666; font-size: 14px; border-top: 1px solid #eee; }
            .help-box { background: #e3f2fd; border-right: 4px solid #2196F3; padding: 20px; margin: 25px 0; border-radius: 8px; }
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
                    <h2>تفاصيل الطلب</h2>
                    <div class='order-info'>
                        <div class='order-info-row'>
                            <div class='order-info-label'>رقم الطلب:</div>
                            <div class='order-info-value'><strong style='color: #4CAF50; font-size: 18px;'>#" . htmlspecialchars($orderNumber) . "</strong></div>
                        </div>
                        <div class='order-info-row'>
                            <div class='order-info-label'>اسم العميل:</div>
                            <div class='order-info-value'>" . htmlspecialchars($username) . "</div>
                        </div>
                        <div class='order-info-row'>
                            <div class='order-info-label'>تاريخ الطلب:</div>
                            <div class='order-info-value'>" . date('Y-m-d H:i') . "</div>
                        </div>
                        <div class='order-info-row'>
                            <div class='order-info-label'>الحالة:</div>
                            <div class='order-info-value'><span class='status-badge'>قيد المراجعة</span></div>
                        </div>
                    </div>
                </div>
                
                <h3 style='color: #333; margin: 30px 0 15px 0;'>المنتجات المطلوبة:</h3>
                <table class='items-table'>
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th style='text-align: center;'>الكمية</th>
                            <th style='text-align: center;'>السعر</th>
                            <th style='text-align: center;'>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        $itemsHtml
                    </tbody>
                </table>
                
                <div class='total-section'>
                    <div class='total-row grand'>
                        <span>المجموع الكلي:</span>
                        <span>" . number_format($totalAmount, 2) . " JOD</span>
                    </div>
                </div>
                
                <div class='help-box'>
                    <strong style='color: #1976D2;'>📌 ماذا بعد؟</strong>
                    <ul style='margin: 10px 0; padding-right: 20px; line-height: 2;'>
                        <li>سنقوم بمراجعة طلبك خلال 24 ساعة</li>
                        <li>سيتم التواصل معك لتأكيد التفاصيل</li>
                        <li>يمكنك تتبع حالة الطلب من حسابك</li>
                    </ul>
                </div>
                
                <center>
                    <a href='https://eco-friendy.com/orders' class='button'>تتبع الطلب</a>
                </center>
                
                <p style='margin-top: 30px; color: #666; font-size: 14px; line-height: 1.8;'>
                    إذا كان لديك أي استفسار بخصوص طلبك، لا تتردد في التواصل معنا:<br>
                    📧 البريد الإلكتروني: <a href='mailto:info@eco-friendy.com' style='color: #4CAF50;'>info@eco-friendy.com</a><br>
                    📱 الهاتف: +962 XXX XXX XXX
                </p>
            </div>
            
            <div class='footer'>
                <p style='margin: 0; font-weight: 600;'>شكراً لاختيارك Eco Friendy 🌱</p>
                <p style='margin: 10px 0;'>© 2026 Eco Friendy. جميع الحقوق محفوظة.</p>
                <p style='margin: 10px 0 0 0;'>
                    <a href='https://eco-friendy.com' style='color: #4CAF50; text-decoration: none;'>الموقع الإلكتروني</a> | 
                    <a href='https://eco-friendy.com/privacy' style='color: #4CAF50; text-decoration: none;'>سياسة الخصوصية</a> | 
                    <a href='https://eco-friendy.com/terms' style='color: #4CAF50; text-decoration: none;'>الشروط والأحكام</a>
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // حفظ في قاعدة البيانات
    try {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, order_id, email, type, subject, message, status, attempts, created_at) 
            VALUES (?, ?, ?, 'new_order', ?, ?, 'pending', 0, NOW())
        ");
        
        $stmt->bind_param("iisss", $userId, $orderId, $email, $subject, $message);
        
        if (!$stmt->execute()) {
            error_log("Failed to insert notification: " . $stmt->error);
            return false;
        }
        
        $notificationId = $conn->insert_id;
        $stmt->close();
        
        // إرسال البريد
        if (sendMail($email, $subject, $message)) {
            $updateStmt = $conn->prepare("UPDATE notifications SET status = 'sent', sent_at = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $notificationId);
            $updateStmt->execute();
            $updateStmt->close();
            
            error_log("Order confirmation email sent to: $email");
            return true;
        } else {
            $updateStmt = $conn->prepare("UPDATE notifications SET status = 'failed', attempts = attempts + 1, error_message = 'SMTP send failed' WHERE id = ?");
            $updateStmt->bind_param("i", $notificationId);
            $updateStmt->execute();
            $updateStmt->close();
            
            error_log("Failed to send order email to: $email");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Exception in sendNewOrderEmailToCustomer: " . $e->getMessage());
        return false;
    }
}

/**
 * دالة إرسال إشعار للإدارة عند طلب جديد
 */
function sendNewOrderEmailToAdmin($orderId, $orderNumber, $customerName, $totalAmount) {
    $adminEmail = "info@eco-friendy.com"; // بريد الإدارة
    
    $subject = "🔔 طلب جديد #$orderNumber يحتاج للمراجعة";
    
    $message = "
    <!DOCTYPE html>
    <html dir='rtl' lang='ar'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: #ff9800; color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .alert-box { background: #fff3cd; border-right: 4px solid #ff9800; padding: 20px; margin: 20px 0; border-radius: 5px; }
            .info-table { width: 100%; margin: 20px 0; }
            .info-table td { padding: 10px; border-bottom: 1px solid #eee; }
            .button { display: inline-block; background: #ff9800; color: white !important; padding: 12px 30px; text-decoration: none; border-radius: 5px; }
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
                    <p>تم استلام طلب جديد من العميل ويحتاج إلى مراجعتك.</p>
                </div>
                
                <table class='info-table'>
                    <tr>
                        <td style='font-weight: bold; width: 40%;'>رقم الطلب:</td>
                        <td>#" . htmlspecialchars($orderNumber) . "</td>
                    </tr>
                    <tr>
                        <td style='font-weight: bold;'>اسم العميل:</td>
                        <td>" . htmlspecialchars($customerName) . "</td>
                    </tr>
                    <tr>
                        <td style='font-weight: bold;'>قيمة الطلب:</td>
                        <td style='color: #4CAF50; font-weight: bold;'>" . number_format($totalAmount, 2) . " JOD</td>
                    </tr>
                    <tr>
                        <td style='font-weight: bold;'>التاريخ:</td>
                        <td>" . date('Y-m-d H:i:s') . "</td>
                    </tr>
                </table>
                
                <center>
                    <a href='https://eco-friendy.com/admin/orders?id=$orderId' class='button'>مراجعة الطلب</a>
                </center>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendMail($adminEmail, $subject, $message);
}
?>
