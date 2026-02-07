<?php
/**
 * أداة التثبيت التلقائي لنظام الإشعارات
 * Automatic Installer
 * 
 * هذا الملف سيقوم بـ:
 * 1. إنشاء جميع الملفات المطلوبة
 * 2. إنشاء المجلدات الضرورية
 * 3. ضبط الإعدادات تلقائياً
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// الحصول على المسار الحالي
$baseDir = __DIR__;

echo "<!DOCTYPE html><html dir='rtl'><head><meta charset='UTF-8'>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
    .step { background: #ecf0f1; padding: 15px; border-radius: 8px; margin: 15px 0; border-right: 5px solid #3498db; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 10px 0; border-right: 5px solid #2ecc71; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0; border-right: 5px solid #e74c3c; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 10px 0; border-right: 5px solid #f39c12; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; margin: 10px 0; border-right: 5px solid #17a2b8; }
    pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
    .button { background: #2ecc71; color: white; padding: 15px 30px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; margin: 10px 5px; }
    .button:hover { background: #27ae60; }
</style></head><body><div class='container'>";

echo "<h1>🚀 تثبيت نظام الإشعارات البريدية</h1>";

// ===================================================
// الخطوة 1: إنشاء المجلدات
// ===================================================
echo "<div class='step'><h3>الخطوة 1: إنشاء المجلدات</h3>";

$directories = [
    $baseDir . '/hooks',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<div class='success'>✅ تم إنشاء مجلد: " . basename($dir) . "</div>";
        } else {
            echo "<div class='error'>❌ فشل إنشاء مجلد: " . basename($dir) . "</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ المجلد موجود مسبقاً: " . basename($dir) . "</div>";
    }
}
echo "</div>";

// ===================================================
// الخطوة 2: قراءة إعدادات config.php الموجود
// ===================================================
echo "<div class='step'><h3>الخطوة 2: قراءة إعدادات config.php</h3>";

$configPath = $baseDir . '/config.php';
if (file_exists($configPath)) {
    echo "<div class='success'>✅ ملف config.php موجود</div>";
    include_once $configPath;
} else {
    echo "<div class='error'>❌ ملف config.php غير موجود</div>";
    echo "<div class='warning'>💡 يرجى رفع ملف config.php أولاً</div>";
    die();
}
echo "</div>";

// ===================================================
// الخطوة 3: إنشاء EmailNotificationSystem.php
// ===================================================
echo "<div class='step'><h3>الخطوة 3: إنشاء EmailNotificationSystem.php</h3>";

$emailSystemContent = <<<'PHP'
<?php
/**
 * نظام إرسال الإيميلات التلقائية
 */

// تحميل ملف mailer.php
$mailerPath = __DIR__ . '/mail/mailer.php';
if (file_exists($mailerPath)) {
    require_once $mailerPath;
} else {
    die("Error: mail/mailer.php not found");
}

class EmailNotificationSystem {
    
    private $conn;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
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
            </div>
        </div>
        ";
        
        $emailType = ($newStatus === 'completed') ? 'completed' : 'general';
        $notificationId = $this->createNotification($customerEmail, $emailType, $subject, $message, null, $orderId);
        
