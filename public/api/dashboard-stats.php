<?php
// api/dashboard-stats.php
// جلب إحصائيات لوحة التحكم
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

require_once '../config.php';

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    // عدد المستخدمين
    $sqlUsers = "SELECT COUNT(*) as total FROM users";
    $stmtUsers = $pdo->query($sqlUsers);
    $totalUsers = $stmtUsers->fetch(PDO::FETCH_ASSOC)['total'];
    
    // عدد الطلبات
    $sqlOrders = "SELECT COUNT(*) as total FROM orders";
    $stmtOrders = $pdo->query($sqlOrders);
    $totalOrders = $stmtOrders->fetch(PDO::FETCH_ASSOC)['total'];
    
    // عدد المنتجات
    $sqlProducts = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
    $stmtProducts = $pdo->query($sqlProducts);
    $totalProducts = $stmtProducts->fetch(PDO::FETCH_ASSOC)['total'];
    
    // إجمالي الإيرادات (من الطلبات المكتملة)
    $sqlRevenue = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'completed'";
    $stmtRevenue = $pdo->query($sqlRevenue);
    $totalRevenue = $stmtRevenue->fetch(PDO::FETCH_ASSOC)['total'];
    
    // إحصائيات إضافية
    // الطلبات قيد الانتظار
    $sqlPending = "SELECT COUNT(*) as total FROM orders WHERE status = 'pending'";
    $stmtPending = $pdo->query($sqlPending);
    $pendingOrders = $stmtPending->fetch(PDO::FETCH_ASSOC)['total'];
    
    // الطلبات قيد التنفيذ
    $sqlProcessing = "SELECT COUNT(*) as total FROM orders WHERE status = 'processing'";
    $stmtProcessing = $pdo->query($sqlProcessing);
    $processingOrders = $stmtProcessing->fetch(PDO::FETCH_ASSOC)['total'];
    
    // الطلبات المكتملة
    $sqlCompleted = "SELECT COUNT(*) as total FROM orders WHERE status = 'completed'";
    $stmtCompleted = $pdo->query($sqlCompleted);
    $completedOrders = $stmtCompleted->fetch(PDO::FETCH_ASSOC)['total'];
    
    // الطلبات الملغاة
    $sqlCancelled = "SELECT COUNT(*) as total FROM orders WHERE status = 'cancelled'";
    $stmtCancelled = $pdo->query($sqlCancelled);
    $cancelledOrders = $stmtCancelled->fetch(PDO::FETCH_ASSOC)['total'];
    
    // طلبات اليوم
    $sqlToday = "SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()";
    $stmtToday = $pdo->query($sqlToday);
    $todayOrders = $stmtToday->fetch(PDO::FETCH_ASSOC)['total'];
    
    // مبيعات اليوم
    $sqlTodayRevenue = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'completed'";
    $stmtTodayRevenue = $pdo->query($sqlTodayRevenue);
    $todayRevenue = $stmtTodayRevenue->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_users' => $totalUsers,
            'total_orders' => $totalOrders,
            'total_products' => $totalProducts,
            'total_revenue' => number_format($totalRevenue, 2) . ' د.أ',
            'pending_orders' => $pendingOrders,
            'processing_orders' => $processingOrders,
            'completed_orders' => $completedOrders,
            'cancelled_orders' => $cancelledOrders,
            'today_orders' => $todayOrders,
            'today_revenue' => number_format($todayRevenue, 2) . ' د.أ'
        ]
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
