<?php
// api/add-product.php
// إضافة منتج جديد

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'طريقة الطلب غير مسموحة'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // التحقق من الحقول المطلوبة
    $required = ['name', 'description', 'price', 'category', 'stock'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new Exception("الحقل '{$field}' مطلوب");
        }
    }

    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }

    // إدراج المنتج
    $sql = "INSERT INTO products (name, description, price, category, stock, image, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        trim($data['name']),
        trim($data['description']),
        floatval($data['price']),
        trim($data['category']),
        intval($data['stock']),
        isset($data['image']) ? trim($data['image']) : null,
        'active'
    ]);

    if ($result) {
        $productId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إضافة المنتج بنجاح',
            'product_id' => $productId
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('فشل في إضافة المنتج');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