        if ($notificationId && sendMail($customerEmail, $subject, $message)) {
            $this->updateNotificationStatus($notificationId, 'sent');
            
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
}
PHP;

$emailSystemFile = $baseDir . '/EmailNotificationSystem.php';
if (file_put_contents($emailSystemFile, $emailSystemContent)) {
    echo "<div class='success'>✅ تم إنشاء EmailNotificationSystem.php</div>";
} else {
    echo "<div class='error'>❌ فشل إنشاء EmailNotificationSystem.php</div>";
}
echo "</div>";

// ===================================================
// الخطوة 4: إنشاء Hooks
// ===================================================
echo "<div class='step'><h3>الخطوة 4: إنشاء ملفات Hooks</h3>";

// Hook 1: User Registration
$userRegHookContent = <<<'PHP'
<?php
require_once __DIR__ . '/../EmailNotificationSystem.php';
require_once __DIR__ . '/../config.php';

function onUserRegistration($userId, $fullname, $email) {
    global $conn;
    
    $emailSystem = new EmailNotificationSystem($conn);
    
    try {
        $result = $emailSystem->sendWelcomeEmail($userId, $fullname, $email);
        
        if ($result) {
            error_log("✅ تم إرسال إيميل الترحيب بنجاح إلى: {$email}");
            return true;
        } else {
            error_log("❌ فشل إرسال إيميل الترحيب إلى: {$email}");
            return false;
        }
    } catch (Exception $e) {
        error_log("❌ خطأ في إرسال إيميل الترحيب: " . $e->getMessage());
        return false;
    }
}
PHP;

if (file_put_contents($baseDir . '/hooks/user_registration_hook.php', $userRegHookContent)) {
    echo "<div class='success'>✅ تم إنشاء user_registration_hook.php</div>";
}

// Hook 2: New Order
$newOrderHookContent = <<<'PHP'
<?php
require_once __DIR__ . '/../EmailNotificationSystem.php';
require_once __DIR__ . '/../config.php';

function onNewOrder($orderId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT id, order_number, customer_name, customer_email, total_amount, product_name
        FROM orders 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        error_log("❌ لم يتم العثور على الطلب رقم: {$orderId}");
        return false;
    }
    
    $emailSystem = new EmailNotificationSystem($conn);
    
    try {
        $result = $emailSystem->sendNewOrderEmail(
            $order['id'],
            $order['customer_email'],
            $order['customer_name'],
            $order['order_number'],
            $order['total_amount'],
            $order['product_name']
        );
        
        if ($result) {
            error_log("✅ تم إرسال إيميل تأكيد الطلب #{$order['order_number']} إلى: {$order['customer_email']}");
            return true;
        } else {
            error_log("❌ فشل إرسال إيميل تأكيد الطلب #{$order['order_number']}");
            return false;
        }
    } catch (Exception $e) {
        error_log("❌ خطأ في إرسال إيميل الطلب: " . $e->getMessage());
        return false;
    }
}
PHP;

if (file_put_contents($baseDir . '/hooks/new_order_hook.php', $newOrderHookContent)) {
    echo "<div class='success'>✅ تم إنشاء new_order_hook.php</div>";
}

// Hook 3: Order Status Update
$orderStatusHookContent = <<<'PHP'
<?php
require_once __DIR__ . '/../EmailNotificationSystem.php';
require_once __DIR__ . '/../config.php';

function onOrderStatusUpdate($orderId, $newStatus) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT id, order_number, customer_name, customer_email, status, notified_completed
        FROM orders 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    if (!$order) {
        error_log("❌ لم يتم العثور على الطلب رقم: {$orderId}");
        return false;
    }
    
    if ($newStatus === 'completed' && $order['notified_completed'] == 1) {
        error_log("ℹ️ تم إرسال إشعار الإكمال مسبقاً للطلب #{$order['order_number']}");
        return true;
    }
    
    $emailSystem = new EmailNotificationSystem($conn);
    
    try {
        $result = $emailSystem->sendOrderStatusUpdateEmail(
            $order['id'],
            $order['customer_email'],
            $order['customer_name'],
            $order['order_number'],
            $newStatus
        );
        
        if ($result) {
            error_log("✅ تم إرسال إيميل تحديث حالة الطلب #{$order['order_number']} إلى: {$order['customer_email']}");
            return true;
        } else {
            error_log("❌ فشل إرسال إيميل تحديث حالة الطلب #{$order['order_number']}");
            return false;
        }
    } catch (Exception $e) {
        error_log("❌ خطأ في إرسال إيميل تحديث الحالة: " . $e->getMessage());
        return false;
    }
}
PHP;

if (file_put_contents($baseDir . '/hooks/order_status_update_hook.php', $orderStatusHookContent)) {
    echo "<div class='success'>✅ تم إنشاء order_status_update_hook.php</div>";
}

