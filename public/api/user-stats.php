<?php
// api/user-stats.php
// جلب إحصائيات المستخدم

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// التحقق من تسجيل الدخول
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

    $userId = $_SESSION['user_id'];

    // إجمالي الطلبات
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalOrders = $stmt->fetch()['count'];

    // الطلبات قيد الانتظار والتنفيذ
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status IN ('pending', 'processing')");
    $stmt->execute([$userId]);
    $pendingOrders = $stmt->fetch()['count'];

    // الطلبات المكتملة
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $completedOrders = $stmt->fetch()['count'];

    // إجمالي المبلغ المنفق
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE user_id = ? AND status != 'cancelled'");
    $stmt->execute([$userId]);
    $totalSpent = $stmt->fetch()['total'];

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_orders' => (int)$totalOrders,
            'pending_orders' => (int)$pendingOrders,
            'completed_orders' => (int)$completedOrders,
            'total_spent' => number_format((float)$totalSpent, 2) . ' د.أ'
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
