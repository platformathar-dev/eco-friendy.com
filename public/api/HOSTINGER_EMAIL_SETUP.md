# 📧 دليل إعداد نظام التحقق بالبريد الإلكتروني - Hostinger

## ✅ لديك كل ما تحتاجه!

معلومات SMTP الخاصة بك:
- **البريد:** info@eco-friendy.com
- **Host:** smtp.hostinger.com
- **Port:** 465
- **Encryption:** SSL

---

## 🚀 خطوات التثبيت والإعداد

### 1️⃣ تثبيت PHPMailer

#### الطريقة الأولى: Composer (الأفضل)
```bash
# افتح Terminal/CMD في مجلد المشروع
cd path/to/your/project

# ثبت PHPMailer
composer require phpmailer/phpmailer
```

#### الطريقة الثانية: التحميل اليدوي
```
1. اذهب إلى: https://github.com/PHPMailer/PHPMailer/releases
2. حمل ملف ZIP
3. استخرجه في مجلد المشروع
4. ستحتاج 3 ملفات:
   - PHPMailer.php
   - SMTP.php
   - Exception.php
```

---

### 2️⃣ الحصول على كلمة مرور البريد

#### خطوات الحصول على كلمة المرور من Hostinger:

**الخطوة 1:** اذهب إلى لوحة التحكم
```
https://hpanel.hostinger.com
```

**الخطوة 2:** اختر Email من القائمة الجانبية

**الخطوة 3:** ابحث عن البريد: info@eco-friendy.com

**الخطوة 4:** اضغط على "Manage" أو "إدارة"

**الخطوة 5:** ستجد خيارين:
- **إظهار كلمة المرور** (إذا كنت تذكرها)
- **إعادة تعيين كلمة المرور** (إذا نسيتها)

**⚠️ مهم:** احفظ كلمة المرور في مكان آمن!

---

### 3️⃣ إعداد الملفات

#### البنية المطلوبة:
```
📁 project/
├── 📁 api/
│   ├── email_config_hostinger.php     ← إعدادات SMTP
│   ├── email_helper_hostinger.php     ← دوال الإرسال
│   ├── register-with-verification.php ← API التسجيل
│   ├── verify-email.php               ← API التحقق
│   └── test-email-hostinger.php       ← اختبار الإرسال
├── 📁 vendor/                          ← PHPMailer (من Composer)
└── composer.json
```

---

### 4️⃣ تحديث كلمة المرور

#### في ملف `email_config_hostinger.php`:
```php
define('SMTP_PASSWORD', 'YOUR_ACTUAL_PASSWORD'); // ضع كلمة المرور هنا
```

**مثال:**
```php
define('SMTP_PASSWORD', 'MyP@ssw0rd123'); // استبدلها بكلمة المرور الحقيقية
```

---

### 5️⃣ اختبار النظام

#### الخطوة 1: افتح ملف الاختبار
```
http://yoursite.com/api/test-email-hostinger.php
```

أو على localhost:
```
http://localhost/api/test-email-hostinger.php
```

#### الخطوة 2: تحقق من الإعدادات
يجب أن ترى:
- ✅ PHPMailer مثبت
- ✅ كلمة المرور مضبوطة
- ✅ جميع الإعدادات صحيحة

#### الخطوة 3: أرسل بريد تجريبي
اضغط على زر "📧 إرسال بريد تجريبي"

#### الخطوة 4: تحقق من البريد
افتح صندوق الوارد في info@eco-friendy.com

---

## 📋 إعداد قاعدة البيانات

### تشغيل SQL للجداول المطلوبة:
```sql
-- إنشاء جدول رموز التحقق
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `verification_code` varchar(6) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `verification_code` (`verification_code`),
  CONSTRAINT `email_verifications_ibfk_1` 
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- إضافة حقل التحقق لجدول المستخدمين
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `is_email_verified` tinyint(1) NOT NULL DEFAULT 0;

-- فهرس
CREATE INDEX IF NOT EXISTS idx_email_verified ON users(is_email_verified);
```

