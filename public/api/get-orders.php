<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// التعامل مع طلبات OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً'
    ]);
    exit();
}

try {
    // الاتصال بقاعدة البيانات
    require_once 'config.php';
    
    $userId = $_SESSION['user_id'];
    
    // جلب جميع طلبات المستخدم مع تفاصيل المنتجات
    $stmt = $conn->prepare("
        SELECT 
            o.id,
            o.total_amount,
            o.status,
            o.notes,
            o.created_at,
            COUNT(oi.id) as items_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // تنسيق التاريخ
        $row['created_at'] = date('Y-m-d H:i', strtotime($row['created_at']));
        
        // تحويل العدد إلى integer
        $row['items_count'] = (int)$row['items_count'];
        
        // جلب تفاصيل المنتجات في هذا الطلب
        $itemsStmt = $conn->prepare("
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
        
        $itemsStmt->bind_param("i", $row['id']);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        
        $items = [];
        while ($item = $itemsResult->fetch_assoc()) {
            $items[] = $item;
        }
        
        $row['items'] = $items;
        $itemsStmt->close();
        
        $orders[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total' => count($orders)
    ]);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ]);
}
?>
