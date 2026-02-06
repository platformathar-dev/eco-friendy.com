<?php
// api/place-order.php
// حفظ طلب جديد - نسخة محسّنة
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
    
    // تحضير البيانات
    $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
    $customer_name = trim($data['customer_name']);
    $customer_phone = trim($data['customer_phone']);
    $customer_address = trim($data['customer_address']);
    $shipping_address = isset($data['shipping_address']) ? trim($data['shipping_address']) : $customer_address;
    $notes = isset($data['notes']) ? trim($data['notes']) : '';
    $payment_method = isset($data['payment_method']) ? trim($data['payment_method']) : 'cod';
    $total_amount = floatval($data['total_amount']);
    $status = isset($data['status']) ? trim($data['status']) : 'pending';
    
    // إدراج الطلب - جميع الحقول
    $sql = "INSERT INTO orders 
            (user_id, customer_name, customer_phone, customer_address, shipping_address, 
             notes, payment_method, total_amount, status, created_at, updated_at) 
            VALUES 
            (:user_id, :customer_name, :customer_phone, :customer_address, :shipping_address, 
             :notes, :payment_method, :total_amount, :status, NOW(), NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':customer_name' => $customer_name,
        ':customer_phone' => $customer_phone,
        ':customer_address' => $customer_address,
        ':shipping_address' => $shipping_address,
        ':notes' => $notes,
        ':payment_method' => $payment_method,
        ':total_amount' => $total_amount,
        ':status' => $status
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    if (!$orderId) {
        throw new Exception('Failed to get order ID');
    }
    
    // إدراج المنتجات
    $sqlItem = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                VALUES (:order_id, :product_id, :quantity, :price)";
    $stmtItem = $pdo->prepare($sqlItem);
    
    foreach ($data['items'] as $item) {
        if (empty($item['product_id']) || empty($item['quantity']) || !isset($item['price'])) {
            throw new Exception('Invalid item data');
        }
        
        $stmtItem->execute([
            ':order_id' => $orderId,
            ':product_id' => intval($item['product_id']),
            ':quantity' => intval($item['quantity']),
            ':price' => floatval($item['price'])
        ]);
        
        // تحديث المخزون بطريقة آمنة
        $sqlUpdateStock = "UPDATE products 
                          SET stock = GREATEST(0, stock - :quantity) 
                          WHERE id = :product_id";
        $stmtStock = $pdo->prepare($sqlUpdateStock);
        $stmtStock->execute([
            ':quantity' => intval($item['quantity']),
            ':product_id' => intval($item['product_id'])
        ]);
    }
    
    // تأكيد المعاملة
    $pdo->commit();
    
    // إرسال الاستجابة
    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل طلبك بنجاح',
        'order_id' => $orderId,
        'order_number' => str_pad($orderId, 5, '0', STR_PAD_LEFT),
        'data' => [
            'user_id' => $user_id,
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'customer_address' => $customer_address,
            'shipping_address' => $shipping_address,
            'payment_method' => $payment_method,
            'total_amount' => $total_amount,
            'status' => $status
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // إلغاء المعاملة
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // إرسال رسالة الخطأ
    die(json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE));
}
?>
