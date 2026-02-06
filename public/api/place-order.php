<?php
// api/place-order.php
// تسجيل طلب جديد باسم الزبون

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
require_once '../config.php';

try {
    $pdo = getDBConnection();

    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }

    $input = json_decode(file_get_contents('php://input'), true);

    // جلب البيانات
    $customerName = trim($input['customer_name'] ?? '');
    $customerPhone = trim($input['customer_phone'] ?? '');
    $customerAddress = trim($input['customer_address'] ?? '');
    $notes = trim($input['notes'] ?? '');
    $items = $input['items'] ?? [];
    $totalAmount = $input['total_amount'] ?? 0;

    // التحقق من البيانات
    if (empty($customerName) || empty($customerPhone) || empty($customerAddress)) {
        echo json_encode([
            'success' => false,
            'message' => 'يرجى ملء جميع الحقول المطلوبة (الاسم، الهاتف، العنوان)'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if (empty($items)) {
        echo json_encode([
            'success' => false,
            'message' => 'يرجى إضافة منتج واحد على الأقل'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // التحقق من توفر المنتجات وحساب المجموع الحقيقي
    $calculatedTotal = 0;

    foreach ($items as $item) {
        $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$item['product_id']]);
        $product = $stmt->fetch();

        if (!$product) {
            echo json_encode([
                'success' => false,
                'message' => 'أحد المنتجات غير موجود أو غير متاح'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        if ($product['stock'] < $item['quantity']) {
            echo json_encode([
                'success' => false,
                'message' => "الكمية المطلوبة من \"{$product['name']}\" غير متوفرة. المتاح: {$product['stock']}"
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }

        $calculatedTotal += $product['price'] * $item['quantity'];
    }

    // بدء المعاملة
    $pdo->beginTransaction();

    // معرّف المستخدم (إن كان مسجلاً)
    $userId = $_SESSION['user_id'] ?? null;

    // إنشاء ملاحظة تتضمن بيانات الزبون
    $orderNotes = "الاسم: {$customerName}\nالهاتف: {$customerPhone}\nالعنوان: {$customerAddress}";
    if (!empty($notes)) {
        $orderNotes .= "\nملاحظات: {$notes}";
    }

    // إدخال الطلب
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, total_amount, status, notes, customer_name, customer_phone, customer_address, created_at)
        VALUES (?, ?, 'pending', ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $userId,
        $calculatedTotal,
        $orderNotes,
        $customerName,
        $customerPhone,
        $customerAddress
    ]);

    $orderId = $pdo->lastInsertId();

    // إدخال عناصر الطلب وتحديث المخزون
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES (?, ?, ?, ?)
    ");

    $stockStmt = $pdo->prepare("
        UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?
    ");

    foreach ($items as $item) {
        // جلب السعر الحقيقي من قاعدة البيانات
        $priceStmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $priceStmt->execute([$item['product_id']]);
        $realPrice = $priceStmt->fetch()['price'];

        // إدخال عنصر الطلب
        $itemStmt->execute([
            $orderId,
            $item['product_id'],
            $item['quantity'],
            $realPrice
        ]);

        // تقليل المخزون
        $stockStmt->execute([
            $item['quantity'],
            $item['product_id'],
            $item['quantity']
        ]);
    }

    // تأكيد المعاملة
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'تم تسجيل طلبك بنجاح',
        'order_id' => (int)$orderId,
        'customer_name' => $customerName
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
