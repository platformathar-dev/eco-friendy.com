const express = require('express');
const path = require('path');
const app = express();

// ⚙️ الإعدادات الأساسية
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// 📁 تحديد مجلد الملفات العامة
app.use(express.static('public'));

// 🏠 الصفحة الرئيسية - المتجر الكامل
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// 📝 صفحة التسجيل
app.get('/register', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'register.html'));
});

// 🔑 صفحة تسجيل الدخول
app.get('/login', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'login.html'));
});

// 👨‍💼 لوحة تحكم الأدمن
app.get('/admin', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'admin-dashboard.html'));
});

// 🛒 صفحة المنتج الواحد
app.get('/product/:id', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'product-details.html'));
});

// 🛍️ صفحة السلة
app.get('/cart', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'cart.html'));
});

// 📦 صفحة الطلبات
app.get('/orders', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'orders.html'));
});

// 👤 صفحة الملف الشخصي
app.get('/profile', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'profile.html'));
});

// ℹ️ صفحة من نحن
app.get('/about', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'about.html'));
});

// 📞 صفحة اتصل بنا
app.get('/contact', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'contact.html'));
});

// ====================
// 🔌 API ENDPOINTS
// ====================

// 🛒 API للمنتجات - جلب كل المنتجات
app.get('/api/products', (req, res) => {
  const products = [
    {
      id: 1,
      name: 'زجاجة مياه قابلة لإعادة الاستخدام',
      price: 15.99,
      originalPrice: 19.99,
      category: 'مستلزمات يومية',
      image: 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?w=500&q=80',
      rating: 4.8,
      reviews: 234,
      discount: 20,
      inStock: true,
      isNew: true,
      description: 'زجاجة مياه عالية الجودة من الفولاذ المقاوم للصدأ',
      features: ['خالية من BPA', 'عزل حراري 24 ساعة', 'صديقة للبيئة']
    },
    {
      id: 2,
      name: 'حقيبة تسوق قماشية عضوية',
      price: 12.50,
      originalPrice: 14.70,
      category: 'حقائب',
      image: 'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?w=500&q=80',
      rating: 4.9,
      reviews: 189,
      discount: 15,
      inStock: true,
      isBestSeller: true,
      description: 'حقيبة تسوق قابلة لإعادة الاستخدام من القطن العضوي',
      features: ['قطن عضوي 100%', 'تتحمل حتى 15 كجم', 'قابلة للغسل']
    },
    {
      id: 3,
      name: 'فرشاة أسنان خشبية طبيعية',
      price: 8.99,
      originalPrice: 8.99,
      category: 'العناية الشخصية',
      image: 'https://images.unsplash.com/photo-1607613009820-a29f7bb81c04?w=500&q=80',
      rating: 4.7,
      reviews: 156,
      discount: 0,
      inStock: true,
      isEcoChoice: true,
      description: 'فرشاة أسنان من خشب الخيزران الطبيعي',
      features: ['خشب خيزران 100%', 'قابلة للتحلل', 'شعيرات ناعمة']
    },
    {
      id: 4,
      name: 'مجموعة أدوات طعام خيزران',
      price: 22.99,
      originalPrice: 28.99,
      category: 'مطبخ',
      image: 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?w=500&q=80',
      rating: 4.6,
      reviews: 98,
      discount: 21,
      inStock: true,
      isNew: true,
      description: 'مجموعة كاملة من أدوات الطعام المحمولة',
      features: ['خيزران طبيعي', 'حقيبة حمل', 'مثالية للسفر']
    },
    {
      id: 5,
      name: 'شامبو طبيعي بالأعشاب',
      price: 18.50,
      originalPrice: 18.50,
      category: 'العناية بالشعر',
      image: 'https://images.unsplash.com/photo-1608248543803-ba4f8c70ae0b?w=500&q=80',
      rating: 4.9,
      reviews: 312,
      discount: 0,
      inStock: true,
      isBestSeller: true,
      description: 'شامبو صلب خالٍ من المواد الكيميائية',
      features: ['100% طبيعي', 'خالي من السلفات', 'يدوم 3 أشهر']
    },
    {
      id: 6,
      name: 'مصاصات معدنية قابلة لإعادة الاستخدام',
      price: 9.99,
      originalPrice: 12.99,
      category: 'مستلزمات يومية',
      image: 'https://images.unsplash.com/photo-1625772452859-1c03d5bf1137?w=500&q=80',
      rating: 4.8,
      reviews: 267,
      discount: 23,
      inStock: true,
      isEcoChoice: true,
      description: 'مجموعة من 4 مصاصات من الفولاذ المقاوم للصدأ',
      features: ['4 قطع + فرشاة تنظيف', 'آمنة للاستخدام', 'سهلة التنظيف']
    },
    {
      id: 7,
      name: 'صابون طبيعي بزيت الزيتون',
      price: 6.50,
      originalPrice: 6.50,
      category: 'العناية الشخصية',
      image: 'https://images.unsplash.com/photo-1600857062241-98e5dba60b7f?w=500&q=80',
      rating: 4.7,
      reviews: 189,
      discount: 0,
      inStock: true,
      isNew: false,
      description: 'صابون صنع يدوي من زيت الزيتون البكر',
      features: ['صنع يدوي', '100% طبيعي', 'مناسب لجميع أنواع البشرة']
    },
    {
      id: 8,
      name: 'كوب قهوة عازل للحرارة',
      price: 24.99,
      originalPrice: 32.99,
      category: 'مستلزمات يومية',
      image: 'https://images.unsplash.com/photo-1571019613576-2b22c76fd955?w=500&q=80',
      rating: 4.9,
      reviews: 445,
      discount: 24,
      inStock: true,
      isBestSeller: true,
      description: 'كوب قهوة محمول بعزل حراري مزدوج',
      features: ['عزل 6 ساعات', 'غطاء محكم', 'تصميم عصري']
    },
    {
      id: 9,
      name: 'فوط قماشية قابلة لإعادة الاستخدام',
      price: 14.99,
      originalPrice: 14.99,
      category: 'منتجات نسائية',
      image: 'https://images.unsplash.com/photo-1582735689369-4fe89db7114c?w=500&q=80',
      rating: 4.8,
      reviews: 178,
      discount: 0,
      inStock: true,
      isEcoChoice: true,
      description: 'مجموعة من 6 فوط قماشية عضوية',
      features: ['قطن عضوي', 'قابلة للغسل', 'صحية وآمنة']
    },
    {
      id: 10,
      name: 'فرشاة تنظيف خشبية متعددة الاستخدام',
      price: 11.50,
      originalPrice: 11.50,
      category: 'مطبخ',
      image: 'https://images.unsplash.com/photo-1563295797-5fe8d0aef92d?w=500&q=80',
      rating: 4.6,
      reviews: 134,
      discount: 0,
      inStock: true,
      isNew: true,
      description: 'فرشاة تنظيف للأطباق من ألياف جوز الهند',
      features: ['خشب طبيعي', 'ألياف نباتية', 'قابلة للتحلل']
    },
    {
      id: 11,
      name: 'مناديل قماشية عضوية',
      price: 16.99,
      originalPrice: 21.25,
      category: 'مستلزمات يومية',
      image: 'https://images.unsplash.com/photo-1621607424803-c1c5e8e96d68?w=500&q=80',
      rating: 4.7,
      reviews: 203,
      discount: 20,
      inStock: true,
      isEcoChoice: true,
      description: 'مناديل قماشية ناعمة بديلة للورقية',
      features: ['قطن عضوي', 'مجموعة 12 قطعة', 'قابلة للغسل']
    },
    {
      id: 12,
      name: 'شاحن طاقة شمسية محمول',
      price: 45.99,
      originalPrice: 59.99,
      category: 'إلكترونيات',
      image: 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500&q=80',
      rating: 4.5,
      reviews: 167,
      discount: 23,
      inStock: true,
      isNew: true,
      description: 'شاحن محمول بالطاقة الشمسية 20000mAh',
      features: ['طاقة شمسية', 'مقاوم للماء', '2 منفذ USB']
    }
  ];

  res.json({
    success: true,
    products: products,
    total: products.length
  });
});

