<?php
// ملف API لتسجيل الخروج مع تحديث حالة الجلسة
// api/logout.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
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
        throw new Exception('لا توجد جلسة نشطة');
    }
    
    // الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    $userId = $_SESSION['user_id'];
    $sessionToken = $_SESSION['session_token'] ?? null;
    $loginSessionId = $_SESSION['login_session_id'] ?? null;
    
    // تحديث وقت تسجيل الخروج في جدول login_sessions
    if ($loginSessionId) {
        $sql = "UPDATE login_sessions SET logout_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$loginSessionId]);
    }
    
    // حذف الجلسة النشطة
    if ($sessionToken) {
        $deleteSql = "DELETE FROM active_sessions WHERE session_token = ?";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute([$sessionToken]);
    }
    
    // حذف الكوكيز
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    if (isset($_COOKIE['user_id'])) {
        setcookie('user_id', '', time() - 3600, '/');
    }
    
    // تدمير الجلسة
    session_unset();
    session_destroy();
    
    // إرجاع استجابة نجاح
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل الخروج بنجاح'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
