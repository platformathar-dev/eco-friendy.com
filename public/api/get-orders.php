<?php
// api/get-orders.php
// جلب قائمة الطلبات

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

require_once '../config.php';

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    // التحقق من تسجيل الدخول (اختياري - يمكن تعطيله للضيوف)
    $isAdmin = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    $userId = $_SESSION['user_id'] ?? null;
    
    // بناء الاستعلام حسب الصلاحيات
    if ($isAdmin) {
        // الأدمن يرى جميع الطلبات
        $sql = "SELECT 
                    o.*,
                    COUNT(oi.id) as items_count
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                GROUP BY o.id
                ORDER BY o.created_at DESC";
        $stmt = $pdo->query($sql);
    } elseif ($userId) {
        // المستخدم العادي يرى طلباته فقط
        $sql = "SELECT 
                    o.*,
                    COUNT(oi.id) as items_count
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.user_id = :user_id
                GROUP BY o.id
                ORDER BY o.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
    } else {
        // الضيوف لا يمكنهم رؤية الطلبات
        throw new Exception('يجب تسجيل الدخول لعرض الطلبات');
    }
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // جلب عناصر كل طلب
    foreach ($orders as &$order) {
        $sqlItems = "SELECT 
                        oi.*,
                        p.name as product_name,
                        p.image as product_image
                    FROM order_items oi
                    LEFT JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = :order_id";
        $stmtItems = $pdo->prepare($sqlItems);
        $stmtItems->execute([':order_id' => $order['id']]);
        $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        
        // تنسيق البيانات
        $order['id'] = (int)$order['id'];
        $order['user_id'] = (int)$order['user_id'];
        $order['total_amount'] = (float)$order['total_amount'];
        $order['items_count'] = (int)$order['items_count'];
        $order['order_number'] = str_pad($order['id'], 5, '0', STR_PAD_LEFT);
    }
    
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'total' => count($orders),
        'is_admin' => $isAdmin
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
