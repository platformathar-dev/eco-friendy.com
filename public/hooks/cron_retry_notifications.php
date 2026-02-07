<?php
/**
 * مهمة CRON لإعادة محاولة إرسال الإشعارات الفاشلة
 * CRON Job - Retry Failed Notifications
 * 
 * يتم تشغيل هذا الملف كل ساعة أو حسب الحاجة
 * 
 * لإضافة CRON Job في السيرفر:
 * crontab -e
 * ثم أضف السطر التالي:
 * 0 * * * * /usr/bin/php /path/to/your/project/cron_retry_notifications.php
 */

require_once __DIR__ . '/EmailNotificationSystem.php';
require_once __DIR__ . '/db_config.php';

echo "🔄 بدء عملية إعادة محاولة إرسال الإشعارات الفاشلة...\n";
echo "الوقت: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('-', 50) . "\n";

try {
    $emailSystem = new EmailNotificationSystem($conn);
    
    // إعادة محاولة إرسال الإشعارات الفاشلة (حتى 3 محاولات)
    $retriedCount = $emailSystem->retryFailedNotifications(3);
    
    echo "✅ تم إعادة محاولة إرسال {$retriedCount} إشعار\n";
    
    // عرض إحصائيات
    $stats = $conn->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM notifications
        GROUP BY status
    ");
    
    echo "\n📊 إحصائيات الإشعارات:\n";
    while ($row = $stats->fetch_assoc()) {
        $statusEmoji = [
            'sent' => '✅',
            'pending' => '⏳',
            'failed' => '❌'
        ];
        
        $emoji = $statusEmoji[$row['status']] ?? '📧';
        echo "{$emoji} {$row['status']}: {$row['count']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "\n";
    error_log("CRON Error: " . $e->getMessage());
}

echo "\n" . str_repeat('-', 50) . "\n";
echo "✅ اكتملت المهمة بنجاح\n";

// إغلاق الاتصال
$conn->close();
