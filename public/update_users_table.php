<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        die("❌ فشل الاتصال بقاعدة البيانات");
    }

    echo "<h2>🔄 جاري تحديث جدول المستخدمين...</h2>";

    // التحقق من وجود حقل status
    try {
        $pdo->query("SELECT status FROM users LIMIT 1");
        echo "✅ حقل status موجود بالفعل<br>";
    } catch (PDOException $e) {
        // إضافة حقل status
        $sql1 = "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive', 'banned') DEFAULT 'active' AFTER password";
        $pdo->exec($sql1);
        echo "✅ تم إضافة حقل status<br>";
    }

    // التحقق من وجود حقل last_login
    try {
        $pdo->query("SELECT last_login FROM users LIMIT 1");
        echo "✅ حقل last_login موجود بالفعل<br>";
    } catch (PDOException $e) {
        // إضافة حقل last_login
        $sql2 = "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER updated_at";
        $pdo->exec($sql2);
        echo "✅ تم إضافة حقل last_login<br>";
    }

    // تحديث جميع المستخدمين الحاليين ليكونوا نشطين
    $pdo->exec("UPDATE users SET status = 'active' WHERE status IS NULL OR status = ''");
    echo "✅ تم تفعيل جميع المستخدمين الحاليين<br>";

    echo "<br><h3 style='color: green;'>🎉 تم تحديث جدول المستخدمين بنجاح!</h3>";
    echo "<p><strong>الآن احذف هذا الملف من السيرفر!</strong></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ خطأ: " . $e->getMessage() . "</h3>";
}
?>
```

**شغّله:**
```
https://eco-friendy.com/update_users_table.php
