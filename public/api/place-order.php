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

require_once '../config.php';

try {
    // قراءة البيانات
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('بيانات غير صالحة');
    }
    
    // التحقق من البيانات المطلوبة
    if (empty($data['customer_name']) || empty($data['customer_phone']) || 
        empty($data['customer_address']) || empty($data['items']) || 
        empty($data['total_amount'])) {
        throw new Exception('يرجى ملء جميع الحقول المطلوبة');
    }
    
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
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
        $stmt->execute([
            ':user_id' => 0, // 0 = زائر (guest order)
            ':customer_name' => $data['customer_name'],
            ':customer_phone' => $data['customer_phone'],
            ':customer_address' => $data['customer_address'],
            ':notes' => $data['notes'] ?? null,
            ':payment_method' => $data['payment_method'] ?? 'cod',
            ':total_amount' => $data['total_amount']
        ]);
        
        $orderId = $pdo->lastInsertId();
        
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
        
        foreach ($data['items'] as $item) {
            $stmtItem->execute([
                ':order_id' => $orderId,
                ':product_id' => $item['product_id'],
                ':quantity' => $item['quantity'],
                ':price' => $item['price']
            ]);
            
            // تحديث المخزون (تقليل الكمية)
            $sqlUpdateStock = "UPDATE products 
                              SET stock = stock - :quantity 
                              WHERE id = :product_id AND stock >= :quantity";
            $stmtStock = $pdo->prepare($sqlUpdateStock);
            $stmtStock->execute([
                ':quantity' => $item['quantity'],
                ':product_id' => $item['product_id']
            ]);
        }
        
        // تأكيد المعاملة
        $pdo->commit();
        
        // إرسال إشعار (اختياري - يمكن إرسال بريد إلكتروني هنا)
        // sendOrderNotification($orderId, $data);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تسجيل طلبك بنجاح',
            'order_id' => $orderId,
            'order_number' => str_pad($orderId, 5, '0', STR_PAD_LEFT)
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        // إلغاء المعاملة في حالة الخطأ
        $pdo->rollBack();
        throw $e;
    }
    
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

// دالة اختيارية لإرسال إشعارات
function sendOrderNotification($orderId, $orderData) {
    // يمكن إضافة كود لإرسال بريد إلكتروني أو رسالة SMS
    // مثال: إرسال بريد للعميل وللأدمن
    
    $to = 'info@eco-friendy.com';
    $subject = 'طلب جديد #' . $orderId;
    $message = "تم استلام طلب جديد:\n\n";
    $message .= "رقم الطلب: #" . $orderId . "\n";
    $message .= "العميل: " . $orderData['customer_name'] . "\n";
    $message .= "الهاتف: " . $orderData['customer_phone'] . "\n";
    $message .= "العنوان: " . $orderData['customer_address'] . "\n";
    $message .= "المبلغ: " . $orderData['total_amount'] . " د.أ\n";
    $message .= "طريقة الدفع: " . ($orderData['payment_method'] ?? 'غير محدد') . "\n";
    
    $headers = 'From: noreply@eco-friendy.com' . "\r\n" .
               'Content-Type: text/plain; charset=UTF-8';
    
    // mail($to, $subject, $message, $headers);
}
?>
