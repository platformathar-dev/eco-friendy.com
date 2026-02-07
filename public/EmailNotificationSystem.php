<?php
/**
 * نظام إرسال الإيميلات التلقائية
 * Email Notification System
 */

// تحديد المسار الصحيح لملف mailer.php
// غيّر المسار حسب موقع ملف mailer.php في مشروعك
$mailerPath = __DIR__ . '/mail/mailer.php';
if (!file_exists($mailerPath)) {
    // جرب مسار آخر
    $mailerPath = dirname(__DIR__) . '/mail/mailer.php';
}
if (file_exists($mailerPath)) {
    require_once $mailerPath;
} else {
    die("Error: mailer.php not found. Please check the path.");
}

class EmailNotificationSystem {
    
    private $conn;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * إنشاء إشعار جديد في قاعدة البيانات
     */
    private function createNotification($email, $type, $subject, $message, $userId = null, $orderId = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications 
            (user_id, order_id, email, type, subject, message, status, attempts, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', 0, NOW())
        ");
        
        $stmt->bind_param("iissss", $userId, $orderId, $email, $type, $subject, $message);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * تحديث حالة الإشعار
     */
    private function updateNotificationStatus($notificationId, $status, $errorMessage = null) {
        if ($status === 'sent') {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET status = ?, sent_at = NOW(), attempts = attempts + 1 
                WHERE id = ?
            ");
            $stmt->bind_param("si", $status, $notificationId);
        } else {
            $stmt = $this->conn->prepare("
                UPDATE notifications 
                SET status = ?, error_message = ?, attempts = attempts + 1 
                WHERE id = ?
            ");
            $stmt->bind_param("ssi", $status, $errorMessage, $notificationId);
        }
        
        return $stmt->execute();
    }
    
    /**
     * إرسال إيميل ترحيب بعد إنشاء حساب جديد
     */
    public function sendWelcomeEmail($userId, $fullname, $email) {
        $subject = "مرحباً بك في Eco-Friendly! 🌿";
        
        $message = "
        <div dir='rtl' style='font-family: Arial, sans-serif; padding: 20px; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px;'>
                <h1 style='color: #2ecc71; text-align: center;'>مرحباً بك يا {$fullname}! 🎉</h1>
                
                <p style='font-size: 16px; line-height: 1.8; color: #333;'>
                    شكراً لانضمامك إلى Eco-Friendly، منصتك الموثوقة للمنتجات الصديقة للبيئة.
                </p>
                
                <div style='background-color: #e8f8f5; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #2ecc71; margin-top: 0;'>ماذا بعد؟</h3>
                    <ul style='line-height: 2;'>
                        <li>استكشف منتجاتنا الصديقة للبيئة</li>
                        <li>احصل على عروض خاصة للأعضاء الجدد</li>
                        <li>تتبع طلباتك بسهولة</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='https://eco-friendy.com' style='display: inline-block; padding: 15px 40px; background-color: #2ecc71; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                        ابدأ التسوق الآن
                    </a>
                </div>
                
                <p style='text-align: center; color: #7f8c8d; margin-top: 30px; font-size: 14px;'>
                    إذا كان لديك أي استفسار، لا تتردد في التواصل معنا
                </p>
            </div>
        </div>
        ";
        
        $notificationId = $this->createNotification($email, 'general', $subject, $message, $userId);
        
        if ($notificationId && sendMail($email, $subject, $message)) {
            $this->updateNotificationStatus($notificationId, 'sent');
            return true;
        } else {
            $this->updateNotificationStatus($notificationId, 'failed', 'فشل في إرسال البريد');
            return false;
        }
    }
    
    /**
     * إرسال إيميل تأكيد طلب جديد
     */
    public function sendNewOrderEmail($orderId, $customerEmail, $customerName, $orderNumber, $totalAmount, $products = '') {
        $subject = "تأكيد طلبك #{$orderNumber} 📦";
        
        $message = "
        <div dir='rtl' style='font-family: Arial, sans-serif; padding: 20px; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px;'>
                <h1 style='color: #3498db; text-align: center;'>شكراً لطلبك! 🛍️</h1>
                
                <p style='font-size: 16px; color: #333;'>
                    عزيزي/عزيزتي <strong>{$customerName}</strong>،
                </p>
                
                <p style='font-size: 16px; line-height: 1.8; color: #333;'>
                    تم استلام طلبك بنجاح وسيتم معالجته في أقرب وقت ممكن.
                </p>
                
                <div style='background-color: #ebf5fb; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='color: #3498db; margin-top: 0;'>تفاصيل الطلب</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #d5dbdb;'><strong>رقم الطلب:</strong></td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #d5dbdb; text-align: left;'>#{$orderNumber}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0; border-bottom: 1px solid #d5dbdb;'><strong>المبلغ الإجمالي:</strong></td>
                            <td style='padding: 10px 0; border-bottom: 1px solid #d5dbdb; text-align: left;'>{$totalAmount} دينار</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px 0;'><strong>حالة الطلب:</strong></td>
                            <td style='padding: 10px 0; text-align: left;'><span style='color: #f39c12;'>قيد المعالجة</span></td>
                        </tr>
                    </table>
                </div>
                
                <p style='font-size: 14px; color: #7f8c8d; text-align: center; margin-top: 30px;'>
                    سنرسل لك تحديثات حول حالة طلبك عبر البريد الإلكتروني
                </p>
            </div>
        </div>
        ";
        
        $notificationId = $this->createNotification($customerEmail, 'new_order', $subject, $message, null, $orderId);
        
        if ($notificationId && sendMail($customerEmail, $subject, $message)) {
            $this->updateNotificationStatus($notificationId, 'sent');
            return true;
        } else {
            $this->updateNotificationStatus($notificationId, 'failed', 'فشل في إرسال البريد');
            return false;
        }
    }
    
    /**
     * إرسال إيميل عند تحديث حالة الطلب
     */
    public function sendOrderStatusUpdateEmail($orderId, $customerEmail, $customerName, $orderNumber, $newStatus) {
        $statusMessages = [
            'pending' => ['title' => 'قيد الانتظار ⏳', 'message' => 'طلبك قيد المراجعة', 'color' => '#95a5a6'],
            'processing' => ['title' => 'قيد المعالجة 🔄', 'message' => 'جاري تجهيز طلبك', 'color' => '#f39c12'],
            'completed' => ['title' => 'تم الإنجاز ✅', 'message' => 'تم تسليم طلبك بنجاح!', 'color' => '#2ecc71'],
            'cancelled' => ['title' => 'تم الإلغاء ❌', 'message' => 'تم إلغاء طلبك', 'color' => '#e74c3c']
        ];
        
        $statusInfo = $statusMessages[$newStatus] ?? ['title' => 'تحديث الحالة', 'message' => 'تم تحديث حالة طلبك', 'color' => '#3498db'];
        
        $subject = "تحديث حالة طلبك #{$orderNumber} - {$statusInfo['title']}";
        
        $message = "
        <div dir='rtl' style='font-family: Arial, sans-serif; padding: 20px; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px;'>
                <div style='text-align: center; padding: 20px; background-color: {$statusInfo['color']}; border-radius: 5px; margin-bottom: 30px;'>
                    <h1 style='color: white; margin: 0;'>{$statusInfo['title']}</h1>
                </div>
                
                <p style='font-size: 16px; color: #333;'>
                    عزيزي/عزيزتي <strong>{$customerName}</strong>،
                </p>
                
                <p style='font-size: 16px; line-height: 1.8; color: #333;'>
                    {$statusInfo['message']}
                </p>
                
                <div style='background-color: #ecf0f1; padding: 20px; border-radius: 5px; margin: 20px 0; text-align: center;'>
                    <p style='margin: 0; color: #7f8c8d;'>رقم الطلب</p>
                    <h2 style='margin: 10px 0; color: #2c3e50;'>#{$orderNumber}</h2>
                </div>
                
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='https://eco-friendy.com/orders' style='display: inline-block; padding: 15px 40px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                        عرض تفاصيل الطلب
                    </a>
                </div>
            </div>
        </div>
        ";
        
        $emailType = ($newStatus === 'completed') ? 'completed' : 'general';
        $notificationId = $this->createNotification($customerEmail, $emailType, $subject, $message, null, $orderId);
        
        if ($notificationId && sendMail($customerEmail, $subject, $message)) {
            $this->updateNotificationStatus($notificationId, 'sent');
            
            // تحديث علم الإشعار في جدول الطلبات
            if ($newStatus === 'completed') {
                $stmt = $this->conn->prepare("UPDATE orders SET notified_completed = 1 WHERE id = ?");
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
            }
            
            return true;
        } else {
            $this->updateNotificationStatus($notificationId, 'failed', 'فشل في إرسال البريد');
            return false;
        }
    }
    
    /**
     * إعادة محاولة إرسال الإشعارات الفاشلة
     */
    public function retryFailedNotifications($maxAttempts = 3) {
        $stmt = $this->conn->query("
            SELECT * FROM notifications 
            WHERE status = 'failed' 
            AND attempts < {$maxAttempts}
            ORDER BY created_at DESC
        ");
        
        $retried = 0;
        while ($notification = $stmt->fetch_assoc()) {
            if (sendMail($notification['email'], $notification['subject'], $notification['message'])) {
                $this->updateNotificationStatus($notification['id'], 'sent');
                $retried++;
            } else {
                $this->updateNotificationStatus($notification['id'], 'failed', 'فشلت المحاولة رقم ' . ($notification['attempts'] + 1));
            }
        }
        
        return $retried;
    }
}
