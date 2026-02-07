<?php
// send_notifications_advanced.php - نظام الإشعارات المتقدم
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mail/mailer.php';

// ==============================
// دالة مساعدة لتسجيل الأحداث
// ==============================
function logEvent($message, $type = 'info') {
    $logFile = __DIR__ . '/logs/notifications_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$type}] {$message}\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    if ($type === 'error') {
        echo "<span style='color:red'>❌ {$message}</span><br>";
    } else {
        echo "<span style='color:green'>✅ {$message}</span><br>";
    }
}

// ==============================
// 1. إشعارات الطلبات الجديدة
// ==============================
function processNewOrders($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, u.email as user_email, u.fullname 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.status = 'pending' 
            AND o.notification_sent IS NULL
            LIMIT 50
        ");
        $stmt->execute();
        $newOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count = 0;
        foreach ($newOrders as $order) {
            $subject = "تأكيد طلبك رقم #{$order['id']} - Eco Friendy";
            
            $message = getOrderConfirmationTemplate($order);
            
            // البريد الإلكتروني للعميل
            $customerEmail = $order['user_email'] ?? $order['customer_email'] ?? null;
            
            if ($customerEmail) {
                // تسجيل الإشعار للعميل
                $insertStmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, order_id, email, type, subject, message)
                    VALUES (?, ?, ?, 'new_order', ?, ?)
                ");
                $insertStmt->execute([
                    $order['user_id'],
                    $order['id'],
                    $customerEmail,
                    $subject,
                    $message
                ]);
                
                $count++;
            }
            
            // إشعار الأدمن
            $adminSubject = "طلب جديد #{$order['id']} من {$order['customer_name']}";
            $adminMessage = getAdminNewOrderTemplate($order);
            
            $adminInsert = $pdo->prepare("
                INSERT INTO notifications (order_id, email, type, subject, message)
                VALUES (?, 'admin@eco-friendy.com', 'new_order', ?, ?)
            ");
            $adminInsert->execute([$order['id'], $adminSubject, $adminMessage]);
            
            // تحديث حالة الطلب
            $updateStmt = $pdo->prepare("
                UPDATE orders 
                SET notification_sent = NOW() 
                WHERE id = ?
            ");
            $updateStmt->execute([$order['id']]);
        }
        
        if ($count > 0) {
            logEvent("تم إنشاء {$count} إشعار طلب جديد");
        }
        
        return $count;
        
    } catch (Exception $e) {
        logEvent("خطأ في معالجة الطلبات الجديدة: " . $e->getMessage(), 'error');
        return 0;
    }
}

// ==============================
// 2. إشعارات الطلبات المكتملة
// ==============================
function processCompletedOrders($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, u.email as user_email, u.fullname 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.status = 'completed' 
            AND o.completion_notified IS NULL
            LIMIT 50
        ");
        $stmt->execute();
        $completedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $count = 0;
        foreach ($completedOrders as $order) {
            $subject = "تم إتمام طلبك رقم #{$order['id']} - Eco Friendy";
            $message = getOrderCompletedTemplate($order);
            
            $customerEmail = $order['user_email'] ?? $order['customer_email'] ?? null;
            
            if ($customerEmail) {
                $insertStmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, order_id, email, type, subject, message)
                    VALUES (?, ?, ?, 'completed', ?, ?)
                ");
                $insertStmt->execute([
                    $order['user_id'],
                    $order['id'],
                    $customerEmail,
                    $subject,
                    $message
                ]);
                
                $count++;
            }
            
            // تحديث حالة الإشعار
            $updateStmt = $pdo->prepare("
                UPDATE orders 
                SET completion_notified = NOW() 
                WHERE id = ?
            ");
            $updateStmt->execute([$order['id']]);
        }
        
        if ($count > 0) {
            logEvent("تم إنشاء {$count} إشعار طلب مكتمل");
        }
        
        return $count;
        
    } catch (Exception $e) {
        logEvent("خطأ في معالجة الطلبات المكتملة: " . $e->getMessage(), 'error');
        return 0;
    }
}

