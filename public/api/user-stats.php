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
    
    // إجمالي الطلبات
    $totalOrdersStmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM orders 
        WHERE user_id = ?
    ");
    $totalOrdersStmt->bind_param("i", $userId);
    $totalOrdersStmt->execute();
    $totalOrders = $totalOrdersStmt->get_result()->fetch_assoc()['count'];
    $totalOrdersStmt->close();
    
    // الطلبات قيد الانتظار والتنفيذ
    $pendingOrdersStmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM orders 
        WHERE user_id = ? AND status IN ('pending', 'processing')
    ");
    $pendingOrdersStmt->bind_param("i", $userId);
    $pendingOrdersStmt->execute();
    $pendingOrders = $pendingOrdersStmt->get_result()->fetch_assoc()['count'];
    $pendingOrdersStmt->close();
    
    // الطلبات المكتملة
    $completedOrdersStmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM orders 
        WHERE user_id = ? AND status = 'completed'
    ");
    $completedOrdersStmt->bind_param("i", $userId);
    $completedOrdersStmt->execute();
    $completedOrders = $completedOrdersStmt->get_result()->fetch_assoc()['count'];
    $completedOrdersStmt->close();
    
    // إجمالي المبلغ المنفق
    $totalSpentStmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM orders 
        WHERE user_id = ? AND status != 'cancelled'
    ");
    $totalSpentStmt->bind_param("i", $userId);
    $totalSpentStmt->execute();
    $totalSpent = $totalSpentStmt->get_result()->fetch_assoc()['total'];
    $totalSpentStmt->close();
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_orders' => (int)$totalOrders,
            'pending_orders' => (int)$pendingOrders,
            'completed_orders' => (int)$completedOrders,
            'total_spent' => number_format((float)$totalSpent, 2) . ' د.أ'
        ]
    ]);
    
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ]);
}
?>
