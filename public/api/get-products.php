<?php
// api/get-products.php
// جلب قائمة المنتجات - متاح للجميع (بدون تسجيل دخول)

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception('فشل الاتصال بقاعدة البيانات');
    }
    
    // جلب جميع المنتجات النشطة فقط
    // تم تحديث الاستعلام ليتوافق مع بنية الجدول الفعلية
    $sql = "SELECT 
                id,
                name,
                category,
                price,
                stock,
                description,
                image,
                status,
                created_at,
                updated_at
            FROM products 
            WHERE status = 'active'
            ORDER BY created_at DESC";
    
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // تحويل الأنواع للتأكد من صحة البيانات
    foreach ($products as &$product) {
        $product['id'] = (int)$product['id'];
        $product['price'] = (float)$product['price'];
        $product['stock'] = (int)$product['stock'];
        
        // إضافة حقول افتراضية إذا كانت فارغة
        if (empty($product['image'])) {
            $product['image'] = null;
        }
        if (empty($product['description'])) {
            $product['description'] = 'لا يوجد وصف';
        }
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'total' => count($products)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في قاعدة البيانات',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
