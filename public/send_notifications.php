<?php
/**
 * ===============================================
 * نظام إرسال الإشعارات التلقائية
 * Eco Friendy Store - Notification System
 * ===============================================
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mail/mailer.php';

// ===============================================
// إحصائيات الإرسال
// ===============================================
$stats = [
    'new_orders' => 0,
    'completed_orders' => 0,
    'general' => 0,
    'sent' => 0,
    'failed' => 0,
    'total_processed' => 0
];

$pdo = getDBConnection();

if (!$pdo) {
    die('❌ فشل الاتصال بقاعدة البيانات');
}

echo "<h1>🔔 نظام إرسال الإشعارات - Eco Friendy</h1>";
echo "<p>بدء المعالجة في: " . date('Y-m-d H:i:s') . "</p>";
echo "<hr>";

// ===============================================
// 1. إشعارات الطلبات الجديدة
// ===============================================
echo "<h2>📦 معالجة الطلبات الجديدة...</h2>";

try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.email as user_email, u.fullname 
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.status = 'pending' 
        AND o.notification_sent = 0
        LIMIT 50
    ");
    $stmt->execute();
    $newOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($newOrders as $order) {
        $customerEmail = $order['user_email'] ?? $order['customer_email'] ?? null;
        
        if (!$customerEmail || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            echo "⚠️ الطلب #{$order['id']}: بريد إلكتروني غير صالح<br>";
            continue;
        }
        
        $subject = "تأكيد طلبك رقم #{$order['id']} - Eco Friendy 🐾";
        
        $message = getOrderConfirmationTemplate([
            'order_id' => $order['id'],
            'customer_name' => $order['customer_name'],
            'total_amount' => $order['total_amount'],
            'order_date' => $order['created_at']
        ]);
        
        // إضافة الإشعار إلى قاعدة البيانات
        $insertStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, order_id, email, type, subject, message, status)
            VALUES (?, ?, ?, 'new_order', ?, ?, 'pending')
        ");
        
        $insertStmt->execute([
            $order['user_id'],
            $order['id'],
            $customerEmail,
            $subject,
            $message
        ]);
        
        $notificationId = $pdo->lastInsertId();
        
        // محاولة الإرسال
        if (sendMail($customerEmail, $subject, $message)) {
            // تحديث حالة الإشعار
            $updateNotif = $pdo->prepare("
                UPDATE notifications 
                SET status = 'sent', sent_at = NOW(), attempts = attempts + 1
                WHERE id = ?
            ");
            $updateNotif->execute([$notificationId]);
            
            // تحديث حالة الطلب
            $updateOrder = $pdo->prepare("
                UPDATE orders 
                SET notification_sent = 1
                WHERE id = ?
            ");
            $updateOrder->execute([$order['id']]);
            
            echo "✅ تم إرسال إشعار الطلب #{$order['id']} إلى: {$customerEmail}<br>";
            $stats['sent']++;
        } else {
            // تحديث حالة الإشعار كفاشل
            $updateNotif = $pdo->prepare("
                UPDATE notifications 
                SET status = 'failed', attempts = attempts + 1, error_message = 'فشل في الإرسال عبر SMTP'
                WHERE id = ?
            ");
            $updateNotif->execute([$notificationId]);
            
            echo "❌ فشل إرسال إشعار الطلب #{$order['id']} إلى: {$customerEmail}<br>";
            $stats['failed']++;
        }
        
        $stats['new_orders']++;
        $stats['total_processed']++;
    }
    
    echo "<p><strong>إجمالي الطلبات الجديدة المعالجة: {$stats['new_orders']}</strong></p>";
    
} catch (Exception $e) {
    echo "❌ خطأ في معالجة الطلبات الجديدة: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// ===============================================
// 2. إشعارات الطلبات المكتملة
// ===============================================
echo "<h2>✅ معالجة الطلبات المكتملة...</h2>";

try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.email as user_email, u.fullname 
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.status = 'completed' 
        AND (o.completion_notified = 0 OR o.completion_notified IS NULL)
        LIMIT 50
    ");
    $stmt->execute();
    $completedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($completedOrders as $order) {
        $customerEmail = $order['user_email'] ?? $order['customer_email'] ?? null;
        
        if (!$customerEmail || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            echo "⚠️ الطلب #{$order['id']}: بريد إلكتروني غير صالح<br>";
            continue;
        }
        
        $subject = "تم إتمام طلبك رقم #{$order['id']} - Eco Friendy 🎉";
        
        $message = getOrderCompletedTemplate([
            'order_id' => $order['id'],
            'customer_name' => $order['customer_name']
        ]);
        
        // إضافة الإشعار
        $insertStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, order_id, email, type, subject, message, status)
            VALUES (?, ?, ?, 'completed', ?, ?, 'pending')
        ");
        
        $insertStmt->execute([
            $order['user_id'],
            $order['id'],
            $customerEmail,
            $subject,
            $message
        ]);
        
        $notificationId = $pdo->lastInsertId();
        
        // محاولة الإرسال
        if (sendMail($customerEmail, $subject, $message)) {
            $updateNotif = $pdo->prepare("
                UPDATE notifications 
                SET status = 'sent', sent_at = NOW(), attempts = attempts + 1
                WHERE id = ?
            ");
            $updateNotif->execute([$notificationId]);
            
            $updateOrder = $pdo->prepare("
                UPDATE orders 
                SET completion_notified = 1
                WHERE id = ?
            ");
            $updateOrder->execute([$order['id']]);
            
            echo "✅ تم إرسال إشعار إتمام الطلب #{$order['id']} إلى: {$customerEmail}<br>";
            $stats['sent']++;
        } else {
            $updateNotif = $pdo->prepare("
                UPDATE notifications 
                SET status = 'failed', attempts = attempts + 1, error_message = 'فشل في الإرسال عبر SMTP'
                WHERE id = ?
            ");
            $updateNotif->execute([$notificationId]);
            
            echo "❌ فشل إرسال إشعار إتمام الطلب #{$order['id']} إلى: {$customerEmail}<br>";
            $stats['failed']++;
        }
        
        $stats['completed_orders']++;
        $stats['total_processed']++;
    }
    
    echo "<p><strong>إجمالي الطلبات المكتملة المعالجة: {$stats['completed_orders']}</strong></p>";
    
} catch (Exception $e) {
    echo "❌ خطأ في معالجة الطلبات المكتملة: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// ===============================================
// 3. إعادة محاولة إرسال الإشعارات الفاشلة
// ===============================================
echo "<h2>🔄 إعادة محاولة الإشعارات الفاشلة...</h2>";

try {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE status = 'failed' 
        AND attempts < 3
        ORDER BY created_at ASC
        LIMIT 20
    ");
    $stmt->execute();
    $failedNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($failedNotifications as $notif) {
        if (sendMail($notif['email'], $notif['subject'], $notif['message'])) {
            $updateStmt = $pdo->prepare("
                UPDATE notifications 
                SET status = 'sent', sent_at = NOW(), attempts = attempts + 1, error_message = NULL
                WHERE id = ?
            ");
            $updateStmt->execute([$notif['id']]);
            
            echo "✅ نجحت إعادة الإرسال للإشعار #{$notif['id']} إلى: {$notif['email']}<br>";
            $stats['sent']++;
        } else {
            $updateStmt = $pdo->prepare("
                UPDATE notifications 
                SET attempts = attempts + 1, error_message = 'فشلت المحاولة رقم ' || (attempts + 1)
                WHERE id = ?
            ");
            $updateStmt->execute([$notif['id']]);
            
            echo "❌ فشلت إعادة الإرسال للإشعار #{$notif['id']} إلى: {$notif['email']}<br>";
            $stats['failed']++;
        }
        
        $stats['total_processed']++;
    }
    
} catch (Exception $e) {
    echo "❌ خطأ في إعادة المحاولة: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// ===============================================
// 4. الإحصائيات النهائية
// ===============================================
echo "<h2>📊 الإحصائيات النهائية</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>البند</th><th>العدد</th></tr>";
echo "<tr><td>إشعارات الطلبات الجديدة</td><td>{$stats['new_orders']}</td></tr>";
echo "<tr><td>إشعارات الطلبات المكتملة</td><td>{$stats['completed_orders']}</td></tr>";
echo "<tr><td>إجمالي الإشعارات المرسلة بنجاح</td><td style='color: green; font-weight: bold;'>{$stats['sent']}</td></tr>";
echo "<tr><td>إجمالي الإشعارات الفاشلة</td><td style='color: red; font-weight: bold;'>{$stats['failed']}</td></tr>";
echo "<tr><td>إجمالي المعالجة</td><td style='font-weight: bold;'>{$stats['total_processed']}</td></tr>";
echo "</table>";

echo "<p>انتهت المعالجة في: " . date('Y-m-d H:i:s') . "</p>";

// ===============================================
// قوالب البريد الإلكتروني
// ===============================================

function getOrderConfirmationTemplate($data) {
    return '
    <!DOCTYPE html>
    <html dir="rtl" lang="ar">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #f39200, #e68500); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { padding: 30px; }
            .order-info { background: #fff3e6; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .order-info h2 { color: #c77400; margin-top: 0; }
            .info-row { display: flex; justify-content: space-between; margin: 10px 0; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
            .info-label { font-weight: bold; color: #666; }
            .info-value { color: #333; }
            .button { display: inline-block; background: #f39200; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { background: #f9f9f9; padding: 20px; text-align: center; color: #666; font-size: 14px; }
            .footer p { margin: 5px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🐾 Eco Friendy</h1>
                <p style="margin: 10px 0 0 0; font-size: 16px;">متجر مستلزمات الحيوانات الأليفة</p>
            </div>
            
            <div class="content">
                <h2 style="color: #c77400;">شكراً لك ' . htmlspecialchars($data['customer_name']) . '! 🎉</h2>
                <p>تم استلام طلبك بنجاح ونحن نعمل على معالجته.</p>
                
                <div class="order-info">
                    <h2>تفاصيل الطلب</h2>
                    <div class="info-row">
                        <span class="info-label">رقم الطلب:</span>
                        <span class="info-value">#' . $data['order_id'] . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">المبلغ الإجمالي:</span>
                        <span class="info-value" style="color: #f39200; font-weight: bold; font-size: 18px;">' . number_format($data['total_amount'], 2) . ' د.أ</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">تاريخ الطلب:</span>
                        <span class="info-value">' . date('Y-m-d H:i', strtotime($data['order_date'])) . '</span>
                    </div>
                </div>
                
                <p>سنتواصل معك قريباً عبر الهاتف لتأكيد الطلب وترتيب موعد التوصيل.</p>
                
                <p style="margin-top: 30px;">إذا كانت لديك أي استفسارات، لا تتردد في الاتصال بنا:</p>
                <p>📞 <strong>+962790083039</strong></p>
            </div>
            
            <div class="footer">
                <p><strong>Eco Friendy Store</strong></p>
                <p>الأردن - عمان | info@eco-friendy.com</p>
                <p>شكراً لثقتك بنا ❤️🐾</p>
            </div>
        </div>
    </body>
    </html>
    ';
}

function getOrderCompletedTemplate($data) {
    return '
    <!DOCTYPE html>
    <html dir="rtl" lang="ar">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { padding: 30px; text-align: center; }
            .success-icon { font-size: 64px; margin: 20px 0; }
            .footer { background: #f9f9f9; padding: 20px; text-align: center; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🐾 Eco Friendy</h1>
                <p style="margin: 10px 0 0 0;">تم إتمام طلبك بنجاح!</p>
            </div>
            
            <div class="content">
                <div class="success-icon">🎉</div>
                <h2 style="color: #10b981;">تهانينا ' . htmlspecialchars($data['customer_name']) . '!</h2>
                <p style="font-size: 16px;">تم إتمام طلبك رقم <strong>#' . $data['order_id'] . '</strong> وتسليمه بنجاح.</p>
                <p>نتمنى أن تكون راضياً عن منتجاتنا وخدماتنا!</p>
                <p style="margin-top: 30px;">لا تتردد في زيارتنا مرة أخرى 🐾</p>
            </div>
            
            <div class="footer">
                <p><strong>Eco Friendy Store</strong></p>
                <p>الأردن - عمان | +962790083039</p>
                <p>شكراً لثقتك بنا ❤️🐾</p>
            </div>
        </div>
    </body>
    </html>
    ';
}
