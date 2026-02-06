<?php
// ملف API لعرض سجل تسجيلات الدخول
// api/get_login_history.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
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
    // التحقق من وجود جلسة نشطة
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
    
    // إذا كان المستخدم admin، يمكنه رؤية جميع السجلات
    // إذا كان مستخدم عادي، يرى سجلاته فقط
    
    // الحصول على المعاملات من الطلب
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // بناء الاستعلام
    $sql = "SELECT 
                ls.id,
                ls.user_id,
                ls.identifier,
                ls.status,
                ls.ip_address,
                ls.device_type,
                ls.browser,
                ls.operating_system,
                ls.failure_reason,
                ls.remember_me,
                ls.created_at,
                ls.logout_at,
                u.fullname,
                u.username,
                u.email,
                u.role
            FROM login_sessions ls
            LEFT JOIN users u ON ls.user_id = u.id
            WHERE 1=1";
    
    $params = [];
    
    // إذا كان مستخدم عادي، فقط سجلاته
    if ($userRole !== 'admin') {
        $sql .= " AND ls.user_id = ?";
        $params[] = $userId;
    } else {
        // إذا كان admin وحدد user_id معين
        if ($targetUserId) {
            $sql .= " AND ls.user_id = ?";
            $params[] = $targetUserId;
        }
    }
    
    // تصفية حسب الحالة
    if ($status && in_array($status, ['success', 'failed', 'blocked'])) {
        $sql .= " AND ls.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY ls.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $loginHistory = $stmt->fetchAll();
    
    // حساب إجمالي السجلات
    $countSql = "SELECT COUNT(*) as total FROM login_sessions ls WHERE 1=1";
    $countParams = [];
    
    if ($userRole !== 'admin') {
        $countSql .= " AND ls.user_id = ?";
        $countParams[] = $userId;
    } else if ($targetUserId) {
        $countSql .= " AND ls.user_id = ?";
        $countParams[] = $targetUserId;
    }
    
    if ($status && in_array($status, ['success', 'failed', 'blocked'])) {
        $countSql .= " AND ls.status = ?";
        $countParams[] = $status;
    }
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetch()['total'];
    
    // حساب الإحصائيات
    $statsSql = "SELECT 
                    COUNT(*) as total_logins,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_logins,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_logins,
                    SUM(CASE WHEN status = 'blocked' THEN 1 ELSE 0 END) as blocked_logins
                FROM login_sessions WHERE 1=1";
    
    $statsParams = [];
    if ($userRole !== 'admin') {
        $statsSql .= " AND user_id = ?";
        $statsParams[] = $userId;
    } else if ($targetUserId) {
        $statsSql .= " AND user_id = ?";
        $statsParams[] = $targetUserId;
    }
    
    $statsStmt = $pdo->prepare($statsSql);
    $statsStmt->execute($statsParams);
    $stats = $statsStmt->fetch();
    
    // إرجاع النتائج
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $loginHistory,
        'pagination' => [
            'total' => (int)$totalRecords,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $totalRecords
        ],
        'statistics' => [
            'total_logins' => (int)$stats['total_logins'],
            'successful_logins' => (int)$stats['successful_logins'],
            'failed_logins' => (int)$stats['failed_logins'],
            'blocked_logins' => (int)$stats['blocked_logins'],
            'success_rate' => $stats['total_logins'] > 0 
                ? round(($stats['successful_logins'] / $stats['total_logins']) * 100, 2) 
                : 0
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