echo "</div>";

// ===================================================
// الخطوة 5: اختبار النظام
// ===================================================
echo "<div class='step'><h3>الخطوة 5: اختبار النظام</h3>";

if (isset($conn) && !$conn->connect_error) {
    echo "<div class='success'>✅ الاتصال بقاعدة البيانات ناجح</div>";
    
    // فحص جدول notifications
    $result = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($result && $result->num_rows > 0) {
        echo "<div class='success'>✅ جدول notifications موجود</div>";
    } else {
        echo "<div class='error'>❌ جدول notifications غير موجود</div>";
    }
    
    // فحص ملف mailer
    if (file_exists($baseDir . '/mail/mailer.php')) {
        echo "<div class='success'>✅ ملف mailer.php موجود</div>";
    } else {
        echo "<div class='error'>❌ ملف mailer.php غير موجود</div>";
    }
} else {
    echo "<div class='error'>❌ فشل الاتصال بقاعدة البيانات</div>";
}

echo "</div>";

// ===================================================
// النتيجة النهائية
// ===================================================
echo "<div class='success' style='margin-top: 30px;'>";
echo "<h2 style='margin-top: 0;'>🎉 تم التثبيت بنجاح!</h2>";
echo "<p><strong>الملفات التي تم إنشاؤها:</strong></p>";
echo "<ul>";
echo "<li>✅ EmailNotificationSystem.php</li>";
echo "<li>✅ hooks/user_registration_hook.php</li>";
echo "<li>✅ hooks/new_order_hook.php</li>";
echo "<li>✅ hooks/order_status_update_hook.php</li>";
echo "</ul>";
echo "</div>";

echo "<div class='info'>";
echo "<h3>📝 الخطوات التالية:</h3>";
echo "<ol>";
echo "<li><strong>اختبر النظام:</strong> افتح <code>test_system.php</code> وجرب إرسال بريد تجريبي</li>";
echo "<li><strong>ادمج مع صفحاتك:</strong> استخدم الأمثلة أدناه</li>";
echo "</ol>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>🔗 أمثلة الاستخدام:</h3>";

echo "<h4>1️⃣ في صفحة التسجيل:</h4>";
echo "<pre>
require_once 'config.php';
require_once 'hooks/user_registration_hook.php';

// بعد إدخال المستخدم
if (\$stmt->execute()) {
    \$userId = \$conn->insert_id;
    onUserRegistration(\$userId, \$fullname, \$email);
}
</pre>";

echo "<h4>2️⃣ في صفحة إنشاء الطلب:</h4>";
echo "<pre>
require_once 'config.php';
require_once 'hooks/new_order_hook.php';

// بعد إنشاء الطلب
if (\$stmt->execute()) {
    \$orderId = \$conn->insert_id;
    
    // توليد رقم الطلب
    \$orderNumber = 'ORD-' . str_pad(\$orderId, 6, '0', STR_PAD_LEFT);
    \$conn->query(\"UPDATE orders SET order_number = '{\$orderNumber}' WHERE id = {\$orderId}\");
    
    onNewOrder(\$orderId);
}
</pre>";

echo "<h4>3️⃣ في صفحة تحديث حالة الطلب:</h4>";
echo "<pre>
require_once 'config.php';
require_once 'hooks/order_status_update_hook.php';

// عند تحديث الحالة
\$stmt = \$conn->prepare(\"UPDATE orders SET status = ? WHERE id = ?\");
\$stmt->bind_param(\"si\", \$newStatus, \$orderId);

if (\$stmt->execute()) {
    onOrderStatusUpdate(\$orderId, \$newStatus);
}
</pre>";

echo "</div>";

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='test_system.php' class='button'>🧪 اختبار النظام الآن</a>";
echo "<a href='diagnose.php' class='button' style='background: #3498db;'>🔍 تشخيص النظام</a>";
echo "</div>";

echo "</div></body></html>";
?>
