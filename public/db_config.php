<?php
/**
 * إعدادات الاتصال بقاعدة البيانات
 * Database Configuration
 * 
 * ⚠️ تأكد من تحديث البيانات بمعلوماتك الفعلية
 */

// معلومات الاتصال بقاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'u407057164_ecofriendy');
define('DB_USER', 'u407057164_ecofriendy');
define('DB_PASS', 'Abazid@#$%27887');
define('DB_CHARSET', 'utf8mb4');


// إنشاء الاتصال
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// التحقق من الاتصال
if ($conn->connect_error) {
    error_log("❌ فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}

// تعيين ترميز UTF-8
$conn->set_charset("utf8mb4");

// إرجاع الاتصال
return $conn;
