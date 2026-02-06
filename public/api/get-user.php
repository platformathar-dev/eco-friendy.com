<?php
// ملف API للتحقق من المستخدم الحالي المسجل دخوله
// api/get-user.php

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
    // التحقق من وجود جلسة نشطة
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'message' => 'لا توجد جلسة نشطة',
            'user' => null
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    $userId = $_SESSION['user_id'];
    
    // جلب بيانات المستخدم من قاعدة البيانات
    $sql = "SELECT id, username, email, fullname, role, status, phone, created_at, last_login 
            FROM users 
            WHERE id = ? AND status = 'active'";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // التحقق من وجود المستخدم
    if (!$user) {
        // المستخدم غير موجود أو غير نشط - تدمير الجلسة
        session_unset();
        session_destroy();
        
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'message' => 'الحساب غير موجود أو غير نشط',
            'user' => null
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // التحقق من صحة التوكن إذا كان موجوداً
    if (isset($_SESSION['session_token'])) {
        $sessionToken = $_SESSION['session_token'];
        
        // التحقق من الجلسة النشطة
        $tokenSql = "SELECT id, expires_at FROM active_sessions 
                     WHERE session_token = ? AND user_id = ?";
        $tokenStmt = $pdo->prepare($tokenSql);
        $tokenStmt->execute([$sessionToken, $userId]);
        $activeSession = $tokenStmt->fetch();
        
        if ($activeSession) {
            // التحقق من انتهاء صلاحية الجلسة
            $expiresAt = strtotime($activeSession['expires_at']);
            if ($expiresAt < time()) {
                // الجلسة منتهية
                session_unset();
                session_destroy();
                
                // حذف الجلسة من قاعدة البيانات
                $deleteSql = "DELETE FROM active_sessions WHERE id = ?";
                $deleteStmt = $pdo->prepare($deleteSql);
                $deleteStmt->execute([$activeSession['id']]);
                
                http_response_code(200);
                echo json_encode([
                    'success' => false,
                    'message' => 'انتهت صلاحية الجلسة',
                    'user' => null
                ], JSON_UNESCAPED_UNICODE);
                exit();
            }
            
            // تحديث آخر نشاط
            $updateSql = "UPDATE active_sessions SET last_activity = CURRENT_TIMESTAMP WHERE id = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$activeSession['id']]);
        }
    }
    
    // إرجاع بيانات المستخدم
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'تم جلب بيانات المستخدم بنجاح',
        'user' => [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'fullname' => $user['fullname'],
            'role' => $user['role'],
            'phone' => $user['phone'],
            'created_at' => $user['created_at'],
            'last_login' => $user['last_login']
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'user' => null
    ], JSON_UNESCAPED_UNICODE);
}
?>
