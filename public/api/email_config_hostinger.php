<?php
/**
 * إعدادات البريد الإلكتروني - Hostinger
 * 
 * معلومات SMTP الخاصة بـ Hostinger
 * البريد: info@eco-friendy.com
 */

// ============================================
// إعدادات SMTP - Hostinger
// ============================================
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465); // استخدام SSL على المنفذ 465
define('SMTP_SECURE', 'ssl'); // SSL encryption
define('SMTP_USERNAME', 'info@eco-friendy.com'); // بريدك الإلكتروني الكامل
define('SMTP_PASSWORD', 'YOUR_EMAIL_PASSWORD'); // ⚠️ ضع كلمة مرور البريد هنا
define('SMTP_FROM_EMAIL', 'info@eco-friendy.com');
define('SMTP_FROM_NAME', 'Eco Friendly Store');

// ============================================
// إعدادات إضافية
// ============================================
define('EMAIL_VERIFICATION_EXPIRY', 15); // مدة صلاحية رمز التحقق بالدقائق
define('SITE_URL', 'https://eco-friendy.com'); // رابط موقعك
define('SUPPORT_EMAIL', 'info@eco-friendy.com'); // بريد الدعم الفني

// ============================================
// دالة مساعدة لإرسال البريد
// ============================================
function getMailerConfig() {
    return [
        'host' => SMTP_HOST,
        'port' => SMTP_PORT,
        'secure' => SMTP_SECURE,
        'username' => SMTP_USERNAME,
        'password' => SMTP_PASSWORD,
        'from_email' => SMTP_FROM_EMAIL,
        'from_name' => SMTP_FROM_NAME
    ];
}

/**
 * ⚠️ تعليمات مهمة:
 * 
 * 1. استبدل 'YOUR_EMAIL_PASSWORD' بكلمة المرور الحقيقية للبريد
 * 2. كلمة المرور هي نفس كلمة المرور التي تستخدمها لتسجيل الدخول إلى البريد
 * 3. لا تشارك هذا الملف مع أحد (أضفه إلى .gitignore)
 * 
 * للحصول على كلمة المرور:
 * - اذهب إلى لوحة تحكم Hostinger
 * - قسم البريد الإلكتروني (Email)
 * - يمكنك رؤية أو إعادة تعيين كلمة المرور
 */
?>
