# 📧 دليل تثبيت واستخدام PHPMailer

## ✅ نعم، PHPMailer يعمل بشكل ممتاز!

PHPMailer هو أفضل مكتبة لإرسال البريد الإلكتروني في PHP.

---

## 🎯 طرق التثبيت

### الطريقة 1️⃣: باستخدام Composer (الأفضل والأسهل)

#### الخطوة 1: تحميل Composer
إذا لم يكن لديك Composer:
- اذهب إلى: https://getcomposer.org/download/
- حمل وثبت Composer

#### الخطوة 2: تثبيت PHPMailer
افتح Terminal/CMD في مجلد مشروعك واكتب:
```bash
composer require phpmailer/phpmailer
```

#### الخطوة 3: التحقق من التثبيت
يجب أن تجد مجلد جديد اسمه `vendor` في مشروعك:
```
📁 project/
├── 📁 vendor/
│   └── 📁 phpmailer/
└── composer.json
```

---

### الطريقة 2️⃣: التحميل اليدوي (بدون Composer)

#### الخطوة 1: تحميل PHPMailer
- اذهب إلى: https://github.com/PHPMailer/PHPMailer
- اضغط على "Code" → "Download ZIP"

#### الخطوة 2: استخراج الملفات
```
📁 project/
└── 📁 PHPMailer/
    ├── PHPMailer.php
    ├── SMTP.php
    ├── Exception.php
    └── ...
```

#### الخطوة 3: تضمين الملفات في الكود
```php
<?php
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
```

---

## 📝 طرق إرسال البريد الإلكتروني

### الطريقة 1️⃣: استخدام Gmail (مجاني وسهل) ⭐ الأفضل للبداية

#### خطوات الإعداد:

**1. تفعيل التحقق بخطوتين في Gmail:**
- اذهب إلى: https://myaccount.google.com/security
- فعّل "2-Step Verification"

**2. إنشاء كلمة مرور التطبيق:**
- اذهب إلى: https://myaccount.google.com/apppasswords
- اختر "Mail" و "Other (Custom name)"
- اكتب اسم مثل "Eco Friendly Store"
- احفظ كلمة المرور (16 رقم)

**3. استخدم هذا الكود:**
```php
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // إذا استخدمت Composer

$mail = new PHPMailer(true);

try {
    // إعدادات SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@gmail.com';        // بريدك
    $mail->Password = 'xxxx xxxx xxxx xxxx';         // كلمة مرور التطبيق
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    
    // المرسل والمستقبل
    $mail->setFrom('your-email@gmail.com', 'Eco Friendly Store');
    $mail->addAddress('user@example.com', 'اسم المستخدم');
    
    // المحتوى
    $mail->isHTML(true);
    $mail->Subject = 'رمز التحقق';
    $mail->Body = '<h1>رمز التحقق: 123456</h1>';
    
    $mail->send();
    echo 'تم إرسال البريد بنجاح!';
    
} catch (Exception $e) {
    echo "فشل الإرسال: {$mail->ErrorInfo}";
}
?>
```

**✅ المميزات:**
- مجاني تماماً
- سهل التثبيت
- يعمل مباشرة

**❌ العيوب:**
- حد إرسال: 500 رسالة/يوم
- قد يذهب للـ Spam أحياناً

---

### الطريقة 2️⃣: استخدام SendGrid (احترافي) ⭐ للمشاريع الكبيرة

**المميزات:**
- 100 رسالة/يوم مجاناً
- احترافي جداً
- معدل توصيل عالي

**خطوات الإعداد:**

**1. إنشاء حساب:**
- اذهب إلى: https://sendgrid.com
- سجل حساب مجاني

**2. إنشاء API Key:**
- Dashboard → Settings → API Keys
- Create API Key
- احفظ الـ Key

**3. الكود:**
```php
<?php
$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->Host = 'smtp.sendgrid.net';
$mail->SMTPAuth = true;
$mail->Username = 'apikey';  // دائماً "apikey"
$mail->Password = 'SG.xxxxxxxxxxxxx';  // API Key
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
$mail->CharSet = 'UTF-8';

$mail->setFrom('noreply@yourdomain.com', 'Eco Friendly');
$mail->addAddress($userEmail, $userName);

$mail->isHTML(true);
$mail->Subject = 'رمز التحقق';
$mail->Body = $emailContent;

$mail->send();
?>
```

