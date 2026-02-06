<?php
// api/get-all-orders.php
// جلب جميع الطلبات للأدمن

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once '../config.php';

try {
    $pdo = getDBConnection();

    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }

    // جلب جميع الطلبات مع اسم العميل وعدد المنتجات
    $stmt = $pdo->query("
        SELECT 
            o.id,
            o.user_id,
            o.total_amount,
            o.status,
            o.notes,
            o.created_at,
            u.fullname as customer_name,
            COUNT(oi.id) as items_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");

    $orders = $stmt->fetchAll();

    // جلب تفاصيل المنتجات لكل طلب
    $itemsStmt = $pdo->prepare("
        SELECT 
            oi.product_id,
            oi.quantity,
            oi.price,
            p.name as product_name,
            p.image as product_image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");

    foreach ($orders as &$order) {
        $order['created_at'] = date('Y-m-d H:i', strtotime($order['created_at']));
        $order['items_count'] = (int)$order['items_count'];

        $itemsStmt->execute([$order['id']]);
        $order['items'] = $itemsStmt->fetchAll();
    }
    unset($order);

    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total' => count($orders)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
