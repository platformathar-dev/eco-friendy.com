<?php
// api/login.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

require_once '../config.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('بيانات غير صالحة');
    }
    
    // التحقق من البيانات المطلوبة
    if (empty($data['username']) || empty($data['password'])) {
        throw new Exception('يرجى إدخال اسم المستخدم وكلمة المرور');
    }
    
    $username = trim($data['username']);
    $password = $data['password'];
    
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    // البحث عن المستخدم
    $sql = "SELECT id, fullname, username, email, password, role, status 
            FROM users 
            WHERE username = :username OR email = :username";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('اسم المستخدم أو كلمة المرور غير صحيحة');
    }
    
    // التحقق من كلمة المرور
    if (!password_verify($password, $user['password'])) {
        throw new Exception('اسم المستخدم أو كلمة المرور غير صحيحة');
    }
    
    // التحقق من حالة الحساب
    if ($user['status'] === 'banned') {
        throw new Exception('تم حظر هذا الحساب. يرجى التواصل مع الإدارة');
    }
    
    if ($user['status'] === 'inactive') {
        throw new Exception('هذا الحساب غير نشط');
    }
    
    // تحديث آخر تسجيل دخول
    $updateSql = "UPDATE users SET last_login = NOW() WHERE id = :id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([':id' => $user['id']]);
    
    // حفظ بيانات المستخدم في الجلسة
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role']; // هذا هو المهم!
    $_SESSION['logged_in'] = true;
    
    // إعداد البيانات للإرجاع (بدون كلمة المرور)
    unset($user['password']);
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل الدخول بنجاح',
        'user' => $user,
        'redirect' => $user['role'] === 'admin' ? '/dashboard.html' : '/index.html'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