---

### الطريقة 3️⃣: استخدام Mailgun

**1. إنشاء حساب:**
- https://mailgun.com
- خطة مجانية: 5000 رسالة/شهر لأول 3 أشهر

**2. الكود:**
```php
<?php
$mail->isSMTP();
$mail->Host = 'smtp.mailgun.org';
$mail->SMTPAuth = true;
$mail->Username = 'postmaster@your-domain.mailgun.org';
$mail->Password = 'your-password';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
?>
```

---

### الطريقة 4️⃣: SMTP الخاص بالاستضافة

إذا كان لديك استضافة، اتصل بشركة الاستضافة واطلب:
- SMTP Host
- SMTP Port
- SMTP Username
- SMTP Password

```php
<?php
$mail->isSMTP();
$mail->Host = 'mail.yourdomain.com';
$mail->SMTPAuth = true;
$mail->Username = 'noreply@yourdomain.com';
$mail->Password = 'your-password';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
?>
```

---

## 🧪 اختبار PHPMailer

### ملف اختبار بسيط:

```php
<?php
// test-email.php
use PHPMailer\PHPMailer\PHPMailer;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Gmail Settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'YOUR_EMAIL@gmail.com';
    $mail->Password = 'YOUR_APP_PASSWORD';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    
    $mail->setFrom('YOUR_EMAIL@gmail.com', 'Test');
    $mail->addAddress('YOUR_EMAIL@gmail.com'); // أرسل لنفسك للاختبار
    
    $mail->isHTML(true);
    $mail->Subject = 'اختبار PHPMailer';
    $mail->Body = '<h1>مرحباً! PHPMailer يعمل ✅</h1>';
    
    $mail->send();
    echo '✅ تم إرسال البريد بنجاح!';
    
} catch (Exception $e) {
    echo "❌ فشل: {$mail->ErrorInfo}";
}
?>
```

---

## ⚠️ مشاكل شائعة وحلولها

### المشكلة 1: "SMTP Error: Could not authenticate"
**الحل:**
- تأكد من كلمة مرور التطبيق صحيحة (Gmail)
- تأكد من تفعيل "Less secure app access" أو استخدام App Password

### المشكلة 2: "Connection timeout"
**الحل:**
- تحقق من أن Port 587 أو 465 غير محظور
- جرب Port 465 مع SSL بدلاً من 587 TLS

### المشكلة 3: الرسائل تذهب للـ Spam
**الحل:**
- استخدم SendGrid أو Mailgun
- أضف SPF و DKIM records لنطاقك

### المشكلة 4: "Class PHPMailer not found"
**الحل:**
```php
// تأكد من هذا السطر
require 'vendor/autoload.php';

// أو إذا كنت تستخدم التحميل اليدوي
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';
```

---

## 🎯 توصيتي لك

### للتطوير والتجربة:
**استخدم Gmail** ✅
- سريع وسهل
- مجاني
- لا يحتاج تعقيد

### للإنتاج والمشروع الفعلي:
**استخدم SendGrid** ✅
- احترافي
- معدل توصيل عالي
- تقارير مفصلة

---

## 📊 مقارنة سريعة

| الخدمة | مجاني؟ | الحد اليومي | سهولة الإعداد | التوصية |
|--------|---------|-------------|---------------|----------|
| Gmail | ✅ | 500 رسالة/يوم | ⭐⭐⭐⭐⭐ | للتطوير |
| SendGrid | ✅ | 100 رسالة/يوم | ⭐⭐⭐⭐ | للإنتاج |
| Mailgun | ✅ 3 شهور | 5000 رسالة/شهر | ⭐⭐⭐ | بديل جيد |
| SMTP الاستضافة | ✅ | حسب الخطة | ⭐⭐⭐⭐ | إذا متوفر |

---

## ✅ الخلاصة

**نعم، PHPMailer يعمل بشكل ممتاز!** 

**للبدء السريع:**
1. ثبت PHPMailer بـ Composer
2. استخدم Gmail مع App Password
3. اختبر بإرسال رسالة لنفسك
4. إذا نجح، استخدمه في نظام التحقق

**هل تريد مساعدة في التثبيت والإعداد؟** 🚀
