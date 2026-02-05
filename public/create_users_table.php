<?php
require_once 'config.php';

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        die("❌ فشل الاتصال بقاعدة البيانات");
    }

    // إنشاء جدول المستخدمين
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fullname VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(15) NOT NULL UNIQUE,
        birthdate DATE NOT NULL,
        gender ENUM('male', 'female') NOT NULL,
        country VARCHAR(10) NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    
    echo "✅ تم إنشاء جدول المستخدمين بنجاح!";
    
} catch (PDOException $e) {
    echo "❌ خطأ: " . $e->getMessage();
}
?>
```

## 🚀 خطوات التشغيل

### 1️⃣ ارفع الملف
- ارفع ملف `create_users_table.php` إلى مجلد `public` في الاستضافة

### 2️⃣ شغّل الملف
- افتح المتصفح واذهب إلى:
```
https://your-domain.com/create_users_table.php
