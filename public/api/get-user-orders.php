<?php
// ملف API لعرض طلبات المستخدم
// api/get-user-orders.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// معالجة طلبات OPTIONS (لـ CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // التحقق من تسجيل الدخول
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        throw new Exception('يجب تسجيل الدخول أولاً');
    }
    
    // الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'];
    
    // المعاملات من الطلب
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
    
    // بناء الاستعلام
    $sql = "SELECT 
                o.id,
                o.order_number,
                o.customer_name,
                o.customer_phone,
                o.customer_email,
                o.customer_address,
                o.shipping_address,
                o.notes,
                o.payment_method,
                o.payment_status,
                o.status,
                o.total_amount,
                o.created_at,
                o.updated_at,
                u.username,
                u.fullname,
                u.email as user_email
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    
    // إذا كان مستخدم عادي، فقط طلباته
    if ($userRole !== 'admin') {
        $sql .= " AND o.user_id = ?";
        $params[] = $userId;
    }
    
    // إذا حدد order_id معين
    if ($orderId) {
        $sql .= " AND o.id = ?";
        $params[] = $orderId;
    }
    
    // تصفية حسب الحالة
    if ($status) {
        $sql .= " AND o.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    // جلب منتجات كل طلب
    foreach ($orders as &$order) {
        $itemsSql = "SELECT 
                        oi.id,
                        oi.product_id,
                        oi.product_name,
                        oi.quantity,
                        oi.price,
                        oi.total,
                        p.image,
                        p.description
                     FROM order_items oi
                     LEFT JOIN products p ON oi.product_id = p.id
                     WHERE oi.order_id = ?";
        
        $itemsStmt = $pdo->prepare($itemsSql);
        $itemsStmt->execute([$order['id']]);
        $order['items'] = $itemsStmt->fetchAll();
    }
    
    // حساب إجمالي الطلبات
    $countSql = "SELECT COUNT(*) as total FROM orders o WHERE 1=1";
    $countParams = [];
    
    if ($userRole !== 'admin') {
        $countSql .= " AND o.user_id = ?";
        $countParams[] = $userId;
    }
    
    if ($status) {
        $countSql .= " AND o.status = ?";
        $countParams[] = $status;
    }
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetch()['total'];
    
    // إحصائيات
    $statsSql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
                 FROM orders WHERE 1=1";
    
    $statsParams = [];
    if ($userRole !== 'admin') {
        $statsSql .= " AND user_id = ?";
        $statsParams[] = $userId;
    }
    
    $statsStmt = $pdo->prepare($statsSql);
    $statsStmt->execute($statsParams);
    $stats = $statsStmt->fetch();
    
    // إرجاع النتائج
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $orders,
        'pagination' => [
            'total' => (int)$totalRecords,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $totalRecords
        ],
        'statistics' => [
            'total_orders' => (int)$stats['total_orders'],
            'total_revenue' => (float)$stats['total_revenue'],
            'pending_orders' => (int)$stats['pending_orders'],
            'confirmed_orders' => (int)$stats['confirmed_orders'],
            'delivered_orders' => (int)$stats['delivered_orders'],
            'cancelled_orders' => (int)$stats['cancelled_orders']
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
