<?php
// ملف API للتسجيل
// api/register.php

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
    ]);
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
    $required_fields = ['fullname', 'username', 'email', 'phone', 'birthdate', 'gender', 'country', 'password'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("الحقل '{$field}' مطلوب");
        }
    }

    // تنظيف البيانات
    $fullname = trim($data['fullname']);
    $username = trim($data['username']);
    $email = trim($data['email']);
    $phone = trim($data['phone']);
    $birthdate = trim($data['birthdate']);
    $gender = trim($data['gender']);
    $country = trim($data['country']);
    $password = $data['password'];

    // التحقق من صحة البيانات
    
    // التحقق من الاسم الكامل (3 أحرف على الأقل)
    if (strlen($fullname) < 3) {
        throw new Exception('الاسم الكامل يجب أن يكون 3 أحرف على الأقل');
    }

    // التحقق من اسم المستخدم (3-50 حرف)
    if (strlen($username) < 3 || strlen($username) > 50) {
        throw new Exception('اسم المستخدم يجب أن يكون بين 3 و 50 حرف');
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        throw new Exception('اسم المستخدم يجب أن يحتوي على أحرف وأرقام و _ فقط');
    }

    // التحقق من البريد الإلكتروني
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('البريد الإلكتروني غير صحيح');
    }

    // التحقق من رقم الهاتف (10 أرقام تبدأ بـ 07)
    if (!preg_match('/^07\d{8}$/', $phone)) {
        throw new Exception('رقم الهاتف يجب أن يبدأ بـ 07 ويتكون من 10 أرقام');
    }

    // التحقق من تاريخ الميلاد
    $date = DateTime::createFromFormat('Y-m-d', $birthdate);
    if (!$date || $date->format('Y-m-d') !== $birthdate) {
        throw new Exception('تاريخ الميلاد غير صحيح');
    }

    // التحقق من أن المستخدم أكبر من 13 سنة
    $today = new DateTime();
    $age = $today->diff($date)->y;
    if ($age < 13) {
        throw new Exception('يجب أن يكون عمرك 13 سنة على الأقل');
    }

    // التحقق من الجنس
    if (!in_array($gender, ['male', 'female'])) {
        throw new Exception('الجنس غير صحيح');
    }

    // التحقق من كلمة المرور (8 أحرف على الأقل)
    if (strlen($password) < 8) {
        throw new Exception('كلمة المرور يجب أن تكون 8 أحرف على الأقل');
    }

    // الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }

    // التحقق من عدم وجود اسم المستخدم مسبقاً
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        throw new Exception('اسم المستخدم موجود مسبقاً');
    }

    // التحقق من عدم وجود البريد الإلكتروني مسبقاً
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('البريد الإلكتروني موجود مسبقاً');
    }

    // التحقق من عدم وجود رقم الهاتف مسبقاً
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        throw new Exception('رقم الهاتف موجود مسبقاً');
    }

    // تشفير كلمة المرور
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // إدراج المستخدم الجديد
    $sql = "INSERT INTO users (fullname, username, email, phone, birthdate, gender, country, password) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $fullname,
        $username,
        $email,
        $phone,
        $birthdate,
        $gender,
        $country,
        $hashed_password
    ]);

    if ($result) {
        $user_id = $pdo->lastInsertId();
        
        // إرجاع استجابة نجاح
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'تم إنشاء حسابك بنجاح',
            'user' => [
                'id' => $user_id,
                'fullname' => $fullname,
                'username' => $username,
                'email' => $email
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('فشل في إنشاء الحساب');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