// 🔍 API للبحث عن المنتجات
app.get('/api/products/search', (req, res) => {
  const { q, category, minPrice, maxPrice, sort } = req.query;
  
  res.json({
    success: true,
    message: 'نتائج البحث',
    query: q
  });
});

// 📦 API لجلب منتج واحد
app.get('/api/products/:id', (req, res) => {
  const productId = parseInt(req.params.id);
  
  res.json({
    success: true,
    product: {
      id: productId,
      name: 'منتج تجريبي',
      price: 19.99
    }
  });
});

// 📝 API التسجيل
app.post('/api/register', (req, res) => {
  const { fullname, email, phone, password } = req.body;
  
  console.log('📥 تم استقبال بيانات تسجيل جديدة:');
  console.log('الاسم:', fullname);
  console.log('البريد:', email);
  console.log('الهاتف:', phone);
  
  res.json({
    success: true,
    message: 'تم إنشاء الحساب بنجاح! 🎉',
    user: {
      fullname,
      email,
      phone
    }
  });
});

// 🔐 API تسجيل الدخول
app.post('/api/login', (req, res) => {
  const { identifier, password, remember } = req.body;
  
  console.log('🔐 محاولة تسجيل دخول:');
  console.log('المعرف:', identifier);
  console.log('تذكرني:', remember);
  
  res.json({
    success: true,
    message: 'تم تسجيل الدخول بنجاح! 🎉',
    user: {
      fullname: 'مستخدم تجريبي',
      email: identifier
    }
  });
});

