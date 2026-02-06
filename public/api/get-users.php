<?php
// api/get-users.php
// جلب قائمة المستخدمين للأدمن

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

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

    $stmt = $pdo->query("
        SELECT 
            id,
            fullname,
            username,
            email,
            phone,
            gender,
            country,
            status,
            created_at,
            last_login
        FROM users 
        ORDER BY created_at DESC
    ");

    $users = $stmt->fetchAll();

    // تنسيق التواريخ
    foreach ($users as &$user) {
        $user['created_at'] = date('Y-m-d H:i', strtotime($user['created_at']));
        if ($user['last_login']) {
            $user['last_login'] = date('Y-m-d H:i', strtotime($user['last_login']));
        }
    }
    unset($user);

    echo json_encode([
        'success' => true,
        'users' => $users,
        'total' => count($users)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
