<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// التعامل مع طلبات OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً'
    ]);
    exit();
}

try {
    // الاتصال بقاعدة البيانات
    require_once 'config.php';
    
    $userId = $_SESSION['user_id'];
    
    // جلب معلومات المستخدم
    $stmt = $conn->prepare("
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
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'المستخدم غير موجود'
        ]);
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // تحديث آخر تسجيل دخول
    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $userId);
    $updateStmt->execute();
    $updateStmt->close();
    
    // تنسيق التواريخ
    $user['created_at'] = date('Y-m-d H:i', strtotime($user['created_at']));
    if ($user['last_login']) {
        $user['last_login'] = date('Y-m-d H:i', strtotime($user['last_login']));
    }
    
    // إخفاء معلومات حساسة
    unset($user['password']);
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ]);
}
?>
