<?php
// ملف API لتسجيل الدخول مع دعم الأدوار
// api/login.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// معالجة طلبات OPTIONS (لـ CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

// التأكد من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'طريقة الطلب غير مسموحة'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // قراءة البيانات من الطلب
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // التحقق من وجود البيانات
    if (!$data) {
        throw new Exception('لم يتم استلام بيانات صحيحة');
    }
    
    // التحقق من الحقول المطلوبة
    if (!isset($data['identifier']) || !isset($data['password'])) {
        throw new Exception('الرجاء إدخال البريد الإلكتروني/اسم المستخدم وكلمة المرور');
    }
    
    $identifier = trim($data['identifier']);
    $password = $data['password'];
    $remember = isset($data['remember']) ? $data['remember'] : false;
    
    // التحقق من عدم وجود حقول فارغة
    if (empty($identifier) || empty($password)) {
        throw new Exception('الرجاء إدخال البريد الإلكتروني/اسم المستخدم وكلمة المرور');
    }
    
    // الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    // البحث عن المستخدم (بالبريد الإلكتروني أو اسم المستخدم) مع جلب الدور
    $sql = "SELECT * FROM users WHERE (email = ? OR username = ?) AND status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();
    
    // التحقق من وجود المستخدم
    if (!$user) {
        throw new Exception('البريد الإلكتروني أو اسم المستخدم غير موجود أو الحساب غير نشط');
    }
    
    // التحقق من كلمة المرور
    if (!password_verify($password, $user['password'])) {
        throw new Exception('كلمة المرور غير صحيحة');
    }
    
    // تحديث آخر تسجيل دخول
    $update_sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([$user['id']]);
    
    // بدء الجلسة إذا لم تكن بدأت
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // حفظ بيانات المستخدم في الجلسة (مع الدور)
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_fullname'] = $user['fullname'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role']; // حفظ الدور
    $_SESSION['logged_in'] = true;
    
    // إذا اختار المستخدم "تذكرني"
    if ($remember) {
        // إنشاء token عشوائي
        $token = bin2hex(random_bytes(32));
        
        // حفظ التوكن في كوكيز لمدة 30 يوم
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
        setcookie('user_id', $user['id'], time() + (30 * 24 * 60 * 60), '/');
    }
    
    // تحديد صفحة التوجيه بناءً على الدور
    $redirectUrl = '/';
    if ($user['role'] === 'admin') {
        $redirectUrl = '/admin-dashboard.html';
    } else {
        $redirectUrl = '/dashboard.html';
    }
    
    // إرجاع استجابة نجاح مع صفحة التوجيه
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل الدخول بنجاح',
        'user' => [
            'id' => $user['id'],
            'fullname' => $user['fullname'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'redirect' => $redirectUrl // رابط التوجيه
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
