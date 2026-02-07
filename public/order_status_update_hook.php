<?php
/**
 * Hook لإرسال إيميل عند تحديث حالة الطلب
 */

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../mail/mailer.php';

/**
 * دالة إرسال إيميل عند تحديث حالة الطلب
 */
function sendOrderStatusUpdateEmail($orderId, $userId, $email, $username, $orderNumber, $oldStatus, $newStatus) {
    global $conn;
    
    if (!$conn) {
        error_log("Database connection failed in sendOrderStatusUpdateEmail");
        return false;
    }
    
    // ترجمة الحالات
    $statusTranslations = [
        'pending' => 'قيد المراجعة',
        'confirmed' => 'تم التأكيد',
        'processing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغي',
        'completed' => 'مكتمل'
    ];
    
    $statusArabic = $statusTranslations[$newStatus] ?? $newStatus;
    
    // تحديد الأيقونة واللون حسب الحالة
    $statusEmoji = [
        'pending' => '⏳',
        'confirmed' => '✅',
        'processing' => '📦',
        'shipped' => '🚚',
        'delivered' => '✅',
        'cancelled' => '❌',
        'completed' => '🎉'
    ];
    
    $statusColors = [
        'pending' => '#ff9800',
        'confirmed' => '#4CAF50',
        'processing' => '#2196F3',
        'shipped' => '#9C27B0',
        'delivered' => '#4CAF50',
        'cancelled' => '#f44336',
        'completed' => '#4CAF50'
    ];
    
    $emoji = $statusEmoji[$newStatus] ?? '📋';
    $color = $statusColors[$newStatus] ?? '#4CAF50';
    
    $subject = "$emoji تحديث حالة طلبك #$orderNumber";
    
    // رسالة مخصصة لكل حالة
    $statusMessages = [
        'confirmed' => 'تم تأكيد طلبك وسنبدأ في تجهيزه قريباً.',
        'processing' => 'طلبك الآن قيد التجهيز. سنقوم بشحنه في أقرب وقت.',
        'shipped' => 'تم شحن طلبك! سيصلك خلال 2-3 أيام عمل.',
        'delivered' => 'تم توصيل طلبك بنجاح! نتمنى أن تكون راضياً عن المنتجات.',
        'cancelled' => 'تم إلغاء طلبك. إذا كان لديك أي استفسار، يرجى التواصل معنا.',
        'completed' => 'طلبك مكتمل! شكراً لثقتك في Eco Friendy.'
    ];
    
    $statusMessage = $statusMessages[$newStatus] ?? 'تم تحديث حالة طلبك.';
    
    $message = "
    <!DOCTYPE html>
    <html dir='rtl' lang='ar'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 30px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, $color 0%, " . adjustColor($color, -20) . " 100%); color: white; padding: 40px 30px; text-align: center; }
            .header h1 { margin: 0; font-size: 32px; font-weight: 600; }
            .content { padding: 40px 30px; }
            .status-box { background: linear-gradient(135deg, " . adjustColor($color, 90) . " 0%, " . adjustColor($color, 95) . " 100%); border-right: 5px solid $color; padding: 30px; margin: 25px 0; border-radius: 10px; text-align: center; }
            .status-box h2 { margin: 0 0 15px 0; color: $color; font-size: 28px; }
            .status-box p { margin: 0; font-size: 16px; color: #555; line-height: 1.8; }
            .order-info { background: #f9f9f9; padding: 25px; border-radius: 8px; margin: 25px 0; }
            .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eee; }
            .info-row:last-child { border-bottom: none; }
            .info-label { font-weight: 600; color: #555; }
            .info-value { color: #333; font-weight: 500; }
            .timeline { margin: 30px 0; padding: 20px; background: #f5f5f5; border-radius: 8px; }
            .timeline-item { display: flex; align-items: center; margin: 15px 0; }
            .timeline-dot { width: 20px; height: 20px; border-radius: 50%; margin-left: 15px; }
            .timeline-dot.active { background: $color; box-shadow: 0 0 0 4px " . adjustColor($color, 80) . "; }
            .timeline-dot.inactive { background: #ddd; }
            .timeline-text { flex: 1; color: #555; }
            .timeline-text.active { font-weight: 600; color: $color; }
            .button { display: inline-block; background: $color; color: white !important; padding: 15px 40px; text-decoration: none; border-radius: 8px; margin: 25px 0; font-weight: 600; }
            .footer { background: #f9f9f9; padding: 30px; text-align: center; color: #666; font-size: 14px; border-top: 1px solid #eee; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>$emoji تحديث حالة الطلب</h1>
            </div>
            
            <div class='content'>
                <div class='status-box'>
                    <h2>$statusArabic</h2>
                    <p>$statusMessage</p>
                </div>
                
                <div class='order-info'>
                    <div class='info-row'>
                        <span class='info-label'>رقم الطلب:</span>
                        <span class='info-value'><strong>#" . htmlspecialchars($orderNumber) . "</strong></span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>اسم العميل:</span>
                        <span class='info-value'>" . htmlspecialchars($username) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>الحالة السابقة:</span>
                        <span class='info-value'>" . ($statusTranslations[$oldStatus] ?? $oldStatus) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>الحالة الحالية:</span>
                        <span class='info-value' style='color: $color; font-weight: bold;'>$statusArabic</span>
                    </div>
                </div>
                
                <div class='timeline'>
                    <h3 style='margin-top: 0; color: #333;'>تتبع الطلب:</h3>
                    " . generateTimeline($newStatus, $statusColors) . "
                </div>
                
                <center>
                    <a href='https://eco-friendy.com/orders?id=$orderId' class='button'>عرض تفاصيل الطلب</a>
                </center>
                
                <p style='margin-top: 30px; color: #666; font-size: 14px; line-height: 1.8;'>
                    إذا كان لديك أي استفسار، لا تتردد في التواصل معنا:<br>
                    📧 <a href='mailto:info@eco-friendy.com' style='color: $color;'>info@eco-friendy.com</a>
                </p>
            </div>
            
            <div class='footer'>
                <p style='margin: 0;'>© 2026 Eco Friendy. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // حفظ في قاعدة البيانات
    try {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, order_id, email, type, subject, message, status, attempts, created_at) 
            VALUES (?, ?, ?, 'completed', ?, ?, 'pending', 0, NOW())
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
            
            error_log("Status update email sent to: $email");
            return true;
        } else {
            $updateStmt = $conn->prepare("UPDATE notifications SET status = 'failed', attempts = attempts + 1, error_message = 'SMTP send failed' WHERE id = ?");
            $updateStmt->bind_param("i", $notificationId);
            $updateStmt->execute();
            $updateStmt->close();
            
            error_log("Failed to send status update email to: $email");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Exception in sendOrderStatusUpdateEmail: " . $e->getMessage());
        return false;
    }
}

/**
 * دالة مساعدة لتعديل درجة اللون
 */
function adjustColor($color, $percent) {
    $color = str_replace('#', '', $color);
    $r = hexdec(substr($color, 0, 2));
    $g = hexdec(substr($color, 2, 2));
    $b = hexdec(substr($color, 4, 2));
    
    $r = max(0, min(255, $r + ($r * $percent / 100)));
    $g = max(0, min(255, $g + ($g * $percent / 100)));
    $b = max(0, min(255, $b + ($b * $percent / 100)));
    
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

/**
 * دالة توليد خط زمني للطلب
 */
function generateTimeline($currentStatus, $statusColors) {
    $statuses = [
        'pending' => 'قيد المراجعة',
        'confirmed' => 'تم التأكيد',
        'processing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التوصيل'
    ];
    
    $statusOrder = array_keys($statuses);
    $currentIndex = array_search($currentStatus, $statusOrder);
    
    $html = '';
    foreach ($statuses as $status => $label) {
        $index = array_search($status, $statusOrder);
        $isActive = $index <= $currentIndex;
        $activeClass = $isActive ? 'active' : 'inactive';
        $color = $statusColors[$status] ?? '#ddd';
        
        $html .= "
        <div class='timeline-item'>
            <div class='timeline-dot $activeClass' style='" . ($isActive ? "background: $color; box-shadow: 0 0 0 4px " . adjustColor($color, 80) . ";" : "") . "'></div>
            <div class='timeline-text $activeClass' style='" . ($isActive ? "color: $color; font-weight: 600;" : "") . "'>$label</div>
        </div>";
    }
    
    return $html;
}
?>
