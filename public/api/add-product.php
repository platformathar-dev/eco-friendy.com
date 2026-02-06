<?php
// api/add-product.php
// إضافة منتج جديد مع رفع صورة

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

    // جلب البيانات من FormData
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? 0;
    $category = trim($_POST['category'] ?? '');
    $stock = $_POST['stock'] ?? 0;
    $status = $_POST['status'] ?? 'active';

    // التحقق من البيانات
    if (empty($name) || empty($description) || empty($category)) {
        echo json_encode([
            'success' => false,
            'message' => 'يرجى ملء جميع الحقول المطلوبة'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if ($price <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'السعر يجب أن يكون أكبر من صفر'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // معالجة رفع الصورة
    $imagePath = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];

        // التحقق من نوع الملف
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode([
                'success' => false,
                'message' => 'نوع الملف غير مدعوم. الأنواع المسموحة: JPG, PNG, WEBP, GIF'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        // التحقق من الحجم (2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode([
                'success' => false,
                'message' => 'حجم الصورة كبير جداً. الحد الأقصى 2MB'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        // إنشاء مجلد الصور إن لم يكن موجوداً
        $uploadDir = '../uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // إنشاء اسم فريد للملف
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'product_' . time() . '_' . uniqid() . '.' . $extension;
        $fullPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            $imagePath = 'uploads/products/' . $fileName;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'فشل في رفع الصورة'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    // إدخال المنتج في قاعدة البيانات
    $stmt = $pdo->prepare("
        INSERT INTO products (name, description, price, category, stock, image, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $name,
        $description,
        $price,
        $category,
        (int)$stock,
        $imagePath,
        $status
    ]);

    $productId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'تم إضافة المنتج بنجاح',
        'product_id' => $productId
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
