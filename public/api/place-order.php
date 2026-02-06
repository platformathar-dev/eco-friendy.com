<?php
// api/place-order.php
// حفظ طلب جديد - نسخة مبسطة

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
error_reporting(E_ALL);

// التعامل مع OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// التحقق من POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE));
}

require_once '../config.php';

// قراءة البيانات
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// التحقق من البيانات
if (!$data) {
    die(json_encode(['success' => false, 'message' => 'No data received'], JSON_UNESCAPED_UNICODE));
}

if (empty($data['customer_name']) || empty($data['customer_phone']) || 
    empty($data['customer_address']) || empty($data['items']) || 
    empty($data['total_amount'])) {
    die(json_encode(['success' => false, 'message' => 'Missing required fields'], JSON_UNESCAPED_UNICODE));
}

try {
    // الاتصال بقاعدة البيانات
    $pdo = getDBConnection();
    
    if (!$pdo) {
        die(json_encode(['success' => false, 'message' => 'Database connection failed'], JSON_UNESCAPED_UNICODE));
    }
    
    // بدء المعاملة
    $pdo->beginTransaction();
    
    // إدراج الطلب
    $sql = "INSERT INTO orders 
            (user_id, customer_name, customer_phone, customer_address, notes, payment_method, total_amount, status, created_at, updated_at) 
            VALUES 
            (0, :name, :phone, :address, :notes, :payment, :total, 'pending', NOW(), NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $data['customer_name'],
        ':phone' => $data['customer_phone'],
        ':address' => $data['customer_address'],
        ':notes' => $data['notes'] ?? '',
        ':payment' => $data['payment_method'] ?? 'cod',
        ':total' => floatval($data['total_amount'])
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    if (!$orderId) {
        throw new Exception('Failed to get order ID');
    }
    
    // إدراج المنتجات
    $sqlItem = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)";
    $stmtItem = $pdo->prepare($sqlItem);
    
    foreach ($data['items'] as $item) {
        $stmtItem->execute([
            ':order_id' => $orderId,
            ':product_id' => intval($item['product_id']),
            ':quantity' => intval($item['quantity']),
            ':price' => floatval($item['price'])
        ]);
        
        // تحديث المخزون
        $pdo->exec("UPDATE products SET stock = GREATEST(0, stock - " . intval($item['quantity']) . ") WHERE id = " . intval($item['product_id']));
    }
    
    // تأكيد المعاملة
    $pdo->commit();
    
    // إرسال الاستجابة
    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل طلبك بنجاح',
        'order_id' => $orderId,
        'order_number' => str_pad($orderId, 5, '0', STR_PAD_LEFT)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // إلغاء المعاملة
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // إرسال رسالة الخطأ
    die(json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE));
}
?>
