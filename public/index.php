<?php
// تضمين ملف الاتصال بقاعدة البيانات
require_once 'config.php';

// جلب أحدث المنتجات
$latestProductsQuery = "SELECT * FROM products ORDER BY created_at DESC LIMIT 6";
$latestProducts = $pdo->query($latestProductsQuery)->fetchAll();

// جلب المنتجات ذات الصلة (يمكن تعديل الشرط حسب الحاجة)
$relatedProductsQuery = "SELECT * FROM products WHERE featured = 1 LIMIT 8";
$relatedProducts = $pdo->query($relatedProductsQuery)->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eco Friendy - الصفحة الرئيسية</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #f39200;
            --secondary-color: #e68500;
            --dark-orange: #c77400;
            --light-orange: #fff3e6;
            --accent-color: #ff6b35;
            --success-color: #10b981;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --bg-light: #fffbf7;
            --white: #ffffff;
        }

        body {
            font-family: 'Tajawal', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ============ TOP BAR ============ */
        .top-bar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 10px 15px;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .top-bar-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            transition: opacity 0.3s;
        }

        .top-bar-item:hover {
            opacity: 0.8;
        }

        .lang-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            padding: 6px 18px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: 'Tajawal', sans-serif;
            transition: all 0.3s;
        }

        .lang-btn:hover {
            background: white;
            color: var(--primary-color);
        }

        /* ============ MAIN HEADER ============ */
        .main-header {
            background: white;
            padding: 12px 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .logo {
            font-size: 22px;
            font-weight: 900;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .header-icons {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .header-icon {
            position: relative;
            color: var(--text-dark);
            font-size: 22px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .header-icon:hover {
            color: var(--primary-color);
            transform: scale(1.1);
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--accent-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
        }

        .mobile-menu-btn {
            background: none;
            border: none;
            font-size: 26px;
            color: var(--text-dark);
            cursor: pointer;
        }

        /* ============ SEARCH BAR ============ */
        .search-section {
            background: white;
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .search-bar {
            position: relative;
            width: 100%;
        }

        .search-bar input {
            width: 100%;
            padding: 12px 55px 12px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 30px;
            font-size: 14px;
            font-family: 'Tajawal', sans-serif;
        }

        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .search-bar button {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-color);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }

        .search-bar button:hover {
            background: var(--secondary-color);
        }

        /* ============ HERO SECTION ============ */
        .hero {
            background: linear-gradient(135deg, rgba(243, 146, 0, 0.95), rgba(230, 133, 0, 0.95)),
                        url('https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=1600') center/cover;
            color: white;
            padding: 50px 20px;
            text-align: center;
        }

        .hero h1 {
            font-size: 28px;
            font-weight: 900;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .hero p {
            font-size: 15px;
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 25px;
            align-items: center;
        }

        .btn {
            padding: 14px 50px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
            min-width: 200px;
            text-align: center;
            border: none;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
        }

        .btn:active {
            transform: scale(0.95);
        }

        .btn-white {
            background: white;
            color: var(--primary-color);
        }

        .btn-accent {
            background: var(--accent-color);
            color: white;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        /* ============ SECTION ============ */
        .section {
            padding: 40px 15px;
        }

        .section.bg-white {
            background: white;
        }

        .section-title {
            text-align: center;
            font-size: 24px;
            font-weight: 900;
            color: var(--dark-orange);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            font-size: 20px;
            font-weight: 900;
            color: var(--dark-orange);
        }

        .view-all {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        /* ============ CATEGORIES SLIDER ============ */
        .categories-slider-container {
            position: relative;
            overflow: hidden;
            margin: 0 -15px;
            padding: 0 15px;
        }

        .categories-slider {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding-bottom: 10px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .categories-slider::-webkit-scrollbar {
            display: none;
        }

        .category-card {
            min-width: 120px;
            flex-shrink: 0;
            text-align: center;
            background: white;
            border-radius: 15px;
            padding: 20px 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
        }

        .category-card:active {
            transform: scale(0.95);
        }

        .category-icon {
            font-size: 50px;
            margin-bottom: 10px;
        }

        .category-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--dark-orange);
        }

        /* ============ SUB CATEGORIES ============ */
        .sub-cat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }

        .sub-cat-card {
            background: var(--light-orange);
            border-radius: 12px;
            padding: 20px 10px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s;
            cursor: pointer;
        }

        .sub-cat-card:active {
            transform: scale(0.95);
            border-color: var(--primary-color);
        }

        .sub-cat-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .sub-cat-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark-orange);
        }

        .sub-cat-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark-orange);
            margin-bottom: 15px;
            text-align: center;
            margin-top: 25px;
        }

        .sub-cat-title:first-child {
            margin-top: 0;
        }

        /* ============ PRODUCTS GRID ============ */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative;
            transition: all 0.3s;
        }

        .product-card:active {
            transform: scale(0.98);
        }

        .product-badges {
            position: absolute;
            top: 8px;
            right: 8px;
            display: flex;
            flex-direction: column;
            gap: 5px;
            z-index: 10;
        }

        .badge-new, .badge-sale, .badge-eco {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            color: white;
        }

        .badge-new {
            background: var(--accent-color);
        }

        .badge-sale {
            background: #ef4444;
        }

        .badge-eco {
            background: var(--success-color);
        }

        .product-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
        }

        .product-info {
            padding: 12px;
        }

        .product-category {
            color: var(--text-light);
            font-size: 11px;
            margin-bottom: 5px;
        }

        .product-name {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--dark-orange);
            min-height: 38px;
            line-height: 1.3;
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 8px;
        }

        .stars {
            color: var(--accent-color);
            font-size: 12px;
        }

        .reviews {
            color: var(--text-light);
            font-size: 11px;
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .current-price {
            font-size: 18px;
            font-weight: 900;
            color: var(--primary-color);
        }

        .original-price {
            font-size: 13px;
            color: var(--text-light);
            text-decoration: line-through;
        }

        .discount-badge {
            background: #ef4444;
            color: white;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
        }

        .add-to-cart {
            width: 100%;
            padding: 10px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            font-family: 'Tajawal', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .add-to-cart:active {
            transform: scale(0.95);
        }

        /* ============ RELATED SLIDER ============ */
        .related-slider-container {
            position: relative;
            overflow: hidden;
            margin: 0 -15px;
            padding: 0 15px;
        }

        .related-slider {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding-bottom: 10px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }

        .related-slider::-webkit-scrollbar {
            display: none;
        }

        .related-card {
            min-width: 160px;
            flex-shrink: 0;
        }

        /* ============ FOOTER ============ */
        .footer {
            background: var(--dark-orange);
            color: white;
            padding: 40px 15px 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 25px;
        }

        .footer-section h3 {
            font-size: 16px;
            margin-bottom: 12px;
            color: var(--light-orange);
        }

        .footer-section a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: white;
        }

        .social-links {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .social-icon {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
            transition: all 0.3s;
        }

        .social-icon:hover {
            background: var(--primary-color);
        }

        .footer-bottom {
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 13px;
        }

        /* ============ MOBILE MENU ============ */
        .mobile-nav {
            position: fixed;
            top: 0;
            right: -100%;
            width: 280px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 2000;
            transition: right 0.3s ease;
            overflow-y: auto;
        }

        .mobile-nav.active {
            right: 0;
        }

        .mobile-nav-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mobile-nav-close {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
        }

        .mobile-nav-menu {
            list-style: none;
            padding: 0;
        }

        .mobile-nav-menu li {
            border-bottom: 1px solid #f0f0f0;
        }

        .mobile-nav-menu a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .mobile-nav-menu a:hover {
            background: var(--bg-light);
            color: var(--primary-color);
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .overlay.active {
            display: block;
            opacity: 1;
        }

        /* ============ NOTIFICATION ============ */
        .notification {
            position: fixed;
            top: 80px;
            right: 20px;
            background: var(--success-color);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 3000;
            display: none;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .notification.show {
            display: flex;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* ============ LOADING ============ */
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Notification -->
    <div class="notification" id="notification">
        <i class="fas fa-check-circle"></i>
        <span id="notificationText">تمت الإضافة بنجاح!</span>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>

    <!-- Mobile Nav -->
    <div class="mobile-nav" id="mobileNav">
        <div class="mobile-nav-header">
            <h3>🐾 Eco Friendy</h3>
            <button class="mobile-nav-close" onclick="closeMobileMenu()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <ul class="mobile-nav-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> الرئيسية</a></li>
            <li><a href="products.php"><i class="fas fa-shopping-bag"></i> جميع المنتجات</a></li>
            <li><a href="dogs.php"><i class="fas fa-dog"></i> كلاب</a></li>
            <li><a href="cats.php"><i class="fas fa-cat"></i> قطط</a></li>
            <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> السلة</a></li>
            <li><a href="wishlist.php"><i class="fas fa-heart"></i> المفضلة</a></li>
            <li><a href="about.php"><i class="fas fa-info-circle"></i> من نحن</a></li>
            <li><a href="contact.php"><i class="fas fa-envelope"></i> اتصل بنا</a></li>
        </ul>
    </div>

    <!-- Top Bar -->
    <div class="top-bar">
        <a href="tel:962790083039" class="top-bar-item">
            <i class="fas fa-phone"></i>
            962790083039+
        </a>
        <a href="mailto:info@eco-friendy.com" class="top-bar-item">
            <i class="fas fa-envelope"></i>
            info@eco-friendy.com
        </a>
        <button class="lang-btn" onclick="toggleLanguage()">
            <i class="fas fa-globe"></i>
            <span>English</span>
        </button>
        <a href="#" class="top-bar-item">
            <i class="fas fa-map-marker-alt"></i>
            الأردن - عمان
        </a>
    </div>

    <!-- Main Header -->
    <header class="main-header">
        <div class="header-content">
            <a href="index.php" class="logo">
                🐾 Eco Friendy
            </a>
            
            <div class="header-icons">
                <a href="cart.php" class="header-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="badge" id="cart-count">0</span>
                </a>
                <a href="wishlist.php" class="header-icon">
                    <i class="fas fa-heart"></i>
                    <span class="badge" id="wishlist-count">0</span>
                </a>
                <button class="mobile-menu-btn" onclick="openMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Search Bar -->
    <div class="search-section">
        <div class="search-bar">
            <input type="text" placeholder="ابحث عن منتجات..." id="searchInput">
            <button onclick="searchProducts()"><i class="fas fa-search"></i></button>
        </div>
    </div>

    <!-- Hero -->
    <section class="hero">
        <h1>🐾 مرحباً بك في Eco Friendy Store</h1>
        <p>متجرك الأول لمستلزمات وأطعمة ورعاية الحيوانات الأليفة في الأردن</p>
        <p>كل ما يحتاجه حيوانك الأليف في مكان واحد</p>
        <div class="hero-buttons">
            <a href="products.php" class="btn btn-white">تسوق الآن</a>
            <a href="about.php" class="btn btn-accent">اعرف المزيد</a>
        </div>
    </section>

    <!-- Categories Slider -->
    <section class="section">
        <h2 class="section-title">تسوق حسب الفئة</h2>
        <div class="categories-slider-container">
            <div class="categories-slider" id="categoriesSlider">
                <a href="dogs.php" class="category-card">
                    <div class="category-icon">🐕</div>
                    <div class="category-name">كلاب</div>
                </a>
                <a href="cats.php" class="category-card">
                    <div class="category-icon">🐈</div>
                    <div class="category-name">قطط</div>
                </a>
                <a href="products.php?category=birds" class="category-card">
                    <div class="category-icon">🦜</div>
                    <div class="category-name">طيور</div>
                </a>
                <a href="products.php?category=fish" class="category-card">
                    <div class="category-icon">🐠</div>
                    <div class="category-name">أسماك</div>
                </a>
                <a href="products.php?category=rabbits" class="category-card">
                    <div class="category-icon">🐰</div>
                    <div class="category-name">أرانب</div>
                </a>
                <a href="products.php?category=hamsters" class="category-card">
                    <div class="category-icon">🐹</div>
                    <div class="category-name">هامسترات</div>
                </a>
            </div>
        </div>
    </section>

    <!-- Sub Categories -->
    <section class="section bg-white">
        <h2 class="section-title">أقسام المتجر</h2>
        
        <h3 class="sub-cat-title">🐕 كلاب</h3>
        <div class="sub-cat-grid">
            <a href="dogs.php?type=accessories" class="sub-cat-card">
                <div class="sub-cat-icon">👔</div>
                <div class="sub-cat-name">إكسسوارات</div>
            </a>
            <a href="dogs.php?type=food" class="sub-cat-card">
                <div class="sub-cat-icon">🍖</div>
                <div class="sub-cat-name">طعام</div>
            </a>
            <a href="dogs.php?type=care" class="sub-cat-card">
                <div class="sub-cat-icon">💊</div>
                <div class="sub-cat-name">رعاية</div>
            </a>
        </div>

        <h3 class="sub-cat-title">🐈 قطط</h3>
        <div class="sub-cat-grid">
            <a href="cats.php?type=accessories" class="sub-cat-card">
                <div class="sub-cat-icon">👔</div>
                <div class="sub-cat-name">إكسسوارات</div>
            </a>
            <a href="cats.php?type=food" class="sub-cat-card">
                <div class="sub-cat-icon">🍖</div>
                <div class="sub-cat-name">طعام</div>
            </a>
            <a href="cats.php?type=care" class="sub-cat-card">
                <div class="sub-cat-icon">💊</div>
                <div class="sub-cat-name">رعاية</div>
            </a>
        </div>
    </section>

    <!-- Latest Products -->
    <section class="section">
        <div class="section-header">
            <h2>منتجات أحدث ⭐</h2>
            <a href="products.php?filter=new" class="view-all">عرض الكل ←</a>
        </div>
        <div class="products-grid" id="latestProducts">
            <?php foreach($latestProducts as $product): 
                $discount = 0;
                if($product['sale_price'] && $product['sale_price'] < $product['price']) {
                    $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                }
                $finalPrice = $product['sale_price'] ?? $product['price'];
            ?>
            <div class="product-card">
                <div class="product-badges">
                    <?php if($product['is_new']): ?>
                    <span class="badge-new">جديد</span>
                    <?php endif; ?>
                    <?php if($discount > 0): ?>
                    <span class="badge-sale">تخفيض</span>
                    <?php endif; ?>
                    <?php if($product['is_eco']): ?>
                    <span class="badge-eco">صديق للبيئة</span>
                    <?php endif; ?>
                </div>
                <a href="product.php?id=<?php echo $product['id']; ?>">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="product-image">
                </a>
                <div class="product-info">
                    <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                    <div class="product-rating">
                        <div class="stars">
                            <?php 
                            $rating = $product['rating'] ?? 5;
                            for($i = 0; $i < 5; $i++): 
                                echo $i < $rating ? '★' : '☆';
                            endfor; 
                            ?>
                        </div>
                        <span class="reviews">(<?php echo $product['reviews'] ?? 0; ?>)</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price"><?php echo number_format($finalPrice, 2); ?> دينار</span>
                        <?php if($discount > 0): ?>
                        <span class="original-price"><?php echo number_format($product['price'], 2); ?> دينار</span>
                        <span class="discount-badge">-<?php echo $discount; ?>%</span>
                        <?php endif; ?>
                    </div>
                    <button class="add-to-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                        <i class="fas fa-cart-plus"></i>
                        <span>أضف للسلة</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Related Products Slider -->
    <section class="section bg-white">
        <div class="section-header">
            <h2>منتجات ذات صلة 🔥</h2>
            <a href="products.php?filter=related" class="view-all">عرض الكل ←</a>
        </div>
        <div class="related-slider-container">
            <div class="related-slider" id="relatedSlider">
                <?php foreach($relatedProducts as $product): 
                    $discount = 0;
                    if($product['sale_price'] && $product['sale_price'] < $product['price']) {
                        $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                    }
                    $finalPrice = $product['sale_price'] ?? $product['price'];
                ?>
                <div class="product-card related-card">
                    <div class="product-badges">
                        <?php if($product['is_new']): ?>
                        <span class="badge-new">جديد</span>
                        <?php endif; ?>
                        <?php if($discount > 0): ?>
                        <span class="badge-sale">تخفيض</span>
                        <?php endif; ?>
                        <?php if($product['is_eco']): ?>
                        <span class="badge-eco">صديق للبيئة</span>
                        <?php endif; ?>
                    </div>
                    <a href="product.php?id=<?php echo $product['id']; ?>">
                        <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                    </a>
                    <div class="product-info">
                        <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-rating">
                            <div class="stars">
                                <?php 
                                $rating = $product['rating'] ?? 5;
                                for($i = 0; $i < 5; $i++): 
                                    echo $i < $rating ? '★' : '☆';
                                endfor; 
                                ?>
                            </div>
                            <span class="reviews">(<?php echo $product['reviews'] ?? 0; ?>)</span>
                        </div>
                        <div class="product-price">
                            <span class="current-price"><?php echo number_format($finalPrice, 2); ?> دينار</span>
                            <?php if($discount > 0): ?>
                            <span class="original-price"><?php echo number_format($product['price'], 2); ?> دينار</span>
                            <?php endif; ?>
                        </div>
                        <button class="add-to-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                            <i class="fas fa-cart-plus"></i>
                            <span>أضف للسلة</span>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>روابط سريعة</h3>
                <a href="index.php">الرئيسية</a>
                <a href="products.php">المنتجات</a>
                <a href="about.php">من نحن</a>
                <a href="contact.php">اتصل بنا</a>
            </div>

            <div class="footer-section">
                <h3>خدمة العملاء</h3>
                <a href="faq.php">الأسئلة الشائعة</a>
                <a href="returns.php">سياسة الإرجاع</a>
                <a href="shipping.php">الشحن والتوصيل</a>
                <a href="privacy.php">سياسة الخصوصية</a>
            </div>

            <div class="footer-section">
                <h3>تواصل معنا</h3>
                <a href="tel:+962790083039">
                    <i class="fas fa-phone"></i> +962790083039
                </a>
                <a href="mailto:info@eco-friendy.com">
                    <i class="fas fa-envelope"></i> info@eco-friendy.com
                </a>
                <a href="#">
                    <i class="fas fa-map-marker-alt"></i> الأردن - عمان
                </a>
            </div>

            <div class="footer-section">
                <h3>تابعنا</h3>
                <div class="social-links">
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; 2024 Eco Friendy Store. جميع الحقوق محفوظة ❤️🐾</p>
        </div>
    </footer>

    <script>
        // Mobile Menu Functions
        function openMobileMenu() {
            document.getElementById('mobileNav').classList.add('active');
            document.getElementById('overlay').classList.add('active');
        }

        function closeMobileMenu() {
            document.getElementById('mobileNav').classList.remove('active');
            document.getElementById('overlay').classList.remove('active');
        }

        // Close menu when clicking overlay
        document.getElementById('overlay').addEventListener('click', closeMobileMenu);

        // Add to Cart Function
        function addToCart(productId) {
            // Get cart from localStorage
            let cart = JSON.parse(localStorage.getItem('cart') || '[]');
            
            // Check if product already in cart
            const existingItem = cart.find(item => item.id === productId);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({ id: productId, quantity: 1 });
            }
            
            // Save to localStorage
            localStorage.setItem('cart', JSON.stringify(cart));
            
            // Update cart count
            updateCartCount();
            
            // Show notification
            showNotification('تمت الإضافة للسلة بنجاح!');
        }

        // Update Cart Count
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cart-count').textContent = count;
        }

        // Show Notification
        function showNotification(message) {
            const notification = document.getElementById('notification');
            const notificationText = document.getElementById('notificationText');
            
            notificationText.textContent = message;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // Search Products
        function searchProducts() {
            const query = document.getElementById('searchInput').value;
            if (query.trim()) {
                window.location.href = `products.php?search=${encodeURIComponent(query)}`;
            }
        }

        // Search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });

        // Toggle Language
        function toggleLanguage() {
            // This would redirect to English version
            alert('النسخة الإنجليزية قريباً!');
        }

        // Initialize cart count on page load
        updateCartCount();
    </script>
</body>
</html>
