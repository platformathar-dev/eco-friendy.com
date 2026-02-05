<?php
// ملف إعدادات الاتصال بقاعدة البيانات
// config.php

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'u407057164_ecofriendy');
define('DB_USER', 'u407057164_ecofriendy');
define('DB_PASS', 'Abazid@#$%1999');
define('DB_CHARSET', 'utf8mb4');

// إنشاء اتصال بقاعدة البيانات
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // في حالة فشل الاتصال
        error_log("Database Connection Error: " . $e->getMessage());
        return null;
    }
}

// دالة للتحقق من الاتصال
function testDBConnection() {
    $pdo = getDBConnection();
    if ($pdo) {
        return true;
    }
    return false;
}
?>
