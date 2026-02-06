<?php
// ملف API لتسجيل الدخول مع تسجيل الحالة في قاعدة البيانات
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

// دالة للحصول على عنوان IP الحقيقي
function getRealIPAddress() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// دالة لاستخراج معلومات المتصفح ونظام التشغيل
function parseUserAgent($userAgent) {
    $browser = 'Unknown';
    $os = 'Unknown';
    $device = 'Desktop';
    
    // تحديد المتصفح
    if (strpos($userAgent, 'Firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edg') === false) {
        $browser = 'Chrome';
    } elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
        $browser = 'Safari';
    } elseif (strpos($userAgent, 'Edg') !== false) {
        $browser = 'Edge';
    } elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) {
        $browser = 'Opera';
    }
    
    // تحديد نظام التشغيل
    if (strpos($userAgent, 'Windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($userAgent, 'Mac') !== false) {
        $os = 'MacOS';
    } elseif (strpos($userAgent, 'Linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($userAgent, 'Android') !== false) {
        $os = 'Android';
        $device = 'Mobile';
    } elseif (strpos($userAgent, 'iOS') !== false || strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
        $os = 'iOS';
        $device = strpos($userAgent, 'iPad') !== false ? 'Tablet' : 'Mobile';
    }
    
    // تحديد نوع الجهاز
    if (strpos($userAgent, 'Mobile') !== false && $device === 'Desktop') {
        $device = 'Mobile';
    } elseif (strpos($userAgent, 'Tablet') !== false) {
        $device = 'Tablet';
    }
    
    return [
        'browser' => $browser,
        'os' => $os,
        'device' => $device
    ];
}

// دالة لتسجيل محاولة تسجيل الدخول
function logLoginAttempt($pdo, $userId, $identifier, $status, $failureReason = null, $rememberMe = false, $sessionToken = null) {
    try {
        $ipAddress = getRealIPAddress();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $deviceInfo = parseUserAgent($userAgent);
        
        $sql = "INSERT INTO login_sessions 
                (user_id, identifier, status, ip_address, user_agent, device_type, browser, operating_system, failure_reason, remember_me, session_token) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId,
            $identifier,
            $status,
            $ipAddress,
            $userAgent,
            $deviceInfo['device'],
            $deviceInfo['browser'],
            $deviceInfo['os'],
            $failureReason,
            $rememberMe ? 1 : 0,
            $sessionToken
        ]);
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("خطأ في تسجيل محاولة الدخول: " . $e->getMessage());
        return false;
    }
}

// دالة لإنشاء جلسة نشطة
function createActiveSession($pdo, $userId, $sessionToken, $rememberMe = false) {
    try {
        $ipAddress = getRealIPAddress();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $sessionId = session_id();
        
        // تحديد وقت انتهاء الجلسة
        $expiresAt = date('Y-m-d H:i:s', strtotime($rememberMe ? '+30 days' : '+24 hours'));
        
        // حذف الجلسات القديمة لنفس المستخدم من نفس الجهاز (اختياري)
        $deleteSql = "DELETE FROM active_sessions WHERE user_id = ? AND session_id = ?";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute([$userId, $sessionId]);
        
        // إضافة الجلسة الجديدة
        $sql = "INSERT INTO active_sessions 
                (user_id, session_id, session_token, ip_address, user_agent, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId,
            $sessionId,
            $sessionToken,
            $ipAddress,
            $userAgent,
            $expiresAt
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("خطأ في إنشاء جلسة نشطة: " . $e->getMessage());
        return false;
    }
}

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
    
    // البحث عن المستخدم (بالبريد الإلكتروني أو اسم المستخدم)
    $sql = "SELECT * FROM users WHERE (email = ? OR username = ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();
    
    // التحقق من وجود المستخدم
    if (!$user) {
        // تسجيل محاولة فاشلة - مستخدم غير موجود
        logLoginAttempt($pdo, null, $identifier, 'failed', 'مستخدم غير موجود', $remember);
        throw new Exception('البريد الإلكتروني أو اسم المستخدم غير موجود');
    }
    
    // التحقق من حالة الحساب
    if ($user['status'] !== 'active') {
        // تسجيل محاولة فاشلة - حساب غير نشط
        logLoginAttempt($pdo, $user['id'], $identifier, 'blocked', 'الحساب غير نشط', $remember);
        throw new Exception('الحساب غير نشط. الرجاء التواصل مع الإدارة');
    }
    
    // التحقق من كلمة المرور
    if (!password_verify($password, $user['password'])) {
        // تسجيل محاولة فاشلة - كلمة مرور خاطئة
        logLoginAttempt($pdo, $user['id'], $identifier, 'failed', 'كلمة مرور خاطئة', $remember);
        throw new Exception('كلمة المرور غير صحيحة');
    }
    
    // إنشاء token للجلسة
    $sessionToken = bin2hex(random_bytes(32));
    
    // تحديث آخر تسجيل دخول
    $update_sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([$user['id']]);
    
    // تسجيل محاولة ناجحة
    $loginSessionId = logLoginAttempt($pdo, $user['id'], $identifier, 'success', null, $remember, $sessionToken);
    
    // بدء الجلسة إذا لم تكن بدأت
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // إنشاء جلسة نشطة
    createActiveSession($pdo, $user['id'], $sessionToken, $remember);
    
    // حفظ بيانات المستخدم في الجلسة
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_fullname'] = $user['fullname'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['session_token'] = $sessionToken;
    $_SESSION['login_session_id'] = $loginSessionId;
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // إذا اختار المستخدم "تذكرني"
    if ($remember) {
        // حفظ التوكن في كوكيز لمدة 30 يوم
        setcookie('remember_token', $sessionToken, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        setcookie('user_id', $user['id'], time() + (30 * 24 * 60 * 60), '/', '', false, true);
    }
    
    // تحديد صفحة التوجيه بناءً على الدور
    $redirectUrl = '/';
    if ($user['role'] === 'admin') {
        $redirectUrl = '/admin-dashboard.html';
    } else {
        $redirectUrl = '/user-dashboard.html';
    }
    
    // إرجاع استجابة نجاح
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
        'session' => [
            'token' => $sessionToken,
            'login_session_id' => $loginSessionId
        ],
        'redirect' => $redirectUrl
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
