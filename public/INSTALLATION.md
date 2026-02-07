# 🚀 دليل التثبيت السريع
## Quick Installation Guide

---

## ⚡ التثبيت في 5 خطوات

### 1️⃣ رفع الملفات

ارفع جميع الملفات إلى السيرفر الخاص بك في المسار المناسب:

```
/public_html/
├── EmailNotificationSystem.php
├── db_config.php
├── cron_retry_notifications.php
├── test_system.php
├── hooks/
│   ├── user_registration_hook.php
│   ├── new_order_hook.php
│   └── order_status_update_hook.php
└── examples/ (اختياري)
```

---

### 2️⃣ تحديث إعدادات قاعدة البيانات

افتح ملف `db_config.php` وحدث البيانات:

```php
define('DB_HOST', 'localhost');        // عنوان السيرفر
define('DB_USER', 'اسم_المستخدم');    // اسم المستخدم
define('DB_PASS', 'كلمة_المرور');      // كلمة المرور
define('DB_NAME', 'اسم_قاعدة_البيانات'); // اسم قاعدة البيانات
```

---

### 3️⃣ التأكد من جدول notifications

الجدول موجود مسبقاً في قاعدة البيانات (حسب الصورة المرفقة) ✅

إذا لم يكن موجوداً، استخدم هذا الاستعلام:

```sql
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `type` enum('new_order','completed','general') NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 4️⃣ دمج النظام في صفحاتك

#### أ) في صفحة التسجيل:

```php
require_once 'hooks/user_registration_hook.php';

// بعد إدخال المستخدم
if ($stmt->execute()) {
    $userId = $conn->insert_id;
    onUserRegistration($userId, $fullname, $email);
}
```

#### ب) في صفحة إنشاء الطلب:

```php
require_once 'hooks/new_order_hook.php';

// بعد إدخال الطلب
if ($stmt->execute()) {
    $orderId = $conn->insert_id;
    
    // توليد رقم الطلب
    $orderNumber = 'ORD-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
    $conn->query("UPDATE orders SET order_number = '{$orderNumber}' WHERE id = {$orderId}");
    
    onNewOrder($orderId);
}
```

#### ج) في صفحة تحديث حالة الطلب:

```php
require_once 'hooks/order_status_update_hook.php';

// عند تحديث الحالة
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $newStatus, $orderId);

if ($stmt->execute()) {
    onOrderStatusUpdate($orderId, $newStatus);
}
```

---

### 5️⃣ اختبار النظام

1. افتح الملف: `test_system.php`
2. جرب إرسال إيميل اختباري
3. تحقق من وصول الإيميل
4. راجع جدول `notifications` في قاعدة البيانات

---

## ✅ جاهز للعمل!

الآن النظام جاهز ويعمل تلقائياً عند:
- ✉️ تسجيل مستخدم جديد → إيميل ترحيب
- 📦 إنشاء طلب جديد → إيميل تأكيد
- 🔄 تحديث حالة الطلب → إيميل تحديث

---

## 🔧 إعدادات اختيارية

### إضافة CRON Job لإعادة المحاولات:

```bash
crontab -e
```

أضف هذا السطر:

```bash
0 * * * * /usr/bin/php /path/to/your/project/cron_retry_notifications.php
```

---

## 📞 في حالة وجود مشاكل

1. ✅ تحقق من إعدادات `db_config.php`
2. ✅ تأكد من إعدادات `mail/mailer.php`
3. ✅ راجع ملف `error_log` في السيرفر
4. ✅ تحقق من جدول `notifications` في قاعدة البيانات

---

## 🎉 مبروك!

تم تثبيت النظام بنجاح! 🚀