---

## 🎯 استخدام النظام

### 1. عند التسجيل:
```php
// في register-with-verification.php
$code = generateVerificationCode(); // توليد رمز
sendVerificationEmail($email, $name, $code); // إرسال البريد
// حفظ الرمز في قاعدة البيانات
```

### 2. صفحة التحقق:
```html
<!-- verify-email.html -->
<form onsubmit="verifyCode(event)">
    <input type="text" id="code" placeholder="أدخل رمز التحقق" maxlength="6">
    <button type="submit">تحقق</button>
</form>
```

### 3. API التحقق:
```php
// في verify-email.php
// التحقق من الرمز
// تفعيل الحساب
// إرسال رسالة ترحيب
```

---

## ⚠️ مشاكل محتملة وحلولها

### المشكلة 1: "SMTP Error: Could not authenticate"
**الأسباب:**
- كلمة المرور خاطئة
- البريد غير موجود في Hostinger

**الحل:**
1. تحقق من كلمة المرور في hpanel
2. تأكد من أن البريد info@eco-friendy.com موجود وفعال

---

### المشكلة 2: "Connection timeout"
**الأسباب:**
- Port 465 محظور
- Firewall يمنع الاتصال

**الحل:**
جرب Port 587 مع TLS بدلاً من SSL:
```php
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
```

---

### المشكلة 3: البريد يذهب للـ Spam
**الحل:**
1. تأكد من إضافة SPF Record في DNS:
```
TXT: v=spf1 include:_spf.hostinger.com ~all
```

2. أضف DKIM في إعدادات البريد بـ Hostinger

---

### المشكلة 4: "Class PHPMailer not found"
**الحل:**
```php
// تأكد من هذا السطر في بداية الملف
require_once 'vendor/autoload.php';

// أو إذا كنت تستخدم التحميل اليدوي
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';
```

---

## ✅ قائمة التحقق النهائية

قبل الانتقال للإنتاج، تأكد من:

- [ ] PHPMailer مثبت بنجاح
- [ ] كلمة المرور مضبوطة في email_config_hostinger.php
- [ ] تم اختبار الإرسال عبر test-email-hostinger.php
- [ ] جداول قاعدة البيانات تم إنشاؤها
- [ ] البريد الاختباري وصل بنجاح
- [ ] لا توجد أخطاء في console/logs
- [ ] البريد لا يذهب للـ Spam

---

## 📊 معلومات إضافية

### حدود الإرسال في Hostinger:
- **Premium Shared:** 100 رسالة/ساعة
- **Business:** 150 رسالة/ساعة
- **VPS:** حسب الخطة

### نصائح لتحسين معدل التوصيل:
1. استخدم نطاق موثوق (eco-friendy.com) ✅
2. أضف SPF و DKIM records
3. لا ترسل رسائل بشكل مكثف
4. استخدم نموذج HTML احترافي
5. أضف رابط إلغاء الاشتراك

---

## 🎉 الخلاصة

### ما لديك:
✅ استضافة Hostinger  
✅ بريد إلكتروني: info@eco-friendy.com  
✅ معلومات SMTP كاملة  
✅ PHPMailer جاهز للاستخدام  

### الخطوات التالية:
1. ثبت PHPMailer
2. ضع كلمة مرور البريد
3. اختبر الإرسال
4. نفذ نظام التحقق

**🚀 أنت جاهز للبدء!**

---

## 📞 الدعم

إذا واجهت أي مشكلة:
1. افتح test-email-hostinger.php وشاهد رسائل الخطأ
2. تحقق من error logs في Hostinger
3. تواصل مع دعم Hostinger إذا لزم الأمر

**💡 نصيحة:** احفظ كلمة مرور البريد في مكان آمن وآمن!
