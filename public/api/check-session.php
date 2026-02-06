<?php
// api/check-session.php
// ملف للتحقق من الجلسة الحالية وإصلاحها إذا لزم الأمر
header('Content-Type: application/json; charset=utf-8');

session_start();

require_once '../config.php';

try {
    // التحقق إذا كان المستخدم مسجل دخول
    if (isset($_SESSION['user_id'])) {
        
        // إذا كان role غير موجود في الجلسة، جلبه من قاعدة البيانات
        if (!isset($_SESSION['role'])) {
            $pdo = getDBConnection();
            
            $sql = "SELECT role, fullname, username, email, status FROM users WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $_SESSION['role'] = $user['role'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                echo json_encode([
                    'success' => true,
                    'message' => 'تم تحديث بيانات الجلسة',
                    'session_updated' => true,
                    'user' => [
                        'id' => $_SESSION['user_id'],
                        'username' => $user['username'],
                        'fullname' => $user['fullname'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'status' => $user['status']
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                // المستخدم غير موجود في قاعدة البيانات
                session_destroy();
                echo json_encode([
                    'success' => false,
                    'message' => 'المستخدم غير موجود',
                    'logged_in' => false
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            // الجلسة سليمة
            echo json_encode([
                'success' => true,
                'message' => 'الجلسة نشطة',
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'] ?? '',
                    'fullname' => $_SESSION['fullname'] ?? '',
                    'email' => $_SESSION['email'] ?? '',
                    'role' => $_SESSION['role']
                ],
                'logged_in' => true
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'لم يتم تسجيل الدخول',
            'logged_in' => false
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage(),
        'logged_in' => false
    ], JSON_UNESCAPED_UNICODE);
}
?>
