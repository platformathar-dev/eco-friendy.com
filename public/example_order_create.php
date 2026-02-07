<?php
/**
 * مثال على صفحة إنشاء طلب مع إرسال إيميل تأكيد تلقائي
 * Example: Create Order with Auto Email
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/hooks/new_order_hook.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $customerName = $_POST['customer_name'] ?? 'أحمد محمد';
    $customerEmail = $_POST['customer_email'] ?? 'customer@example.com';
    $customerPhone = $_POST['customer_phone'] ?? '0791234567';
    $customerAddress = $_POST['customer_address'] ?? 'عمان، الأردن';
    $productName = $_POST['product_name'] ?? 'منتج صديق للبيئة';
    $totalAmount = $_POST['total_amount'] ?? 50.00;
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    
    // إدخال الطلب في قاعدة البيانات
    $stmt = $conn->prepare("
        INSERT INTO orders 
        (customer_name, customer_email, customer_phone, customer_address, product_name, total_amount, payment_method, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->bind_param(
        "sssssds",
        $customerName,
        $customerEmail,
        $customerPhone,
        $customerAddress,
        $productName,
        $totalAmount,
        $paymentMethod
    );
    
    if ($stmt->execute()) {
        $orderId = $conn->insert_id;
        
        // توليد رقم الطلب
        $orderNumber = 'ORD-' . date('Y') . '-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
        $conn->query("UPDATE orders SET order_number = '{$orderNumber}' WHERE id = {$orderId}");
        
        echo "✅ تم إنشاء الطلب بنجاح!<br>";
        echo "📦 رقم الطلب: <strong>{$orderNumber}</strong><br>";
        echo "💰 المبلغ الإجمالي: <strong>{$totalAmount} دينار</strong><br>";
        
        // 🎯 إرسال إيميل تأكيد الطلب تلقائياً
        if (onNewOrder($orderId)) {
            echo "📧 تم إرسال إيميل تأكيد الطلب إلى: <strong>{$customerEmail}</strong><br>";
        } else {
            echo "⚠️ تم إنشاء الطلب ولكن فشل إرسال الإيميل<br>";
        }
        
        echo "<hr>";
        echo "<a href='example_order_update.php?order_id={$orderId}'>تحديث حالة الطلب</a>";
        
    } else {
        echo "❌ خطأ في إنشاء الطلب: " . $stmt->error;
    }
    
} else {
    ?>
    <!DOCTYPE html>
    <html dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>إنشاء طلب جديد</title>
        <style>
            body { font-family: Arial; padding: 20px; max-width: 500px; margin: 0 auto; }
            input, select, textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
            button { background: #3498db; color: white; padding: 15px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; }
            button:hover { background: #2980b9; }
            label { font-weight: bold; display: block; margin-top: 10px; }
        </style>
    </head>
    <body>
        <h2>🛒 إنشاء طلب جديد</h2>
        <form method="POST">
            <label>اسم العميل:</label>
            <input type="text" name="customer_name" value="أحمد محمد" required>
            
            <label>البريد الإلكتروني:</label>
            <input type="email" name="customer_email" value="customer@example.com" required>
            
            <label>رقم الهاتف:</label>
            <input type="text" name="customer_phone" value="0791234567" required>
            
            <label>العنوان:</label>
            <textarea name="customer_address" rows="3" required>عمان، الأردن</textarea>
            
            <label>اسم المنتج:</label>
            <input type="text" name="product_name" value="منتج صديق للبيئة" required>
            
            <label>المبلغ الإجمالي (دينار):</label>
            <input type="number" step="0.01" name="total_amount" value="50.00" required>
            
            <label>طريقة الدفع:</label>
            <select name="payment_method" required>
                <option value="cash">نقداً</option>
                <option value="card">بطاقة ائتمان</option>
                <option value="transfer">تحويل بنكي</option>
            </select>
            
            <button type="submit">إنشاء الطلب وإرسال إيميل التأكيد</button>
        </form>
    </body>
    </html>
    <?php
}
