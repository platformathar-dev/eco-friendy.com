// main.js - الوظائف الرئيسية

// فتح القائمة الجانبية
function openMobileMenu() {
    const mobileNav = document.getElementById('mobileNav');
    const overlay = document.getElementById('overlay');
    
    if (mobileNav && overlay) {
        mobileNav.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        overlay.onclick = closeMobileMenu;
    }
}

// إغلاق القائمة الجانبية
function closeMobileMenu() {
    const mobileNav = document.getElementById('mobileNav');
    const overlay = document.getElementById('overlay');
    
    if (mobileNav && overlay) {
        mobileNav.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

// تبديل اللغة
function toggleLanguage() {
    const html = document.documentElement;
    const currentDir = html.getAttribute('dir');
    const newDir = currentDir === 'rtl' ? 'ltr' : 'rtl';
    html.setAttribute('dir', newDir);
    
    // تحديث نص زر اللغة في جميع الأماكن
    const langButtons = document.querySelectorAll('.lang-btn span');
    langButtons.forEach(btn => {
        btn.textContent = newDir === 'rtl' ? 'English' : 'العربية';
    });
}

// البحث عن منتجات
function searchProducts() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    const query = searchInput.value.trim();
    if (query) {
        window.location.href = `products.html?search=${encodeURIComponent(query)}`;
    }
}

// الاستماع لضغط Enter في البحث
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                searchProducts();
            }
        });
    }
});

// إنشاء HTML لبطاقة منتج
function createProductCard(product, isRelated = false) {
    const discount = product.originalPrice 
        ? Math.round(((product.originalPrice - product.price) / product.originalPrice) * 100)
        : 0;
    
    const badgesHTML = product.badges.map(badge => {
        const badgeText = {
            'new': 'جديد',
            'sale': 'خصم',
            'eco': 'بيئي'
        }[badge] || badge;
        return `<span class="badge-${badge}">${badgeText}</span>`;
    }).join('');
    
    const stars = '★'.repeat(Math.floor(product.rating)) + 
                  (product.rating % 1 ? '½' : '') + 
                  '☆'.repeat(5 - Math.ceil(product.rating));
    
    const priceHTML = product.originalPrice
        ? `<span class="current-price">${product.price} دينار</span>
           <span class="original-price">${product.originalPrice}</span>
           <span class="discount-badge">-${discount}%</span>`
        : `<span class="current-price">${product.price} دينار</span>`;
    
    const cardClass = isRelated ? 'product-card related-card' : 'product-card';
    
    return `
        <div class="${cardClass}">
            ${badgesHTML ? `<div class="product-badges">${badgesHTML}</div>` : ''}
            <img src="${product.image}" class="product-image" alt="${product.name}">
            <div class="product-info">
                <div class="product-category">${getCategoryName(product.category)}</div>
                <h3 class="product-name">${product.name}</h3>
                <div class="product-rating">
                    <span class="stars">${stars}</span>
                    <span class="reviews">(${product.reviews})</span>
                </div>
                <div class="product-price">
                    ${priceHTML}
                </div>
                <button class="add-to-cart" onclick="addToCart(${product.id})">
                    <i class="fas fa-shopping-cart"></i>
                    ${isRelated ? 'أضف' : 'أضف للسلة'}
                </button>
            </div>
        </div>
    `;
}

// الحصول على اسم الفئة بالعربي
function getCategoryName(category) {
    const names = {
        'dogs': 'كلاب',
        'cats': 'قطط',
        'birds': 'طيور',
        'fish': 'أسماك',
        'rabbits': 'أرانب',
        'hamsters': 'هامسترات'
    };
    return names[category] || category;
}

// تحميل المنتجات في الصفحة الرئيسية
function loadHomeProducts() {
    const latestProducts = document.getElementById('latestProducts');
    const relatedSlider = document.getElementById('relatedSlider');
    
    if (latestProducts) {
        // عرض أول 4 منتجات في قسم "منتجات أحدث"
        const latest = productsData.slice(0, 4);
        latestProducts.innerHTML = latest.map(p => createProductCard(p)).join('');
    }
    
    if (relatedSlider) {
        // عرض منتجات عشوائية في السلايدر
        const related = getRandomProducts(6);
        relatedSlider.innerHTML = related.map(p => createProductCard(p, true)).join('');
        
        // بدء التمرير التلقائي
        startAutoScroll(relatedSlider);
    }
}

// تمرير تلقائي للسلايدر
function startAutoScroll(slider) {
    if (!slider) return;
    
    let scrollInterval = setInterval(() => {
        if (slider.scrollLeft >= slider.scrollWidth - slider.clientWidth - 10) {
            slider.scrollLeft = 0;
        } else {
            slider.scrollLeft += 1;
        }
    }, 30);
    
    // إيقاف التمرير عند التفاعل
    slider.addEventListener('mouseenter', () => clearInterval(scrollInterval));
    slider.addEventListener('touchstart', () => clearInterval(scrollInterval));
    
    slider.addEventListener('mouseleave', () => {
        scrollInterval = setInterval(() => {
            if (slider.scrollLeft >= slider.scrollWidth - slider.clientWidth - 10) {
                slider.scrollLeft = 0;
            } else {
                slider.scrollLeft += 1;
            }
        }, 30);
    });
}

// تحميل المنتجات للفئات
function startCategoriesAutoScroll() {
    const categoriesSlider = document.getElementById('categoriesSlider');
    if (!categoriesSlider) return;
    
    let scrollInterval = setInterval(() => {
        if (categoriesSlider.scrollLeft >= categoriesSlider.scrollWidth - categoriesSlider.clientWidth - 10) {
            categoriesSlider.scrollLeft = 0;
        } else {
            categoriesSlider.scrollLeft += 1;
        }
    }, 30);
    
    categoriesSlider.addEventListener('mouseenter', () => clearInterval(scrollInterval));
    categoriesSlider.addEventListener('touchstart', () => clearInterval(scrollInterval));
    
    categoriesSlider.addEventListener('mouseleave', () => {
        scrollInterval = setInterval(() => {
            if (categoriesSlider.scrollLeft >= categoriesSlider.scrollWidth - categoriesSlider.clientWidth - 10) {
                categoriesSlider.scrollLeft = 0;
            } else {
                categoriesSlider.scrollLeft += 1;
            }
        }, 30);
    });
}

// تهيئة الصفحة عند التحميل
document.addEventListener('DOMContentLoaded', () => {
    // تحميل المنتجات في الصفحة الرئيسية
    if (document.getElementById('latestProducts')) {
        loadHomeProducts();
    }
    
    // بدء التمرير التلقائي للفئات
    startCategoriesAutoScroll();
    
    // Smooth scroll للروابط
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '#!') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                    closeMobileMenu();
                }
            }
        });
    });
});