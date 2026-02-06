<?php
// api/update-order-status.php
// تحديث حالة الطلب (للأدمن فقط)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

// التعامل مع طلبات OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// التحقق من صلاحيات الأدمن
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح لك بهذه العملية'
    ], JSON_UNESCAPED_UNICODE);
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
    if (empty($data['order_id']) || empty($data['status'])) {
        throw new Exception('يرجى تحديد رقم الطلب والحالة الجديدة');
    }
    
    $orderId = $data['order_id'];
    $newStatus = $data['status'];
    
    // التحقق من صحة الحالة
    $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        throw new Exception('حالة غير صالحة');
    }
    
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    // تحديث حالة الطلب
    $sql = "UPDATE orders 
            SET status = :status,
                updated_at = NOW()
            WHERE id = :order_id";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':status' => $newStatus,
        ':order_id' => $orderId
    ]);
    
    if ($result) {
        // جلب بيانات الطلب المحدث
        $sqlOrder = "SELECT * FROM orders WHERE id = :order_id";
        $stmtOrder = $pdo->prepare($sqlOrder);
        $stmtOrder->execute([':order_id' => $orderId]);
        $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث حالة الطلب بنجاح',
            'order' => $order
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('فشل تحديث حالة الطلب');
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
?>
