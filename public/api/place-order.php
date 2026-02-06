<?php
// ملف API لإضافة طلب جديد
// api/place-order.php

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

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    // التحقق من تسجيل الدخول
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'يجب تسجيل الدخول أولاً'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // قراءة البيانات من الطلب
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // التحقق من وجود البيانات
    if (!$data) {
        throw new Exception('لم يتم استلام بيانات صحيحة');
    }
    
    // التحقق من الحقول المطلوبة
    $requiredFields = ['user_id', 'customer_name', 'customer_phone', 'customer_address', 'payment_method', 'items', 'total_amount'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("الحقل المطلوب مفقود: $field");
        }
    }
    
    // التحقق من أن المستخدم يطلب لنفسه
    if ((int)$data['user_id'] !== (int)$_SESSION['user_id']) {
        throw new Exception('غير مصرح لك بإنشاء طلب لمستخدم آخر');
    }
    
    // الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    // بدء المعاملة
    $pdo->beginTransaction();
    
    // إنشاء رقم طلب فريد
    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    // إدراج الطلب الرئيسي
    $sql = "INSERT INTO orders (
                user_id, 
                order_number, 
                customer_name, 
                customer_phone, 
                customer_email,
                customer_address, 
                shipping_address,
                notes,
                payment_method, 
                status, 
                total_amount,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        (int)$data['user_id'],
        $orderNumber,
        $data['customer_name'],
        $data['customer_phone'],
        $_SESSION['user_email'] ?? '', // البريد من الجلسة
        $data['customer_address'],
        $data['shipping_address'] ?? $data['customer_address'],
        $data['notes'] ?? '',
        $data['payment_method'],
        $data['status'] ?? 'pending',
        (float)$data['total_amount']
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    // إدراج منتجات الطلب
    if (!empty($data['items']) && is_array($data['items'])) {
        $itemSql = "INSERT INTO order_items (
                        order_id, 
                        product_id, 
                        quantity, 
                        price, 
                        total
                    ) VALUES (?, ?, ?, ?, ?)";
        
        $itemStmt = $pdo->prepare($itemSql);
        
        foreach ($data['items'] as $item) {
            $quantity = (int)$item['quantity'];
            $price = (float)$item['price'];
            $total = $quantity * $price;
            
            $itemStmt->execute([
                $orderId,
                (int)$item['product_id'],
                $quantity,
                $price,
                $total
            ]);
        }
    } else {
        throw new Exception('يجب إضافة منتج واحد على الأقل');
    }
    
    // تسجيل نشاط الطلب
    $activitySql = "INSERT INTO order_activity_log (
                        order_id, 
                        user_id, 
                        action, 
                        details
                    ) VALUES (?, ?, ?, ?)";
    
    $activityStmt = $pdo->prepare($activitySql);
    $activityStmt->execute([
        $orderId,
        (int)$data['user_id'],
        'order_created',
        json_encode([
            'payment_method' => $data['payment_method'],
            'total_amount' => $data['total_amount'],
            'items_count' => count($data['items']),
            'created_by' => $_SESSION['user_fullname'] ?? 'مستخدم'
        ])
    ]);
    
    // تأكيد المعاملة
    $pdo->commit();
    
    // إرجاع استجابة نجاح
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'تم إنشاء الطلب بنجاح',
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'payment_method' => $data['payment_method'],
        'total_amount' => $data['total_amount']
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // التراجع عن المعاملة في حالة الخطأ
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
