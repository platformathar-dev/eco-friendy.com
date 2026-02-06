<?php
// api/update-product.php
// تعديل منتج مع رفع صورة جديدة

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
    $productId = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? 0;
    $category = trim($_POST['category'] ?? '');
    $stock = $_POST['stock'] ?? 0;
    $status = $_POST['status'] ?? 'active';
    $oldImage = $_POST['old_image'] ?? '';

    // التحقق من البيانات
    if (empty($productId)) {
        echo json_encode([
            'success' => false,
            'message' => 'معرّف المنتج مطلوب'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

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

    // التحقق من وجود المنتج
    $checkStmt = $pdo->prepare("SELECT id, image FROM products WHERE id = ?");
    $checkStmt->execute([$productId]);
    $existingProduct = $checkStmt->fetch();

    if (!$existingProduct) {
        echo json_encode([
            'success' => false,
            'message' => 'المنتج غير موجود'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // معالجة رفع الصورة الجديدة
    $imagePath = $existingProduct['image']; // الاحتفاظ بالصورة القديمة

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

        // إنشاء مجلد الصور
        $uploadDir = '../uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // إنشاء اسم فريد
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'product_' . time() . '_' . uniqid() . '.' . $extension;
        $fullPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            // حذف الصورة القديمة إن وجدت
            if (!empty($existingProduct['image'])) {
                $oldPath = '../' . $existingProduct['image'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $imagePath = 'uploads/products/' . $fileName;
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'فشل في رفع الصورة'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }

    // تحديث المنتج
    $stmt = $pdo->prepare("
        UPDATE products 
        SET name = ?, description = ?, price = ?, category = ?, stock = ?, image = ?, status = ?, updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $name,
        $description,
        $price,
        $category,
        (int)$stock,
        $imagePath,
        $status,
        $productId
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'تم تعديل المنتج بنجاح'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
