<?php
/**
 * Hook لإرسال إيميل عند تحديث حالة الطلب
 * Order Status Update Hook
 */

require_once __DIR__ . '/../EmailNotificationSystem.php';
require_once __DIR__ . '/../db_config.php';

/**
 * استخدم هذه الدالة عند تحديث حالة الطلب
 */
function onOrderStatusUpdate($orderId, $newStatus) {
    global $conn;
    
    // جلب معلومات الطلب
    $stmt = $conn->prepare("
        SELECT 
            id, 
            order_number, 
            customer_name, 
            customer_email,
            status,
            notified_completed
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
    
    // تجنب إرسال إشعار مكرر للطلبات المكتملة
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

/**
 * مثال على الاستخدام في صفحة تحديث الطلب:
 * 
 * // عند تحديث حالة الطلب
 * $orderId = $_POST['order_id'];
 * $newStatus = $_POST['status']; // pending, processing, completed, cancelled
 * 
 * $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
 * $stmt->bind_param("si", $newStatus, $orderId);
 * 
 * if ($stmt->execute()) {
 *     // إرسال إيميل تحديث الحالة
 *     onOrderStatusUpdate($orderId, $newStatus);
 *     
 *     echo json_encode(['success' => true, 'message' => 'تم تحديث الحالة بنجاح']);
 * }
 */
