<?php
// api/logout.php
// تسجيل الخروج

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// تدمير الجلسة
session_unset();
session_destroy();

echo json_encode([
    'success' => true,
    'message' => 'تم تسجيل الخروج بنجاح'
], JSON_UNESCAPED_UNICODE);
?>
