<?php
// api/get-home-products.php
// جلب المنتجات للصفحة الرئيسية

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

    // جلب أحدث 6 منتجات نشطة
    $sql = "SELECT * FROM products 
            WHERE status = 'active' 
            ORDER BY created_at DESC 
            LIMIT 6";
    
    $stmt = $pdo->query($sql);
    $latestProducts = $stmt->fetchAll();

    // جلب 6 منتجات ذات صلة (عشوائية)
    $sql2 = "SELECT * FROM products 
             WHERE status = 'active' 
             ORDER BY RAND() 
             LIMIT 6";
    
    $stmt2 = $pdo->query($sql2);
    $relatedProducts = $stmt2->fetchAll();

    // إحصائيات
    $statsQuery = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN category LIKE '%كلاب%' OR category LIKE '%dog%' THEN 1 ELSE 0 END) as dogs,
        SUM(CASE WHEN category LIKE '%قطط%' OR category LIKE '%cat%' THEN 1 ELSE 0 END) as cats
        FROM products WHERE status = 'active'");
    $stats = $statsQuery->fetch();

    echo json_encode([
        'success' => true,
        'latestProducts' => $latestProducts,
        'relatedProducts' => $relatedProducts,
        'stats' => $stats
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'latestProducts' => [],
        'relatedProducts' => []
    ], JSON_UNESCAPED_UNICODE);
}
?>
