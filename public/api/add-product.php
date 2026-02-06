<?php
// api/add-product.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once '../config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) throw new Exception('فشل الاتصال بقاعدة البيانات');

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? 0;
    $category = trim($_POST['category'] ?? '');
    $stock = $_POST['stock'] ?? 0;
    $status = $_POST['status'] ?? 'active';

    if (empty($name) || empty($description) || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'يرجى ملء جميع الحقول المطلوبة'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if ($price <= 0) {
        echo json_encode(['success' => false, 'message' => 'السعر يجب أن يكون أكبر من صفر'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // ==================== معالجة رفع الصورة ====================
    $imagePath = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {

        $file = $_FILES['image'];

        // التحقق من نوع الملف
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'نوع الملف غير مدعوم'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'حجم الصورة كبير جداً. الحد الأقصى 2MB'], JSON_UNESCAPED_UNICODE);
            exit();
        }

        // ===== تحديد مسار الرفع =====
        // طريقة 1: من DOCUMENT_ROOT
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
        $uploadDir = $docRoot . '/uploads/products/';

        // طريقة 2: إذا فشلت الأولى، استخدم مسار نسبي من ملف API
        if (empty($docRoot) || $docRoot === '/') {
            $uploadDir = dirname(__DIR__) . '/uploads/products/';
        }

        // إنشاء المجلد
        if (!is_dir($uploadDir)) {
            if (!@mkdir($uploadDir, 0755, true)) {
                // محاولة بصلاحيات أعلى
                @mkdir($uploadDir, 0777, true);
            }
        }

        if (!is_dir($uploadDir)) {
            echo json_encode([
                'success' => false,
                'message' => 'فشل في إنشاء مجلد الصور. أنشئ المجلد يدوياً: uploads/products/ وأعطه صلاحيات 755'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        // التحقق من قابلية الكتابة
        if (!is_writable($uploadDir)) {
            @chmod($uploadDir, 0755);
            if (!is_writable($uploadDir)) {
                @chmod($uploadDir, 0777);
            }
        }

        if (!is_writable($uploadDir)) {
            echo json_encode([
                'success' => false,
                'message' => 'مجلد الصور غير قابل للكتابة. غيّر صلاحيات uploads/products/ إلى 755 أو 777'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        // تحديد الامتداد
        $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
        $extension = $ext[$fileType] ?? 'jpg';

        $fileName = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $fullPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            $imagePath = 'uploads/products/' . $fileName;

            // التأكد من أن الملف تم حفظه فعلاً
            if (!file_exists($fullPath)) {
                echo json_encode(['success' => false, 'message' => 'تم الرفع لكن الملف غير موجود'], JSON_UNESCAPED_UNICODE);
                exit();
            }
        } else {
            $err = error_get_last();
            echo json_encode([
                'success' => false,
                'message' => 'فشل في نقل الصورة. تأكد من صلاحيات المجلد. ' . ($err ? $err['message'] : '')
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'حجم الملف أكبر من upload_max_filesize في php.ini',
            UPLOAD_ERR_FORM_SIZE => 'حجم الملف أكبر من الحد المسموح',
            UPLOAD_ERR_PARTIAL => 'تم رفع جزء فقط',
            UPLOAD_ERR_NO_TMP_DIR => 'مجلد مؤقت غير موجود',
            UPLOAD_ERR_CANT_WRITE => 'فشل في الكتابة',
            UPLOAD_ERR_EXTENSION => 'إضافة PHP أوقفت الرفع'
        ];
        $code = $_FILES['image']['error'];
        echo json_encode([
            'success' => false,
            'message' => 'خطأ رفع: ' . ($errors[$code] ?? "رمز $code")
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // ==================== إدخال المنتج ====================
    $stmt = $pdo->prepare("
        INSERT INTO products (name, description, price, category, stock, image, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([$name, $description, $price, $category, (int)$stock, $imagePath, $status]);

    echo json_encode([
        'success' => true,
        'message' => 'تم إضافة المنتج بنجاح',
        'product_id' => $pdo->lastInsertId(),
        'image' => $imagePath
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
