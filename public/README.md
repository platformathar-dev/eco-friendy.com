# 📧 نظام الإشعارات البريدية التلقائية
## Automatic Email Notification System

نظام متكامل لإرسال الإيميلات التلقائية عند الأحداث المهمة في موقع Eco-Friendly

---

## 📋 جدول المحتويات

1. [المميزات](#-المميزات)
2. [متطلبات التشغيل](#-متطلبات-التشغيل)
3. [التثبيت](#-التثبيت)
4. [الاستخدام](#-الاستخدام)
5. [الملفات المضمنة](#-الملفات-المضمنة)
6. [أمثلة الاستخدام](#-أمثلة-الاستخدام)
7. [الصيانة](#-الصيانة)

---

## ✨ المميزات

### 1️⃣ إرسال إيميل ترحيب عند التسجيل
- يتم إرسال إيميل ترحيبي احترافي فور إنشاء حساب جديد
- تصميم جذاب وصديق للبيئة
- روابط للبدء في التسوق

### 2️⃣ إرسال إيميل تأكيد الطلب
- إشعار فوري عند إنشاء طلب جديد
- يحتوي على تفاصيل الطلب الكاملة
- رقم الطلب للمتابعة

### 3️⃣ إرسال إيميل تحديث حالة الطلب
- إشعار تلقائي عند تغيير حالة الطلب
- تصاميم مختلفة حسب الحالة (معالجة، مكتمل، ملغي)
- منع الإشعارات المكررة

### 4️⃣ سجل كامل للإشعارات
- حفظ جميع الإشعارات في جدول `notifications`
- تتبع حالة الإرسال (معلق، مرسل، فشل)
- عدد المحاولات ورسائل الأخطاء

### 5️⃣ إعادة محاولة الإشعارات الفاشلة
- نظام CRON لإعادة المحاولة التلقائية
- حد أقصى 3 محاولات
- تقارير مفصلة

---

## 🔧 متطلبات التشغيل

- PHP 7.4 أو أحدث
- MySQL 5.7 أو أحدث
- نظام إرسال البريد الإلكتروني (PHPMailer أو مكتبة مشابهة)
- خادم يدعم CRON Jobs (اختياري)

---

## 📦 التثبيت

### 1. رفع الملفات

قم برفع جميع الملفات إلى مجلد مشروعك:

```
/public_html/
├── EmailNotificationSystem.php
├── db_config.php
├── cron_retry_notifications.php
├── hooks/
│   ├── user_registration_hook.php
│   ├── new_order_hook.php
│   └── order_status_update_hook.php
├── mail/
│   └── mailer.php (ملف الإرسال الخاص بك)
└── examples/
    ├── example_registration.php
    ├── example_order_create.php
    └── example_order_update.php
```

### 2. إعداد قاعدة البيانات

قم بتحديث معلومات الاتصال في `db_config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database_name');
```

### 3. التحقق من جدول الإشعارات

تأكد من وجود جدول `notifications` في قاعدة البيانات (موجود مسبقاً حسب الصورة المرفقة)

---

## 🚀 الاستخدام

### 1️⃣ إرسال إيميل ترحيب عند التسجيل

في ملف التسجيل الخاص بك، أضف هذا الكود بعد نجاح إنشاء المستخدم:

```php
require_once 'hooks/user_registration_hook.php';

// بعد إدخال المستخدم في قاعدة البيانات
if ($stmt->execute()) {
    $userId = $conn->insert_id;
    
    // إرسال إيميل الترحيب
    onUserRegistration($userId, $fullname, $email);
}
```

### 2️⃣ إرسال إيميل تأكيد الطلب

في ملف إنشاء الطلب، أضف:

```php
require_once 'hooks/new_order_hook.php';

// بعد إدخال الطلب في قاعدة البيانات
if ($stmt->execute()) {
    $orderId = $conn->insert_id;
    
    // توليد رقم الطلب
    $orderNumber = 'ORD-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
    $conn->query("UPDATE orders SET order_number = '{$orderNumber}' WHERE id = {$orderId}");
    
    // إرسال إيميل التأكيد
    onNewOrder($orderId);
}
```

### 3️⃣ إرسال إيميل تحديث الحالة

في ملف تحديث حالة الطلب، أضف:

```php
require_once 'hooks/order_status_update_hook.php';

// عند تحديث حالة الطلب
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $newStatus, $orderId);

if ($stmt->execute()) {
    // إرسال إيميل التحديث
    onOrderStatusUpdate($orderId, $newStatus);
}
```

---

## 📁 الملفات المضمنة

### الملفات الأساسية

| الملف | الوصف |
|-------|-------|
| `EmailNotificationSystem.php` | الفئة الرئيسية لنظام الإشعارات |
| `db_config.php` | إعدادات الاتصال بقاعدة البيانات |
| `cron_retry_notifications.php` | مهمة CRON لإعادة المحاولات |

### Hooks (الربط التلقائي)

| الملف | الوصف |
|-------|-------|
| `user_registration_hook.php` | Hook لإرسال إيميل الترحيب |
| `new_order_hook.php` | Hook لإرسال إيميل الطلب الجديد |
| `order_status_update_hook.php` | Hook لإرسال إيميل تحديث الحالة |

### الأمثلة التطبيقية

| الملف | الوصف |
|-------|-------|
| `example_registration.php` | مثال على صفحة التسجيل |
| `example_order_create.php` | مثال على صفحة إنشاء طلب |
| `example_order_update.php` | مثال على صفحة تحديث حالة الطلب |

---

## 💡 أمثلة الاستخدام

### مثال 1: تسجيل مستخدم جديد

```php
// ملف: register.php
require_once 'hooks/user_registration_hook.php';

$fullname = "محمد أحمد";
$email = "mohammad@example.com";

// إدخال المستخدم
$stmt = $conn->prepare("INSERT INTO users (fullname, email, ...) VALUES (?, ?, ...)");
$stmt->execute();
$userId = $conn->insert_id;

// إرسال إيميل الترحيب تلقائياً
onUserRegistration($userId, $fullname, $email);
```

### مثال 2: إنشاء طلب جديد

```php
// ملف: create_order.php
require_once 'hooks/new_order_hook.php';

// إنشاء الطلب
$stmt = $conn->prepare("INSERT INTO orders (...) VALUES (...)");
$stmt->execute();
$orderId = $conn->insert_id;

// توليد رقم الطلب
$orderNumber = 'ORD-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
$conn->query("UPDATE orders SET order_number = '{$orderNumber}' WHERE id = {$orderId}");

// إرسال إيميل التأكيد تلقائياً
onNewOrder($orderId);
```

### مثال 3: تحديث حالة الطلب

```php
// ملف: update_order_status.php
require_once 'hooks/order_status_update_hook.php';

$orderId = 123;
$newStatus = 'completed'; // pending, processing, completed, cancelled

// تحديث الحالة
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->bind_param("si", $newStatus, $orderId);
$stmt->execute();

// إرسال إيميل التحديث تلقائياً
onOrderStatusUpdate($orderId, $newStatus);
```

---

## 🔧 الصيانة

### 1. إعداد CRON Job

لإعادة محاولة إرسال الإشعارات الفاشلة تلقائياً كل ساعة:

```bash
# تحرير CRON
crontab -e

# إضافة هذا السطر (كل ساعة)
0 * * * * /usr/bin/php /path/to/your/project/cron_retry_notifications.php

# أو كل 30 دقيقة
*/30 * * * * /usr/bin/php /path/to/your/project/cron_retry_notifications.php
```

### 2. مراقبة الإشعارات

استعلام SQL لمراقبة حالة الإشعارات:

```sql
-- إحصائيات الإشعارات
SELECT 
    status,
    COUNT(*) as count,
    DATE(created_at) as date
FROM notifications
GROUP BY status, DATE(created_at)
ORDER BY date DESC;

-- الإشعارات الفاشلة
SELECT * FROM notifications 
WHERE status = 'failed' 
AND attempts < 3
ORDER BY created_at DESC;

-- آخر 10 إشعارات مرسلة
SELECT * FROM notifications 
WHERE status = 'sent'
ORDER BY sent_at DESC
LIMIT 10;
```

### 3. تنظيف الإشعارات القديمة

```sql
-- حذف الإشعارات الناجحة الأقدم من 30 يوم
DELETE FROM notifications 
WHERE status = 'sent' 
AND sent_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## 📊 حالات الإشعارات

| الحالة | الوصف | الأيقونة |
|--------|-------|---------|
| `pending` | قيد الانتظار | ⏳ |
| `sent` | تم الإرسال بنجاح | ✅ |
| `failed` | فشل الإرسال | ❌ |

---

## 🎨 تخصيص التصاميم

يمكنك تعديل تصاميم الإيميلات في ملف `EmailNotificationSystem.php`:

- `sendWelcomeEmail()` - تصميم إيميل الترحيب
- `sendNewOrderEmail()` - تصميم إيميل تأكيد الطلب
- `sendOrderStatusUpdateEmail()` - تصميم إيميل تحديث الحالة

---

## 🐛 استكشاف الأخطاء

### المشكلة: لا يتم إرسال الإيميلات

**الحل:**
1. تحقق من إعدادات ملف `mail/mailer.php`
2. تأكد من صحة بيانات SMTP
3. راجع ملف `error_log` في السيرفر

### المشكلة: الإيميلات تذهب إلى SPAM

**الحل:**
1. تأكد من إعدادات SPF و DKIM
2. استخدم عنوان بريد حقيقي في المرسل
3. تجنب الكلمات المشبوهة في الموضوع

### المشكلة: تكرار الإشعارات

**الحل:**
- النظام يحتوي على آلية لمنع التكرار عبر حقل `notified_completed`
- تأكد من عدم استدعاء الـ Hook أكثر من مرة

---

## 📞 الدعم

في حالة وجود أي استفسارات أو مشاكل:
- راجع الأمثلة في مجلد `examples/`
- تحقق من جدول `notifications` في قاعدة البيانات
- راجع ملفات السجل (logs)

---

## 📝 ملاحظات مهمة

✅ النظام يحفظ جميع الإشعارات في قاعدة البيانات  
✅ يمكن إعادة محاولة الإشعارات الفاشلة  
✅ التصاميم responsive وتعمل على جميع الأجهزة  
✅ دعم كامل للغة العربية (RTL)  
✅ آمن ومحمي من SQL Injection  

---

## 🎉 تم بنجاح!

الآن لديك نظام إشعارات بريدية تلقائي ومتكامل! 🚀
