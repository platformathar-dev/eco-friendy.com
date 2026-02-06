<?php
// api/get-user-profile.php
// جلب معلومات المستخدم

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// التحقق من تسجيل الدخول
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

    $userId = $_SESSION['user_id'];

    // جلب معلومات المستخدم
    $stmt = $pdo->prepare("
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
        WHERE id = ?
    ");

    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'المستخدم غير موجود'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // تحديث آخر تسجيل دخول
    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$userId]);

    // تنسيق التواريخ
    $user['created_at'] = date('Y-m-d H:i', strtotime($user['created_at']));
    if ($user['last_login']) {
        $user['last_login'] = date('Y-m-d H:i', strtotime($user['last_login']));
    }

    echo json_encode([
        'success' => true,
        'user' => $user
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
