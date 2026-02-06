<?php
// api/delete-product.php
// حذف منتج

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
    $productId = $input['product_id'] ?? null;

    if (empty($productId)) {
        echo json_encode([
            'success' => false,
            'message' => 'معرّف المنتج مطلوب'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // جلب بيانات المنتج لحذف الصورة
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'المنتج غير موجود'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // حذف الصورة من السيرفر
    if (!empty($product['image'])) {
        $imagePath = '../' . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    // حذف المنتج من قاعدة البيانات
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$productId]);

    echo json_encode([
        'success' => true,
        'message' => 'تم حذف المنتج بنجاح'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