// ==============================
// 3. إرسال الإشعارات المعلقة
// ==============================
function sendPendingNotifications($pdo, $limit = 20) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE status = 'pending' 
            AND attempts < 3
            ORDER BY created_at ASC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'total' => count($notifications),
            'sent' => 0,
            'failed' => 0
        ];
        
        foreach ($notifications as $notif) {
            $success = sendMail($notif['email'], $notif['subject'], $notif['message']);
            
            if ($success) {
                $updateStmt = $pdo->prepare("
                    UPDATE notifications 
                    SET status = 'sent', 
                        sent_at = NOW(), 
                        attempts = attempts + 1 
                    WHERE id = ?
                ");
                $updateStmt->execute([$notif['id']]);
                
                $stats['sent']++;
                logEvent("تم إرسال: {$notif['email']} - {$notif['subject']}");
                
            } else {
                $updateStmt = $pdo->prepare("
                    UPDATE notifications 
                    SET status = 'failed', 
                        attempts = attempts + 1, 
                        error_message = 'فشل الإرسال عبر SMTP'
                    WHERE id = ?
                ");
                $updateStmt->execute([$notif['id']]);
                
                $stats['failed']++;
                logEvent("فشل إرسال: {$notif['email']} - {$notif['subject']}", 'error');
            }
            
            // تأخير صغير لتجنب حظر SMTP
            usleep(500000); // 0.5 ثانية
        }
        
        return $stats;
        
    } catch (Exception $e) {
        logEvent("خطأ في إرسال الإشعارات: " . $e->getMessage(), 'error');
        return ['total' => 0, 'sent' => 0, 'failed' => 0];
    }
}

// ==============================
// 4. إعادة محاولة الإشعارات الفاشلة
// ==============================
function retryFailedNotifications($pdo, $maxAttempts = 3) {
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET status = 'pending' 
            WHERE status = 'failed' 
            AND attempts < ?
        ");
        $stmt->execute([$maxAttempts]);
        
        $count = $stmt->rowCount();
        
        if ($count > 0) {
            logEvent("تم إعادة {$count} إشعار فاشل إلى حالة الانتظار");
        }
        
        return $count;
        
    } catch (Exception $e) {
        logEvent("خطأ في إعادة المحاولة: " . $e->getMessage(), 'error');
        return 0;
    }
}

