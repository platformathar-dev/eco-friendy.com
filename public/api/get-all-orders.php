<?php
// api/get-all-orders.php
// جلب جميع الطلبات مع تفاصيلها (للأدمن)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// التحقق من صلاحيات الأدمن (اختياري - يمكن تفعيله)
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     http_response_code(403);
//     echo json_encode([
//         'success' => false,
//         'message' => 'غير مصرح لك بهذه العملية'
//     ], JSON_UNESCAPED_UNICODE);
//     exit();
// }

require_once '../config.php';

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    // جلب جميع الطلبات مع معلومات العميل الكاملة
    $sql = "SELECT 
                o.id,
                o.user_id,
                o.total_amount,
                o.status,
                o.payment_method,
                o.shipping_address,
                o.notes,
                o.customer_name,
                o.customer_phone,
                o.customer_address,
                o.customer_email,
                o.product_name,
                o.order_number,
                o.created_at,
                o.updated_at,
                COUNT(oi.id) as items_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            GROUP BY o.id
            ORDER BY o.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // جلب تفاصيل المنتجات لكل طلب
    foreach ($orders as &$order) {
        $sqlItems = "SELECT 
                        oi.id,
                        oi.product_id,
                        oi.quantity,
                        oi.price,
                        p.name as product_name,
                        p.image as product_image
                     FROM order_items oi
                     LEFT JOIN products p ON oi.product_id = p.id
                     WHERE oi.order_id = :order_id";
        
        $stmtItems = $pdo->prepare($sqlItems);
        $stmtItems->execute([':order_id' => $order['id']]);
        $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        
        // تنسيق التاريخ
        $order['created_at'] = date('Y-m-d H:i', strtotime($order['created_at']));
        
        // التأكد من وجود قيمة افتراضية للحقول الفارغة
        $order['customer_name'] = $order['customer_name'] ?? '-';
        $order['customer_phone'] = $order['customer_phone'] ?? '-';
        $order['customer_email'] = $order['customer_email'] ?? '-';
        $order['customer_address'] = $order['customer_address'] ?? '-';
        $order['notes'] = $order['notes'] ?? '';
        $order['payment_method'] = $order['payment_method'] ?? 'نقدي';
        $order['order_number'] = $order['order_number'] ?? 'ORD-' . $order['id'];
        
        // حساب عدد المنتجات الفعلي
        $order['items_count'] = count($order['items']);
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total' => count($orders)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
