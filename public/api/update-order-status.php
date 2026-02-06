<?php
// api/update-order-status.php
// تحديث حالة الطلب

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once '../config.php';

try {
    $pdo = getDBConnection();

    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = $input['order_id'] ?? null;
    $newStatus = $input['status'] ?? null;

    if (empty($orderId) || empty($newStatus)) {
        echo json_encode([
            'success' => false,
            'message' => 'معرّف الطلب والحالة الجديدة مطلوبان'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // التحقق من صحة الحالة
    $allowedStatuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (!in_array($newStatus, $allowedStatuses)) {
        echo json_encode([
            'success' => false,
            'message' => 'حالة غير صالحة'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // التحقق من وجود الطلب
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);

    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'الطلب غير موجود'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // تحديث الحالة
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);

    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث حالة الطلب بنجاح'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
