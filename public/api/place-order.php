<?php
// api/place-order.php
// حفظ طلب جديد

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// التعامل مع طلبات OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// تفعيل تسجيل الأخطاء
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';

try {
    // قراءة البيانات
    $input = file_get_contents('php://input');
    
    // تسجيل البيانات المستلمة للتشخيص
    error_log("Received data: " . $input);
    
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('بيانات غير صالحة - JSON decode failed');
    }
    
    // التحقق من البيانات المطلوبة
    if (empty($data['customer_name'])) {
        throw new Exception('الاسم مطلوب');
    }
    
    if (empty($data['customer_phone'])) {
        throw new Exception('رقم الهاتف مطلوب');
    }
    
    if (empty($data['customer_address'])) {
        throw new Exception('العنوان مطلوب');
    }
    
    if (empty($data['items']) || !is_array($data['items']) || count($data['items']) == 0) {
        throw new Exception('يجب أن يحتوي الطلب على منتج واحد على الأقل');
    }
    
    if (empty($data['total_amount'])) {
        throw new Exception('المبلغ الإجمالي مطلوب');
    }
    
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    // تسجيل: بدء حفظ الطلب
    error_log("Starting order save process...");
    
    // بدء المعاملة
    $pdo->beginTransaction();
    
    try {
        // إدراج الطلب في جدول orders
        $sql = "INSERT INTO orders (
                    user_id,
                    customer_name,
                    customer_phone,
                    customer_address,
                    notes,
                    payment_method,
                    total_amount,
                    status,
                    created_at,
                    updated_at
                ) VALUES (
                    :user_id,
                    :customer_name,
                    :customer_phone,
                    :customer_address,
                    :notes,
                    :payment_method,
                    :total_amount,
                    'pending',
                    NOW(),
                    NOW()
                )";
        
        $stmt = $pdo->prepare($sql);
        
        $params = [
            ':user_id' => 0, // 0 = زائر (guest order)
            ':customer_name' => $data['customer_name'],
            ':customer_phone' => $data['customer_phone'],
            ':customer_address' => $data['customer_address'],
            ':notes' => isset($data['notes']) ? $data['notes'] : null,
            ':payment_method' => isset($data['payment_method']) ? $data['payment_method'] : 'cod',
            ':total_amount' => floatval($data['total_amount'])
        ];
        
        // تسجيل البارامترات
        error_log("Order params: " . json_encode($params));
        
        $result = $stmt->execute($params);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception('فشل إدراج الطلب: ' . $errorInfo[2]);
        }
        
        $orderId = $pdo->lastInsertId();
        
        if (!$orderId) {
            throw new Exception('فشل الحصول على رقم الطلب');
        }
        
        // تسجيل: تم إنشاء الطلب
        error_log("Order created with ID: " . $orderId);
        
        // إدراج عناصر الطلب في جدول order_items
        $sqlItem = "INSERT INTO order_items (
                        order_id,
                        product_id,
                        quantity,
                        price
                    ) VALUES (
                        :order_id,
                        :product_id,
                        :quantity,
                        :price
                    )";
        
        $stmtItem = $pdo->prepare($sqlItem);
        
        $itemCount = 0;
        foreach ($data['items'] as $item) {
            if (empty($item['product_id']) || empty($item['quantity']) || !isset($item['price'])) {
                throw new Exception('بيانات المنتج غير كاملة');
            }
            
            $itemParams = [
                ':order_id' => $orderId,
                ':product_id' => intval($item['product_id']),
                ':quantity' => intval($item['quantity']),
                ':price' => floatval($item['price'])
            ];
            
            // تسجيل بيانات المنتج
            error_log("Item params: " . json_encode($itemParams));
            
            $itemResult = $stmtItem->execute($itemParams);
            
            if (!$itemResult) {
                $errorInfo = $stmtItem->errorInfo();
                throw new Exception('فشل إدراج المنتج: ' . $errorInfo[2]);
            }
            
            $itemCount++;
            
            // تحديث المخزون (تقليل الكمية)
            $sqlUpdateStock = "UPDATE products 
                              SET stock = GREATEST(0, stock - :quantity)
                              WHERE id = :product_id";
            $stmtStock = $pdo->prepare($sqlUpdateStock);
            $stmtStock->execute([
                ':quantity' => intval($item['quantity']),
                ':product_id' => intval($item['product_id'])
            ]);
        }
        
        // تسجيل: تم إدراج المنتجات
        error_log("Inserted $itemCount items");
        
        // تأكيد المعاملة
        $pdo->commit();
        
        // تسجيل: نجاح العملية
        error_log("Order saved successfully!");
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تسجيل طلبك بنجاح',
            'order_id' => $orderId,
            'order_number' => str_pad($orderId, 5, '0', STR_PAD_LEFT),
            'items_count' => $itemCount
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        // إلغاء المعاملة في حالة الخطأ
        $pdo->rollBack();
        error_log("Transaction rolled back: " . $e->getMessage());
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("PDO Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("General Exception: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