// ==============================
// القوالب
// ==============================
function getOrderConfirmationTemplate($order) {
    return '
    <!DOCTYPE html>
    <html dir="rtl" lang="ar">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #f39200, #e68500); color: white; padding: 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { padding: 30px; }
            .order-info { background: #fffbf7; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .order-info p { margin: 10px 0; }
            .order-info strong { color: #f39200; }
            .footer { background: #f9f9f9; padding: 20px; text-align: center; color: #666; font-size: 14px; }
            .button { display: inline-block; background: #f39200; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🐾 Eco Friendy</h1>
                <p>شكراً لطلبك معنا!</p>
            </div>
            <div class="content">
                <h2>مرحباً ' . htmlspecialchars($order['customer_name']) . ' 👋</h2>
                <p>تم استلام طلبك بنجاح وسيتم معالجته في أقرب وقت.</p>
                
                <div class="order-info">
                    <p><strong>رقم الطلب:</strong> #' . $order['id'] . '</p>
                    <p><strong>تاريخ الطلب:</strong> ' . date('Y-m-d H:i', strtotime($order['created_at'])) . '</p>
                    <p><strong>المبلغ الإجمالي:</strong> ' . number_format($order['total_amount'], 2) . ' د.أ</p>
                    <p><strong>العنوان:</strong> ' . htmlspecialchars($order['customer_address']) . '</p>
                    <p><strong>الهاتف:</strong> ' . htmlspecialchars($order['customer_phone']) . '</p>
                </div>
                
                <p>سنتواصل معك قريباً لتأكيد التوصيل. 📞</p>
                <p>إذا كان لديك أي استفسار، لا تتردد في التواصل معنا.</p>
            </div>
            <div class="footer">
                <p><strong>Eco Friendy Store</strong></p>
                <p>متجرك المفضل لمستلزمات الحيوانات الأليفة</p>
                <p>الأردن - عمان | +962790083039</p>
                <p>info@eco-friendy.com</p>
            </div>
        </div>
    </body>
    </html>
    ';
}

function getAdminNewOrderTemplate($order) {
    return '
    <!DOCTYPE html>
    <html dir="rtl" lang="ar">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; }
            .header { background: #ef4444; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; }
            .alert { background: #fee2e2; border-right: 4px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 4px; }
            .order-details { background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .order-details p { margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>⚠️ طلب جديد يحتاج للمعالجة</h1>
            </div>
            <div class="content">
                <div class="alert">
                    <strong>طلب جديد رقم #' . $order['id'] . '</strong>
                </div>
                
                <div class="order-details">
                    <p><strong>العميل:</strong> ' . htmlspecialchars($order['customer_name']) . '</p>
                    <p><strong>الهاتف:</strong> ' . htmlspecialchars($order['customer_phone']) . '</p>
                    <p><strong>العنوان:</strong> ' . htmlspecialchars($order['customer_address']) . '</p>
                    <p><strong>المبلغ:</strong> ' . number_format($order['total_amount'], 2) . ' د.أ</p>
                    <p><strong>التاريخ:</strong> ' . date('Y-m-d H:i', strtotime($order['created_at'])) . '</p>
                </div>
                
                <p>يرجى معالجة هذا الطلب في أقرب وقت ممكن.</p>
            </div>
        </div>
    </body>
    </html>
    ';
}

function getOrderCompletedTemplate($order) {
    return '
    <!DOCTYPE html>
    <html dir="rtl" lang="ar">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; }
            .header { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; text-align: center; }
            .success-icon { font-size: 64px; margin: 20px 0; }
            .footer { background: #f9f9f9; padding: 20px; text-align: center; color: #666; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🎉 تم إتمام طلبك!</h1>
            </div>
            <div class="content">
                <div class="success-icon">✅</div>
                <h2>عزيزي ' . htmlspecialchars($order['customer_name']) . '</h2>
                <p>تم إتمام طلبك رقم <strong>#' . $order['id'] . '</strong> بنجاح!</p>
                <p>شكراً لاختيارك Eco Friendy. نتمنى أن تستمتع بمشترياتك! 🐾</p>
                <p>نسعد دائماً بخدمتك.</p>
            </div>
            <div class="footer">
                <p>Eco Friendy Store</p>
                <p>+962790083039 | info@eco-friendy.com</p>
            </div>
        </div>
    </body>
    </html>
    ';
}

// ==============================
// التنفيذ الرئيسي
// ==============================
echo '<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>نظام الإشعارات - Eco Friendy</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #f39200; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0; }
        .stat-card { background: #fffbf7; padding: 20px; border-radius: 8px; border-right: 4px solid #f39200; }
        .stat-value { font-size: 32px; font-weight: bold; color: #f39200; }
        .stat-label { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔔 نظام الإشعارات - Eco Friendy</h1>
        <p>تاريخ التشغيل: ' . date('Y-m-d H:i:s') . '</p>
        <hr>
';

try {
    $pdo = getDBConnection();
    
    echo '<h2>📊 معالجة الإشعارات...</h2>';
    
    // 1. معالجة الطلبات الجديدة
    echo '<h3>1. الطلبات الجديدة</h3>';
    $newOrdersCount = processNewOrders($pdo);
    
    // 2. معالجة الطلبات المكتملة
    echo '<h3>2. الطلبات المكتملة</h3>';
    $completedCount = processCompletedOrders($pdo);
    
    // 3. إعادة محاولة الفاشلة
    echo '<h3>3. إعادة المحاولة</h3>';
    $retriedCount = retryFailedNotifications($pdo);
    
    // 4. إرسال الإشعارات المعلقة
    echo '<h3>4. إرسال الإشعارات</h3>';
    $sendStats = sendPendingNotifications($pdo, 20);
    
    // الإحصائيات النهائية
    $statsStmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
        FROM notifications
    ");
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo '
        <hr>
        <h2>📈 الإحصائيات الإجمالية</h2>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value">' . $stats['total'] . '</div>
                <div class="stat-label">إجمالي الإشعارات</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #10b981;">' . $stats['sent'] . '</div>
                <div class="stat-label">تم الإرسال</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #f59e0b;">' . $stats['pending'] . '</div>
                <div class="stat-label">قيد الانتظار</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ef4444;">' . $stats['failed'] . '</div>
                <div class="stat-label">فشل</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">' . $sendStats['sent'] . '</div>
                <div class="stat-label">تم إرساله الآن</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">' . $sendStats['failed'] . '</div>
                <div class="stat-label">فشل الآن</div>
            </div>
        </div>
    ';
    
    logEvent("✅ اكتملت المعالجة بنجاح!");
    
} catch (Exception $e) {
    echo '<div style="background: #fee2e2; padding: 20px; border-radius: 8px; color: #991b1b;">';
    echo '<strong>❌ خطأ:</strong> ' . $e->getMessage();
    echo '</div>';
    logEvent("خطأ عام: " . $e->getMessage(), 'error');
}

echo '
    </div>
</body>
</html>
';
