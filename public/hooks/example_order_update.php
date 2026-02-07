<?php
/**
 * مثال على صفحة تحديث حالة الطلب مع إرسال إيميل تلقائي
 * Example: Update Order Status with Auto Email
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/hooks/order_status_update_hook.php';

$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    die("❌ الرجاء تحديد رقم الطلب");
}

// جلب معلومات الطلب
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("❌ الطلب غير موجود");
}

// معالجة تحديث الحالة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $newStatus = $_POST['status'];
    
    // تحديث حالة الطلب
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $orderId);
    
    if ($stmt->execute()) {
        echo "✅ تم تحديث حالة الطلب بنجاح!<br>";
        echo "📦 رقم الطلب: <strong>{$order['order_number']}</strong><br>";
        echo "🔄 الحالة الجديدة: <strong>{$newStatus}</strong><br>";
        
        // 🎯 إرسال إيميل تحديث الحالة تلقائياً
        if (onOrderStatusUpdate($orderId, $newStatus)) {
            echo "📧 تم إرسال إيميل تحديث الحالة إلى: <strong>{$order['customer_email']}</strong><br>";
        } else {
            echo "⚠️ تم تحديث الحالة ولكن فشل إرسال الإيميل<br>";
        }
        
        echo "<hr>";
        echo "<a href='example_order_update.php?order_id={$orderId}'>العودة</a>";
        
        // تحديث بيانات الطلب
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        
    } else {
        echo "❌ خطأ في تحديث الحالة: " . $stmt->error;
    }
}

// عرض النموذج
?>
<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تحديث حالة الطلب</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 600px; margin: 0 auto; }
        .order-info { background: #ecf0f1; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .order-info h3 { margin-top: 0; color: #2c3e50; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #bdc3c7; }
        .info-label { font-weight: bold; color: #7f8c8d; }
        .info-value { color: #2c3e50; }
        select { width: 100%; padding: 15px; margin: 20px 0; border: 2px solid #3498db; border-radius: 5px; font-size: 16px; }
        button { background: #2ecc71; color: white; padding: 15px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; }
        button:hover { background: #27ae60; }
        .status-badge { padding: 5px 15px; border-radius: 20px; display: inline-block; font-weight: bold; }
        .status-pending { background: #f39c12; color: white; }
        .status-processing { background: #3498db; color: white; }
        .status-completed { background: #2ecc71; color: white; }
        .status-cancelled { background: #e74c3c; color: white; }
    </style>
</head>
<body>
    <h2>🔄 تحديث حالة الطلب</h2>
    
    <div class="order-info">
        <h3>معلومات الطلب</h3>
        
        <div class="info-row">
            <span class="info-label">رقم الطلب:</span>
            <span class="info-value"><?= $order['order_number'] ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">اسم العميل:</span>
            <span class="info-value"><?= $order['customer_name'] ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">البريد الإلكتروني:</span>
            <span class="info-value"><?= $order['customer_email'] ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">المبلغ:</span>
            <span class="info-value"><?= $order['total_amount'] ?> دينار</span>
        </div>
        
        <div class="info-row">
            <span class="info-label">المنتج:</span>
            <span class="info-value"><?= $order['product_name'] ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">الحالة الحالية:</span>
            <span class="info-value">
                <span class="status-badge status-<?= $order['status'] ?>">
                    <?= $order['status'] ?>
                </span>
            </span>
        </div>
        
        <div class="info-row">
            <span class="info-label">تاريخ الإنشاء:</span>
            <span class="info-value"><?= $order['created_at'] ?></span>
        </div>
    </div>
    
    <form method="POST">
        <label style="font-weight: bold; display: block; margin-bottom: 10px;">
            اختر الحالة الجديدة:
        </label>
        <select name="status" required>
            <option value="">-- اختر الحالة --</option>
            <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>⏳ قيد الانتظار</option>
            <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>🔄 قيد المعالجة</option>
            <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>✅ مكتمل</option>
            <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>❌ ملغي</option>
        </select>
        
        <button type="submit">تحديث الحالة وإرسال إيميل للعميل</button>
    </form>
    
    <div style="margin-top: 20px; text-align: center;">
        <a href="example_order_create.php" style="color: #3498db;">إنشاء طلب جديد</a>
    </div>
</body>
</html>
