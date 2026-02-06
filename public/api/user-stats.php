<?php
// ملف API لجلب إحصائيات المستخدم
// api/user-stats.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التحقق من تسجيل الدخول
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }

    $userId = $_SESSION['user_id'];

    // إجمالي الطلبات
    $sql = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // طلبات قيد الانتظار
    $sql = "SELECT COUNT(*) as total FROM orders WHERE user_id = ? AND status IN ('pending', 'processing')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $pendingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // طلبات مكتملة
    $sql = "SELECT COUNT(*) as total FROM orders WHERE user_id = ? AND status = 'completed'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $completedOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // إجمالي المشتريات
    $sql = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $totalSpent = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

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
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
