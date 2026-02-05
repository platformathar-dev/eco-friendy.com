<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// معالجة طلبات OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// التأكد من أن الطلب هو POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// بدء الجلسة
session_start();

try {
    // حفظ اسم المستخدم قبل الحذف (اختياري للرسالة)
    $username = isset($_SESSION['user']) ? $_SESSION['user']['username'] : null;
    
    // مسح جميع متغيرات الجلسة
    $_SESSION = array();
    
    // حذف كوكيز الجلسة إن وجدت
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // إتلاف الجلسة نهائياً
    session_destroy();
    
    // حذف كوكيز "تذكرني" إن وجدت
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    if (isset($_COOKIE['user_id'])) {
        setcookie('user_id', '', time() - 3600, '/');
    }
    
    // إرجاع استجابة نجاح
    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل الخروج بنجاح',
        'username' => $username
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // في حالة حدوث خطأ
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء تسجيل الخروج',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
