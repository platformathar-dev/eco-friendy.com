<?php
/**
 * مثال على صفحة التسجيل مع إرسال إيميل ترحيب تلقائي
 * Example: User Registration with Auto Email
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/hooks/user_registration_hook.php';

// محاكاة بيانات التسجيل (في الواقع ستأتي من POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $fullname = $_POST['fullname'] ?? 'محمد أحمد';
    $username = $_POST['username'] ?? 'mohammad_ahmed';
    $email = $_POST['email'] ?? 'test@example.com';
    $phone = $_POST['phone'] ?? '0791234567';
    $password = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
    $birthdate = $_POST['birthdate'] ?? '1995-01-01';
    $gender = $_POST['gender'] ?? 'male';
    $country = $_POST['country'] ?? 'JO';
    
    // إدخال المستخدم في قاعدة البيانات
    $stmt = $conn->prepare("
        INSERT INTO users 
        (fullname, username, email, phone, password, birthdate, gender, country, role, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user', 'active', NOW())
    ");
    
    $stmt->bind_param(
        "ssssssss",
        $fullname,
        $username,
        $email,
        $phone,
        $password,
        $birthdate,
        $gender,
        $country
    );
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        
        echo "✅ تم إنشاء الحساب بنجاح! معرف المستخدم: {$userId}<br>";
        
        // 🎯 إرسال إيميل الترحيب تلقائياً
        if (onUserRegistration($userId, $fullname, $email)) {
            echo "📧 تم إرسال إيميل الترحيب إلى: {$email}<br>";
        } else {
            echo "⚠️ تم إنشاء الحساب ولكن فشل إرسال الإيميل<br>";
        }
        
        echo "<hr>";
        echo "<a href='test-mail.php'>اختبار إرسال البريد</a>";
        
    } else {
        echo "❌ خطأ في إنشاء الحساب: " . $stmt->error;
    }
    
} else {
    // نموذج التسجيل
    ?>
    <!DOCTYPE html>
    <html dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>تسجيل حساب جديد</title>
        <style>
            body { font-family: Arial; padding: 20px; max-width: 500px; margin: 0 auto; }
            input, select { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
            button { background: #2ecc71; color: white; padding: 15px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; }
            button:hover { background: #27ae60; }
            label { font-weight: bold; display: block; margin-top: 10px; }
        </style>
    </head>
    <body>
        <h2>📝 إنشاء حساب جديد</h2>
        <form method="POST">
            <label>الاسم الكامل:</label>
            <input type="text" name="fullname" value="محمد أحمد" required>
            
            <label>اسم المستخدم:</label>
            <input type="text" name="username" value="mohammad_ahmed" required>
            
            <label>البريد الإلكتروني:</label>
            <input type="email" name="email" value="test@example.com" required>
            
            <label>رقم الهاتف:</label>
            <input type="text" name="phone" value="0791234567" required>
            
            <label>كلمة المرور:</label>
            <input type="password" name="password" value="123456" required>
            
            <label>تاريخ الميلاد:</label>
            <input type="date" name="birthdate" value="1995-01-01" required>
            
            <label>الجنس:</label>
            <select name="gender" required>
                <option value="male">ذكر</option>
                <option value="female">أنثى</option>
            </select>
            
            <label>الدولة:</label>
            <input type="text" name="country" value="JO" required>
            
            <button type="submit">إنشاء الحساب وإرسال إيميل الترحيب</button>
        </form>
    </body>
    </html>
    <?php
}
