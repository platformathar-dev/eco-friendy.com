<?php
// مثال على استخدام التحقق من الصلاحيات في صفحة dashboard
// يمكن إضافة هذا الكود في بداية ملف dashboard.php

require_once 'auth_check.php';

// التأكد من أن المستخدم مسجل دخول
requireLogin();

// الحصول على بيانات المستخدم الحالي
$currentUser = getCurrentUser();

// إذا كان المستخدم admin، قم بتوجيهه إلى لوحة تحكم الأدمن
if (isAdmin()) {
    header('Location: /admin-dashboard.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم</title>
</head>
<body>
    <h1>مرحباً <?php echo htmlspecialchars($currentUser['fullname']); ?></h1>
    <p>أنت مسجل دخول كمستخدم عادي</p>
    
    <!-- محتوى لوحة تحكم المستخدم هنا -->
</body>
</html>
