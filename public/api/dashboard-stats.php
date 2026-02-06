<?php
// api/dashboard-stats.php
// إحصائيات لوحة التحكم للأدمن

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// التحقق من تسجيل الدخول (يمكنك إضافة تحقق من صلاحيات الأدمن)
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

    // إجمالي المستخدمين
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $totalUsers = $stmt->fetch()['count'];

    // إجمالي المنتجات النشطة
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
    $totalProducts = $stmt->fetch()['count'];

    // إجمالي الطلبات
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $totalOrders = $stmt->fetch()['count'];

    // إجمالي الإيرادات (الطلبات غير الملغاة)
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled'");
    $totalRevenue = $stmt->fetch()['total'];

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => (int)$totalUsers,
            'total_products' => (int)$totalProducts,
            'total_orders' => (int)$totalOrders,
            'total_revenue' => number_format((float)$totalRevenue, 2) . ' د.أ'
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
