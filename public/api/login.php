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
    // قراءة البيانات المرسلة
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // تسجيل البيانات المستلمة للتشخيص (يمكن حذف هذا السطر لاحقاً)
    error_log("Login attempt data: " . print_r($data, true));
    
    if (!$data) {
        throw new Exception('بيانات غير صالحة');
    }
    
    // التحقق من البيانات المطلوبة
    if (empty($data['username']) || empty($data['password'])) {
        throw new Exception('يرجى إدخال اسم المستخدم وكلمة المرور');
    }
    
    $username = trim($data['username']);
    $password = $data['password'];
    
    // محاولة الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات. يرجى المحاولة لاحقاً.');
    }
    
    // البحث عن المستخدم - الحل: استخدام معاملين منفصلين
    $sql = "SELECT id, fullname, username, email, password, role, status 
            FROM users 
            WHERE username = :username OR email = :email";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username' => $username,
        ':email' => $username  // نفس القيمة لكن باسم معامل مختلف
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // تسجيل محاولة تسجيل دخول فاشلة
        error_log("Login failed: User not found - " . $username);
        throw new Exception('اسم المستخدم أو كلمة المرور غير صحيحة');
    }
    
    // التحقق من كلمة المرور
    if (!password_verify($password, $user['password'])) {
        error_log("Login failed: Wrong password for user - " . $username);
        throw new Exception('اسم المستخدم أو كلمة المرور غير صحيحة');
    }
    
    // التحقق من حالة الحساب
    if ($user['status'] === 'banned') {
        throw new Exception('تم حظر هذا الحساب. يرجى التواصل مع الإدارة');
    }
    
    if ($user['status'] === 'inactive') {
        throw new Exception('هذا الحساب غير نشط. يرجى التواصل مع الإدارة');
    }
    
    // تحديث آخر تسجيل دخول
    try {
        $updateSql = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([':id' => $user['id']]);
    } catch (PDOException $e) {
        // تسجيل الخطأ لكن لا نوقف عملية تسجيل الدخول
        error_log("Failed to update last_login: " . $e->getMessage());
    }
    
    // حفظ بيانات المستخدم في الجلسة
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['logged_in'] = true;
    
    // تسجيل نجاح تسجيل الدخول
    error_log("Login successful: " . $user['username'] . " (Role: " . $user['role'] . ")");
    
    // إعداد البيانات للإرجاع (بدون كلمة المرور)
    unset($user['password']);
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل الدخول بنجاح',
        'user' => $user,
        'redirect' => $user['role'] === 'admin' ? '/admin-dashboard.html' : '/user-dashboard.html'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("PDO Error in login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("General Error in login: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