// 🛒 API إضافة للسلة
app.post('/api/cart/add', (req, res) => {
  const { productId, quantity } = req.body;
  
  console.log(`🛒 إضافة المنتج ${productId} للسلة - الكمية: ${quantity}`);
  
  res.json({
    success: true,
    message: 'تم إضافة المنتج للسلة',
    cartCount: 1
  });
});

// 🗑️ API حذف من السلة
app.delete('/api/cart/:productId', (req, res) => {
  const productId = req.params.productId;
  
  res.json({
    success: true,
    message: 'تم حذف المنتج من السلة'
  });
});

// 📦 API إنشاء طلب
app.post('/api/orders', (req, res) => {
  const { items, shippingAddress, paymentMethod } = req.body;
  
  console.log('📦 طلب جديد:', items);
  
  res.json({
    success: true,
    message: 'تم إنشاء الطلب بنجاح',
    orderId: 'ORD-' + Date.now()
  });
});

// 📧 API رسائل التواصل
app.post('/api/contact', (req, res) => {
  const { name, email, phone, subject, message } = req.body;
  
  console.log('📧 رسالة جديدة من:', name);
  console.log('الموضوع:', subject);
  console.log('الرسالة:', message);
  
  res.json({
    success: true,
    message: 'تم إرسال رسالتك بنجاح! سنتواصل معك قريباً 📧'
  });
});

// ⭐ API التقييمات
app.post('/api/reviews', (req, res) => {
  const { productId, rating, comment } = req.body;
  
  res.json({
    success: true,
    message: 'شكراً لتقييمك! ⭐'
  });
});

// 🚀 تشغيل السيرفر
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log('✅ السيرفر يعمل على المنفذ: ' + PORT);
  console.log('🌐 افتح المتصفح على: http://localhost:' + PORT);
  console.log('🛒 المتجر الإلكتروني جاهز للاستخدام!');
  console.log('');
  console.log('📌 الصفحات المتاحة:');
  console.log('   🏠 الرئيسية: http://localhost:' + PORT);
  console.log('   📝 التسجيل: http://localhost:' + PORT + '/register');
  console.log('   🔑 تسجيل الدخول: http://localhost:' + PORT + '/login');
  console.log('   👨‍💼 لوحة الأدمن: http://localhost:' + PORT + '/admin');
  console.log('   🛍️ السلة: http://localhost:' + PORT + '/cart');
  console.log('   📦 الطلبات: http://localhost:' + PORT + '/orders');
  console.log('   ℹ️ من نحن: http://localhost:' + PORT + '/about');
  console.log('   📞 اتصل بنا: http://localhost:' + PORT + '/contact');
  console.log('');
  console.log('🔌 API Endpoints:');
  console.log('   GET  /api/products - جلب كل المنتجات');
  console.log('   GET  /api/products/:id - جلب منتج واحد');
  console.log('   POST /api/register - تسجيل مستخدم جديد');
  console.log('   POST /api/login - تسجيل الدخول');
  console.log('   POST /api/cart/add - إضافة للسلة');
  console.log('   POST /api/orders - إنشاء طلب');
  console.log('   POST /api/contact - إرسال رسالة');
});
