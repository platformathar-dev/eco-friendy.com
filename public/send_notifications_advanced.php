<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';       // الاتصال بقاعدة البيانات
require_once __DIR__ . '/mail/mailer.php';  // دالة sendMail

// ==============================
// إضافة إشعار جديد تلقائيًا من الطلبات
// ==============================
$stmt = $conn->prepare("SELECT * FROM orders WHERE status = 'pending'");
$stmt->execute();
$newOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($newOrders as $order) {
    $subject = "تم استلام طلبك رقم #{$order['order_number']}";
    $message = "
        <h2>مرحباً {$order['customer_name']} 👋</h2>
        <p>شكراً لطلبك معنا!</p>
        <p>تفاصيل الطلب:</p>
        <ul>
            <li>رقم الطلب: {$order['order_number']}</li>
            <li>المنتج: {$order['product_name']}</li>
            <li>المبلغ: {$order['total_amount']} USD</li>
        </ul>
    ";

    // تسجيل الإشعار في جدول notifications
    $insert = $conn->prepare("
        INSERT INTO notifications (user_id, order_id, email, type, subject, message)
        VALUES (?, ?, ?, 'new_order', ?, ?)
    ");
    $insert->execute([$order['user_id'], $order['id'], $order['customer_email'], $subject, $message]);

    // تحديث حالة الطلب لتجنب الإضافة المتكررة
    $update = $conn->prepare("UPDATE orders SET status = 'processing' WHERE id = ?");
    $update->execute([$order['id']]);
}

// ==============================
// إشعارات الطلب المكتمل
// ==============================
$stmt = $conn->prepare("SELECT * FROM orders WHERE status = 'completed' AND notified_completed IS NULL");
$stmt->execute();
$completedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($completedOrders as $order) {
    $subject = "تم تسليم طلبك رقم #{$order['order_number']}";
    $message = "
        <h2>مرحباً {$order['customer_name']} 🎉</h2>
        <p>تم تسليم طلبك بنجاح. شكراً لاختيارك Eco Friendy!</p>
    ";

    $insert = $conn->prepare("
        INSERT INTO notifications (user_id, order_id, email, type, subject, message)
        VALUES (?, ?, ?, 'completed', ?, ?)
    ");
    $insert->execute([$order['user_id'], $order['id'], $order['customer_email'], $subject, $message]);

    $update = $conn->prepare("UPDATE orders SET notified_completed = 1 WHERE id = ?");
    $update->execute([$order['id']]);
}

// ==============================
// إشعارات عامة لجميع المستخدمين النشطين
// ==============================
$stmt = $conn->prepare("SELECT * FROM users WHERE status = 'active'");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $subject = "تحديثات مهمة من Eco Friendy";
    $message = "
        <h2>مرحباً {$user['fullname']} 👋</h2>
        <p>هذه رسالة إعلامية لتحديثات وعروضنا الجديدة.</p>
    ";

    $insert = $conn->prepare("
        INSERT INTO notifications (user_id, email, type, subject, message)
        VALUES (?, ?, 'general', ?, ?)
    ");
    $insert->execute([$user['id'], $user['email'], $subject, $message]);
}

// ==============================
// إرسال الإشعارات المعلقة (pending)
// ==============================
$stmt = $conn->prepare("SELECT * FROM notifications WHERE status = 'pending'");
$stmt->execute();
$pendingNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($pendingNotifications as $notif) {
    $success = sendMail($notif['email'], $notif['subject'], $notif['message']);

    if ($success) {
        $update = $conn->prepare("
            UPDATE notifications 
            SET status='sent', sent_at=NOW(), attempts=attempts+1 
            WHERE id = ?
        ");
        $update->execute([$notif['id']]);
        echo "✅ تم إرسال: {$notif['email']}<br>";
    } else {
        $update = $conn->prepare("
            UPDATE notifications 
            SET status='failed', attempts=attempts+1, error_message='SMTP/PHPMailer error'
            WHERE id = ?
        ");
        $update->execute([$notif['id']]);
        echo "❌ فشل إرسال: {$notif['email']}<br>";
    }
}

echo "<br>✅ جميع الإشعارات تمت معالجتها.";
