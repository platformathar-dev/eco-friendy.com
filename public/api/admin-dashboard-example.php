<?php
// مثال على استخدام التحقق من الصلاحيات في صفحة admin-dashboard
// يمكن إضافة هذا الكود في بداية ملف admin-dashboard.php

require_once 'auth_check.php';

// التأكد من أن المستخدم admin فقط
requireAdmin();

// الحصول على بيانات المستخدم الحالي
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة تحكم المسؤول</title>
</head>
<body>
    <h1>مرحباً <?php echo htmlspecialchars($currentUser['fullname']); ?></h1>
    <p>أنت مسجل دخول كمسؤول</p>
    
    <!-- محتوى لوحة تحكم الأدمن هنا -->
</body>
</html>
