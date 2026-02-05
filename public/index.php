<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// تضمين ملف الاتصال
require_once 'config.php';

// جلب المنتجات
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 6");
    $latestProducts = $stmt->fetchAll();
    
    $stmt2 = $pdo->query("SELECT * FROM products LIMIT 6");
    $relatedProducts = $stmt2->fetchAll();
} catch(PDOException $e) {
    die("خطأ في جلب المنتجات: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eco Friendy Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Tajawal', sans-serif; background: #fffbf7; }
        
        .header { background: #f39200; color: white; padding: 20px; text-align: center; }
        .header h1 { font-size: 32px; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        .products-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); 
            gap: 20px; 
            margin-top: 30px; 
        }
        
        .product-card { 
            background: white; 
            border-radius: 10px; 
            padding: 15px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
        }
        
        .product-image { 
            width: 100%; 
            height: 200px; 
            object-fit: cover; 
            border-radius: 8px; 
        }
        
        .product-name { 
            font-size: 18px; 
            font-weight: bold; 
            margin: 10px 0; 
            color: #c77400; 
        }
        
        .product-price { 
            font-size: 20px; 
            color: #f39200; 
            font-weight: bold; 
        }
        
        .section-title { 
            font-size: 24px; 
            color: #c77400; 
            margin: 30px 0 20px; 
            border-bottom: 3px solid #f39200; 
            padding-bottom: 10px; 
        }
        
        .no-products { 
            text-align: center; 
            padding: 40px; 
            background: #fff3e6; 
            border-radius: 10px; 
            color: #c77400; 
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🐾 Eco Friendy Store</h1>
        <p>متجرك الأول لمستلزمات الحيوانات الأليفة</p>
    </div>
    
    <div class="container">
        <h2 class="section-title">أحدث المنتجات</h2>
        
        <?php if(count($latestProducts) > 0): ?>
            <div class="products-grid">
                <?php foreach($latestProducts as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-price">
                            <?php 
                            $price = $product['sale_price'] ?? $product['price'];
                            echo number_format($price, 2); 
                            ?> دينار
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-products">
                <i class="fas fa-box-open" style="font-size: 50px; margin-bottom: 20px;"></i>
                <h3>لا توجد منتجات حالياً</h3>
                <p>يرجى إضافة منتجات من phpMyAdmin</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
