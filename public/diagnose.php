<?php
/**
 * أداة تشخيص مشاكل إرسال البريد الإلكتروني
 * Email System Diagnostic Tool
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html dir='rtl'><head><meta charset='UTF-8'>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
    h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
    h2 { color: #34495e; margin-top: 30px; background: #ecf0f1; padding: 10px; border-radius: 5px; }
    .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0; }
    .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
    pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
    .test-box { border: 2px solid #3498db; padding: 20px; border-radius: 10px; margin: 20px 0; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { padding: 12px; text-align: right; border-bottom: 1px solid #ddd; }
    th { background: #3498db; color: white; }
    .button { background: #2ecc71; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 10px 5px; }
    .button:hover { background: #27ae60; }
</style></head><body><div class='container'>";

echo "<h1>🔍 أداة تشخيص نظام البريد الإلكتروني</h1>";
echo "<p style='color: #7f8c8d;'>هذه الأداة ستساعدك في اكتشاف سبب عدم إرسال الإيميلات</p>";

// =====================================================
// 1. فحص اتصال قاعدة البيانات
// =====================================================
echo "<h2>1️⃣ فحص اتصال قاعدة البيانات</h2>";

$dbConfigPath = __DIR__ . '/db_config.php';
if (file_exists($dbConfigPath)) {
    echo "<div class='success'>✅ ملف db_config.php موجود</div>";
    
    try {
        include_once $dbConfigPath;
        
        if (isset($conn) && $conn instanceof mysqli) {
            if ($conn->connect_error) {
                echo "<div class='error'>❌ خطأ في الاتصال: " . $conn->connect_error . "</div>";
            } else {
                echo "<div class='success'>✅ الاتصال بقاعدة البيانات ناجح</div>";
                
                // فحص وجود جدول notifications
                $result = $conn->query("SHOW TABLES LIKE 'notifications'");
                if ($result && $result->num_rows > 0) {
                    echo "<div class='success'>✅ جدول notifications موجود</div>";
                    
                    // عرض بعض الإحصائيات
                    $stats = $conn->query("SELECT COUNT(*) as total FROM notifications")->fetch_assoc();
                    echo "<div class='info'>📊 عدد السجلات في جدول notifications: <strong>{$stats['total']}</strong></div>";
                } else {
                    echo "<div class='error'>❌ جدول notifications غير موجود في قاعدة البيانات!</div>";
                    echo "<div class='warning'>💡 قم بإنشاء الجدول باستخدام الكود في ملف INSTALLATION.md</div>";
                }
            }
        } else {
            echo "<div class='error'>❌ متغير \$conn غير معرّف أو ليس من نوع mysqli</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ خطأ: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='error'>❌ ملف db_config.php غير موجود في المسار: {$dbConfigPath}</div>";
    echo "<div class='warning'>💡 تأكد من رفع الملف وتحديث المسار</div>";
}

// =====================================================
// 2. فحص ملف mailer.php
// =====================================================
echo "<h2>2️⃣ فحص ملف إرسال البريد (mailer.php)</h2>";

$mailerPath = __DIR__ . '/mail/mailer.php';
if (file_exists($mailerPath)) {
    echo "<div class='success'>✅ ملف mail/mailer.php موجود</div>";
    
    // فحص محتوى الملف
    $mailerContent = file_get_contents($mailerPath);
    
    if (strpos($mailerContent, 'function sendMail') !== false) {
        echo "<div class='success'>✅ دالة sendMail موجودة</div>";
    } else {
        echo "<div class='error'>❌ دالة sendMail غير موجودة في الملف</div>";
    }
    
    // عرض محتوى الملف
    echo "<div class='info'><strong>محتوى ملف mailer.php:</strong></div>";
    echo "<pre>" . htmlspecialchars($mailerContent) . "</pre>";
    
} else {
    echo "<div class='error'>❌ ملف mail/mailer.php غير موجود</div>";
    echo "<div class='warning'>💡 هذا الملف ضروري لإرسال الإيميلات. تأكد من رفعه في المسار: /mail/mailer.php</div>";
}

// =====================================================
// 3. اختبار إرسال بريد مباشر
// =====================================================
echo "<h2>3️⃣ اختبار إرسال بريد إلكتروني مباشر</h2>";

if (file_exists($mailerPath)) {
    include_once $mailerPath;
    
    if (function_exists('sendMail')) {
        echo "<div class='test-box'>";
        echo "<h3>📧 اختبار الإرسال</h3>";
        
        if (isset($_POST['test_email'])) {
            $testEmail = $_POST['test_email'];
            $testSubject = "اختبار النظام - " . date('Y-m-d H:i:s');
            $testMessage = "
            <div dir='rtl' style='font-family: Arial; padding: 20px;'>
                <h2 style='color: #2ecc71;'>✅ نجح الاختبار!</h2>
                <p>هذا بريد تجريبي من نظام الإشعارات.</p>
                <p>الوقت: <strong>" . date('Y-m-d H:i:s') . "</strong></p>
            </div>
            ";
            
            echo "<div class='info'>⏳ جاري إرسال بريد تجريبي إلى: <strong>{$testEmail}</strong></div>";
            
            try {
                $result = sendMail($testEmail, $testSubject, $testMessage);
                
                if ($result === true) {
                    echo "<div class='success'>✅ تم إرسال البريد بنجاح!</div>";
                    echo "<div class='info'>✉️ تحقق من صندوق البريد (وصندوق الرسائل غير المرغوب فيها)</div>";
                } else {
                    echo "<div class='error'>❌ فشل إرسال البريد</div>";
                    echo "<div class='warning'>📝 النتيجة: " . var_export($result, true) . "</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>❌ خطأ في الإرسال: " . $e->getMessage() . "</div>";
            }
        }
        
        echo "<form method='POST'>";
        echo "<p><strong>البريد الإلكتروني للاختبار:</strong></p>";
        echo "<input type='email' name='test_email' value='rezak.abazid@gmail.com' style='width: 100%; padding: 10px; border: 2px solid #3498db; border-radius: 5px; margin: 10px 0;' required>";
        echo "<button type='submit' class='button'>🚀 إرسال بريد تجريبي</button>";
        echo "</form>";
        echo "</div>";
        
    } else {
        echo "<div class='error'>❌ دالة sendMail غير متاحة</div>";
    }
}

// =====================================================
// 4. فحص ملفات النظام
// =====================================================
echo "<h2>4️⃣ فحص ملفات النظام</h2>";

$requiredFiles = [
    'EmailNotificationSystem.php' => __DIR__ . '/EmailNotificationSystem.php',
    'db_config.php' => __DIR__ . '/db_config.php',
    'mail/mailer.php' => __DIR__ . '/mail/mailer.php',
    'hooks/user_registration_hook.php' => __DIR__ . '/hooks/user_registration_hook.php',
    'hooks/new_order_hook.php' => __DIR__ . '/hooks/new_order_hook.php',
    'hooks/order_status_update_hook.php' => __DIR__ . '/hooks/order_status_update_hook.php',
];

echo "<table>";
echo "<tr><th>الملف</th><th>الحالة</th></tr>";
foreach ($requiredFiles as $name => $path) {
    $exists = file_exists($path);
    $status = $exists ? 
        "<span style='color: #2ecc71;'>✅ موجود</span>" : 
        "<span style='color: #e74c3c;'>❌ غير موجود</span>";
    echo "<tr><td>{$name}</td><td>{$status}</td></tr>";
}
echo "</table>";

// =====================================================
// 5. فحص إعدادات PHP
// =====================================================
echo "<h2>5️⃣ فحص إعدادات PHP</h2>";

echo "<table>";
echo "<tr><th>الإعداد</th><th>القيمة</th></tr>";
echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
echo "<tr><td>display_errors</td><td>" . ini_get('display_errors') . "</td></tr>";
echo "<tr><td>error_reporting</td><td>" . error_reporting() . "</td></tr>";
echo "<tr><td>mail() function</td><td>" . (function_exists('mail') ? '✅ متاحة' : '❌ غير متاحة') . "</td></tr>";
echo "</table>";

// =====================================================
// 6. فحص سجل الإشعارات
// =====================================================
if (isset($conn) && !$conn->connect_error) {
    echo "<h2>6️⃣ آخر محاولات الإرسال</h2>";
    
    $recent = $conn->query("
        SELECT id, email, type, subject, status, attempts, created_at, error_message 
        FROM notifications 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    
    if ($recent && $recent->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>البريد</th><th>النوع</th><th>الحالة</th><th>المحاولات</th><th>التاريخ</th><th>الخطأ</th></tr>";
        
        while ($row = $recent->fetch_assoc()) {
            $statusColor = [
                'sent' => '#2ecc71',
                'pending' => '#f39c12',
                'failed' => '#e74c3c'
            ];
            $color = $statusColor[$row['status']] ?? '#95a5a6';
            
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['type']}</td>";
            echo "<td><span style='color: {$color}; font-weight: bold;'>{$row['status']}</span></td>";
            echo "<td>{$row['attempts']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "<td>" . ($row['error_message'] ?? '-') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<div class='warning'>⚠️ لا توجد محاولات إرسال في السجل</div>";
    }
}

// =====================================================
// 7. الحلول المقترحة
// =====================================================
echo "<h2>7️⃣ الحلول المقترحة</h2>";

echo "<div class='info'>";
echo "<h3>✅ قائمة التحقق:</h3>";
echo "<ol>";
echo "<li>تأكد من تحديث بيانات قاعدة البيانات في <code>db_config.php</code></li>";
echo "<li>تأكد من وجود ملف <code>mail/mailer.php</code> وأنه يعمل بشكل صحيح</li>";
echo "<li>جرب إرسال بريد تجريبي من الأعلى ⬆️</li>";
echo "<li>تحقق من إعدادات SMTP في ملف <code>mailer.php</code></li>";
echo "<li>تأكد من أن الإيميلات لا تذهب إلى مجلد SPAM</li>";
echo "<li>راجع ملف <code>error_log</code> في السيرفر</li>";
echo "</ol>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ أخطاء شائعة:</h3>";
echo "<ul>";
echo "<li><strong>SMTP غير مُعد بشكل صحيح:</strong> تحقق من username, password, host, port</li>";
echo "<li><strong>الإيميلات محظورة من السيرفر:</strong> اتصل بمزود الاستضافة</li>";
echo "<li><strong>دالة mail() معطلة:</strong> استخدم PHPMailer أو SMTP بدلاً من mail() المدمجة</li>";
echo "<li><strong>المسارات خاطئة:</strong> تأكد من رفع الملفات في المكان الصحيح</li>";
echo "</ul>";
echo "</div>";

// =====================================================
// 8. اختبار مباشر من test-mail.php
// =====================================================
echo "<h2>8️⃣ اختبار من test-mail.php الموجود</h2>";

$testMailPath = dirname(__DIR__) . '/public/test-mail.php';
echo "<div class='info'>";
echo "<p>حسب الصورة، لديك ملف test-mail.php يعمل بنجاح:</p>";
echo "<code>https://eco-friendy.com/public/test-mail.php</code>";
echo "<p>هذا يعني أن نظام الإرسال يعمل! ✅</p>";
echo "<p><strong>المشكلة المحتملة:</strong> عدم ربط النظام بشكل صحيح مع صفحات التسجيل/الطلبات</p>";
echo "</div>";

echo "</div></body></html>";
?>
