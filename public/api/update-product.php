<?php
// api/update-product.php
// تحديث منتج

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once '../config.php';

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['id'])) {
        throw new Exception('معرف المنتج مطلوب');
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }

    // تحديث المنتج
    $sql = "UPDATE products SET 
            name = ?, 
            description = ?, 
            price = ?, 
            category = ?, 
            stock = ?,
            image = ?
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        trim($data['name']),
        trim($data['description']),
        floatval($data['price']),
        trim($data['category']),
        intval($data['stock']),
        isset($data['image']) ? trim($data['image']) : null,
        intval($data['id'])
    ]);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث المنتج بنجاح'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('فشل في تحديث المنتج');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
