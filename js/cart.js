// cart.js - إدارة السلة والمفضلة

// السلة
let cart = JSON.parse(localStorage.getItem('cart')) || [];
let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];

// تحديث عدادات السلة والمفضلة
function updateCounters() {
    const cartCount = document.getElementById('cart-count');
    const wishlistCount = document.getElementById('wishlist-count');
    
    if (cartCount) {
        cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
    }
    
    if (wishlistCount) {
        wishlistCount.textContent = wishlist.length;
    }
}

// إضافة منتج للسلة
function addToCart(productId) {
    const product = getProductById(productId);
    if (!product) return;
    
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            ...product,
            quantity: 1
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCounters();
    showNotification('تمت الإضافة إلى السلة! ✅', 'success');
    return true;
}

// إزالة منتج من السلة
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCounters();
    showNotification('تم حذف المنتج من السلة', 'info');
}

// تحديث كمية منتج في السلة
function updateCartQuantity(productId, quantity) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        item.quantity = parseInt(quantity);
        if (item.quantity <= 0) {
            removeFromCart(productId);
        } else {
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCounters();
        }
    }
}

// الحصول على السلة
function getCart() {
    return cart;
}

// حساب إجمالي السلة
function getCartTotal() {
    return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
}

// تفريغ السلة
function clearCart() {
    cart = [];
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCounters();
}

// إضافة للمفضلة
function addToWishlist(productId) {
    const product = getProductById(productId);
    if (!product) return;
    
    if (wishlist.find(item => item.id === productId)) {
        showNotification('المنتج موجود بالفعل في المفضلة', 'info');
        return;
    }
    
    wishlist.push(product);
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
    updateCounters();
    showNotification('تمت الإضافة إلى المفضلة! ❤️', 'success');
}

// إزالة من المفضلة
function removeFromWishlist(productId) {
    wishlist = wishlist.filter(item => item.id !== productId);
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
    updateCounters();
    showNotification('تم حذف المنتج من المفضلة', 'info');
}

// الحصول على المفضلة
function getWishlist() {
    return wishlist;
}

// التحقق من وجود منتج في المفضلة
function isInWishlist(productId) {
    return wishlist.some(item => item.id === productId);
}

// إظهار إشعار
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    const notificationText = document.getElementById('notificationText');
    
    if (!notification || !notificationText) return;
    
    notificationText.textContent = message;
    notification.className = 'notification show';
    
    if (type === 'success') {
        notification.style.background = '#10b981';
    } else if (type === 'error') {
        notification.style.background = '#ef4444';
    } else {
        notification.style.background = '#3b82f6';
    }
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}

// تحديث العدادات عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', updateCounters);
