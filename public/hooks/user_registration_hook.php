<?php
/**
 * Hook لإرسال إيميل ترحيب عند تسجيل مستخدم جديد
 * User Registration Hook
 */

require_once __DIR__ . '/EmailNotificationSystem.php';
require_once __DIR__ . '/config.php'; // تأكد من وجود ملف إعدادات قاعدة البيانات

/**
 * استخدم هذه الدالة بعد نجاح تسجيل المستخدم
 */
function onUserRegistration($userId, $fullname, $email) {
    global $conn; // أو استخدم اتصال قاعدة البيانات الخاصة بك
    
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

/**
 * مثال على الاستخدام في صفحة التسجيل:
 * 
 * // بعد إدخال البيانات في جدول users
 * $stmt = $conn->prepare("INSERT INTO users (fullname, username, email, password, ...) VALUES (?, ?, ?, ?, ...)");
 * $stmt->bind_param(...);
 * 
 * if ($stmt->execute()) {
 *     $userId = $conn->insert_id;
 *     
 *     // إرسال إيميل الترحيب
 *     onUserRegistration($userId, $fullname, $email);
 *     
 *     // باقي الكود...
 * }
 */
