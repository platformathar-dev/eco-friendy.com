<?php
/**
 * Hook لإرسال إيميل تأكيد عند إنشاء طلب جديد
 * New Order Hook
 */

require_once __DIR__ . '/../EmailNotificationSystem.php';
require_once __DIR__ . '/../config.php';

/**
 * استخدم هذه الدالة بعد نجاح إنشاء طلب جديد
 */
function onNewOrder($orderId) {
    global $conn;
    
    // جلب معلومات الطلب
    $stmt = $conn->prepare("
        SELECT 
            id, 
            order_number, 
            customer_name, 
            customer_email, 
            total_amount,
            product_name
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

/**
 * مثال على الاستخدام في صفحة إنشاء الطلب:
 * 
 * // بعد إدخال الطلب في قاعدة البيانات
 * $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_email, total_amount, ...) VALUES (?, ?, ?, ...)");
 * $stmt->bind_param(...);
 * 
 * if ($stmt->execute()) {
 *     $orderId = $conn->insert_id;
 *     
 *     // توليد رقم الطلب إذا لم يكن موجوداً
 *     $orderNumber = 'ORD-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
 *     $conn->query("UPDATE orders SET order_number = '{$orderNumber}' WHERE id = {$orderId}");
 *     
 *     // إرسال إيميل تأكيد الطلب
 *     onNewOrder($orderId);
 *     
 *     // باقي الكود...
 * }
 */
