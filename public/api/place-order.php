<?php
// api/place-order.php - إصدار محدث يدعم المستخدمين المسجلين والزوار
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// معالجة طلبات OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
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

require_once '../config.php';

try {
    // قراءة البيانات من الطلب
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('لم يتم استلام بيانات صحيحة');
    }
    
    // التحقق من الحقول المطلوبة
    $requiredFields = ['customer_name', 'customer_phone', 'customer_address', 'payment_method', 'items', 'total_amount'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("الحقل المطلوب مفقود: {$field}");
        }
    }
    
    // الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    // بدء المعاملة
    $pdo->beginTransaction();
    
    // معالجة user_id - دعم كلاً من المستخدمين المسجلين والزوار
    $userId = null;
    if (isset($data['user_id']) && $data['user_id'] > 0) {
        // مستخدم مسجل - التحقق من وجوده
        $checkUser = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $checkUser->execute([$data['user_id']]);
        if ($checkUser->fetch()) {
            $userId = $data['user_id'];
        }
    }
    // إذا لم يكن هناك user_id صالح، سيبقى NULL للزوار
    
    // إنشاء رقم طلب فريد
    $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // إدخال الطلب في قاعدة البيانات
    $sql = "INSERT INTO orders (
        order_number,
        user_id,
        customer_name,
        customer_phone,
        customer_address,
        shipping_address,
        notes,
        payment_method,
        status,
        total_amount,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $orderNumber,
        $userId, // سيكون NULL للزوار
        $data['customer_name'],
        $data['customer_phone'],
        $data['customer_address'],
        $data['shipping_address'] ?? $data['customer_address'],
        $data['notes'] ?? '',
        $data['payment_method'],
        $data['status'] ?? 'pending',
        $data['total_amount']
    ]);
    
    // الحصول على معرف الطلب
    $orderId = $pdo->lastInsertId();
    
    // إدخال عناصر الطلب
    if (!isset($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
        throw new Exception('لا توجد عناصر في الطلب');
    }
    
    $itemSql = "INSERT INTO order_items (
        order_id,
        product_id,
        quantity,
        price,
        subtotal
    ) VALUES (?, ?, ?, ?, ?)";
    
    $itemStmt = $pdo->prepare($itemSql);
    
    foreach ($data['items'] as $item) {
        if (!isset($item['product_id']) || !isset($item['quantity']) || !isset($item['price'])) {
            throw new Exception('بيانات العنصر غير كاملة');
        }
        
        $subtotal = $item['quantity'] * $item['price'];
        
        $itemStmt->execute([
            $orderId,
            $item['product_id'],
            $item['quantity'],
            $item['price'],
            $subtotal
        ]);
    }
    
    // تأكيد المعاملة
    $pdo->commit();
    
    // إرجاع استجابة نجاح
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'تم إنشاء الطلب بنجاح',
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'user_type' => $userId ? 'registered' : 'guest'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // التراجع عن المعاملة في حالة الخطأ
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => $e->getTrace()
    ], JSON_UNESCAPED_UNICODE);
}
?>
