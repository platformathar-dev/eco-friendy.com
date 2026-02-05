<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// معالجة طلبات OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// بدء الجلسة
session_start();

// التحقق من وجود مستخدم مسجل دخول
if (isset($_SESSION['user'])) {
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $_SESSION['user']['id'],
            'username' => $_SESSION['user']['username'],
            'fullname' => $_SESSION['user']['fullname'],
            'email' => $_SESSION['user']['email']
        ]
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'لا توجد جلسة نشطة'
    ], JSON_UNESCAPED_UNICODE);
}
?>
