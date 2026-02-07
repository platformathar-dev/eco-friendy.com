<?php
/**
 * صفحة اختبار نظام الإشعارات
 * System Testing Page
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/EmailNotificationSystem.php';

$emailSystem = new EmailNotificationSystem($conn);

?>
<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار نظام الإشعارات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .header p {
            color: #7f8c8d;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .card h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .icon {
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #667eea;
            outline: none;
        }
        button {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            color: white;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
        }
        .btn-info {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            color: white;
        }
        .stat-card.success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }
        .stat-card.warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        .stat-card.danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- Header -->
        <div class="header">
            <h1>🚀 لوحة اختبار نظام الإشعارات البريدية</h1>
            <p>اختبر جميع وظائف النظام من مكان واحد</p>
        </div>

        <!-- Statistics -->
        <div class="card">
            <h2><span class="icon">📊</span> إحصائيات الإشعارات</h2>
            <div class="stats-grid">
                <?php
                $stats = $conn->query("
                    SELECT 
                        COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent,
                        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                        COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                        COUNT(*) as total
                    FROM notifications
                ")->fetch_assoc();
                ?>
                
                <div class="stat-card success">
                    <div class="stat-number"><?= $stats['sent'] ?></div>
                    <div class="stat-label">✅ تم الإرسال</div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-number"><?= $stats['pending'] ?></div>
                    <div class="stat-label">⏳ قيد الانتظار</div>
                </div>
                
                <div class="stat-card danger">
                    <div class="stat-number"><?= $stats['failed'] ?></div>
                    <div class="stat-label">❌ فشل</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">📧 الإجمالي</div>
                </div>
            </div>
        </div>

        <!-- Testing Forms -->
        <div class="grid">
            
            <!-- Test Welcome Email -->
            <div class="card">
                <h2><span class="icon">👋</span> اختبار إيميل الترحيب</h2>
                <form method="POST" action="">
                    <input type="hidden" name="test_type" value="welcome">
                    
                    <div class="form-group">
                        <label>الاسم الكامل:</label>
                        <input type="text" name="fullname" value="محمد أحمد" required>
                    </div>
                    
                    <div class="form-group">
                        <label>البريد الإلكتروني:</label>
                        <input type="email" name="email" value="test@example.com" required>
                    </div>
                    
                    <button type="submit" class="btn-success">إرسال إيميل الترحيب</button>
                </form>
            </div>

            <!-- Test New Order Email -->
            <div class="card">
                <h2><span class="icon">🛒</span> اختبار إيميل الطلب</h2>
                <form method="POST" action="">
                    <input type="hidden" name="test_type" value="new_order">
                    
                    <div class="form-group">
                        <label>اسم العميل:</label>
                        <input type="text" name="customer_name" value="أحمد محمد" required>
                    </div>
                    
                    <div class="form-group">
                        <label>البريد الإلكتروني:</label>
                        <input type="email" name="customer_email" value="customer@example.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label>المبلغ:</label>
                        <input type="number" step="0.01" name="total_amount" value="50.00" required>
                    </div>
                    
                    <button type="submit" class="btn-info">إرسال إيميل الطلب</button>
                </form>
            </div>

            <!-- Test Status Update Email -->
            <div class="card">
                <h2><span class="icon">🔄</span> اختبار إيميل التحديث</h2>
                <form method="POST" action="">
                    <input type="hidden" name="test_type" value="status_update">
                    
                    <div class="form-group">
                        <label>اسم العميل:</label>
                        <input type="text" name="customer_name" value="أحمد محمد" required>
                    </div>
                    
                    <div class="form-group">
                        <label>البريد الإلكتروني:</label>
                        <input type="email" name="customer_email" value="customer@example.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label>رقم الطلب:</label>
                        <input type="text" name="order_number" value="ORD-2025-000001" required>
                    </div>
                    
                    <div class="form-group">
                        <label>الحالة الجديدة:</label>
                        <select name="status" required>
                            <option value="pending">⏳ قيد الانتظار</option>
                            <option value="processing">🔄 قيد المعالجة</option>
                            <option value="completed" selected>✅ مكتمل</option>
                            <option value="cancelled">❌ ملغي</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-primary">إرسال إيميل التحديث</button>
                </form>
            </div>
        </div>

        <?php
        // Process Test Forms
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_type'])) {
            
            $testType = $_POST['test_type'];
            $result = false;
            $message = '';
            
            try {
                switch ($testType) {
                    case 'welcome':
                        $result = $emailSystem->sendWelcomeEmail(
                            999, // Dummy user ID
                            $_POST['fullname'],
                            $_POST['email']
                        );
                        $message = $result ? 
                            "✅ تم إرسال إيميل الترحيب بنجاح إلى {$_POST['email']}" :
                            "❌ فشل إرسال إيميل الترحيب";
                        break;
                    
                    case 'new_order':
                        $result = $emailSystem->sendNewOrderEmail(
                            999, // Dummy order ID
                            $_POST['customer_email'],
                            $_POST['customer_name'],
                            'ORD-TEST-' . time(),
                            $_POST['total_amount']
                        );
                        $message = $result ? 
                            "✅ تم إرسال إيميل الطلب بنجاح إلى {$_POST['customer_email']}" :
                            "❌ فشل إرسال إيميل الطلب";
                        break;
                    
                    case 'status_update':
                        $result = $emailSystem->sendOrderStatusUpdateEmail(
                            999, // Dummy order ID
                            $_POST['customer_email'],
                            $_POST['customer_name'],
                            $_POST['order_number'],
                            $_POST['status']
                        );
                        $message = $result ? 
                            "✅ تم إرسال إيميل تحديث الحالة بنجاح إلى {$_POST['customer_email']}" :
                            "❌ فشل إرسال إيميل تحديث الحالة";
                        break;
                }
                
                echo '<div class="alert ' . ($result ? 'alert-success' : 'alert-error') . '">';
                echo $message;
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="alert alert-error">';
                echo "❌ خطأ: " . $e->getMessage();
                echo '</div>';
            }
        }
        ?>

        <!-- Recent Notifications -->
        <div class="card">
            <h2><span class="icon">📋</span> آخر 10 إشعارات</h2>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                    <thead>
                        <tr style="background: #f8f9fa; text-align: right;">
                            <th style="padding: 12px; border-bottom: 2px solid #dee2e6;">البريد</th>
                            <th style="padding: 12px; border-bottom: 2px solid #dee2e6;">النوع</th>
                            <th style="padding: 12px; border-bottom: 2px solid #dee2e6;">الحالة</th>
                            <th style="padding: 12px; border-bottom: 2px solid #dee2e6;">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent = $conn->query("
                            SELECT email, type, status, created_at 
                            FROM notifications 
                            ORDER BY created_at DESC 
                            LIMIT 10
                        ");
                        
                        while ($row = $recent->fetch_assoc()) {
                            $statusColor = [
                                'sent' => '#2ecc71',
                                'pending' => '#f39c12',
                                'failed' => '#e74c3c'
                            ];
                            $color = $statusColor[$row['status']] ?? '#95a5a6';
                            
                            echo "<tr>";
                            echo "<td style='padding: 12px; border-bottom: 1px solid #dee2e6;'>{$row['email']}</td>";
                            echo "<td style='padding: 12px; border-bottom: 1px solid #dee2e6;'>{$row['type']}</td>";
                            echo "<td style='padding: 12px; border-bottom: 1px solid #dee2e6;'><span style='background: {$color}; color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px;'>{$row['status']}</span></td>";
                            echo "<td style='padding: 12px; border-bottom: 1px solid #dee2e6;'>{$row['created_at']}</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>
