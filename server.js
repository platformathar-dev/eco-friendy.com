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
      name: 'طعام كلاب رويال كانين 15 كجم',
      nameEn: 'Royal Canin Dog Food 15kg',
      price: 65.99,
      originalPrice: 79.99,
      category: 'كلاب',
      categoryEn: 'Dogs',
      image: 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=500&q=80',
      rating: 4.8,
      reviews: 234,
      discount: 18,
      inStock: true,
      isNew: false,
      isBestSeller: true,
      description: 'طعام جاف متوازن للكلاب البالغة، غني بالبروتين',
      descriptionEn: 'Balanced dry food for adult dogs, rich in protein'
    },
    {
      id: 2,
      name: 'رمل قطط بنتونايت 10 لتر',
      nameEn: 'Bentonite Cat Litter 10L',
      price: 12.50,
      originalPrice: 15.00,
      category: 'قطط',
      categoryEn: 'Cats',
      image: 'https://images.unsplash.com/photo-1425082661705-1834bfd09dca?w=500&q=80',
      rating: 4.7,
      reviews: 189,
      discount: 17,
      inStock: true,
      isNew: false,
      description: 'رمل طبيعي ممتاز للقطط، سهل التنظيف',
      descriptionEn: 'Premium natural cat litter, easy to clean'
    },
    {
      id: 3,
      name: 'قفص عصافير معدني كبير',
      nameEn: 'Large Metal Bird Cage',
      price: 45.00,
      originalPrice: 45.00,
      category: 'طيور',
      categoryEn: 'Birds',
      image: 'https://images.unsplash.com/photo-1552728089-57bdde30beb3?w=500&q=80',
      rating: 4.9,
      reviews: 156,
      discount: 0,
      inStock: true,
      isNew: true,
      description: 'قفص واسع ومريح لطيورك مع ملحقات كاملة',
      descriptionEn: 'Spacious and comfortable cage for your birds with full accessories'
    },
    {
      id: 4,
      name: 'حوض سمك زجاجي 60 لتر',
      nameEn: '60L Glass Fish Tank',
      price: 85.00,
      originalPrice: 110.00,
      category: 'أسماك',
      categoryEn: 'Fish',
      image: 'https://images.unsplash.com/photo-1520990427726-2c5482f1e5b6?w=500&q=80',
      rating: 4.6,
      reviews: 98,
      discount: 23,
      inStock: true,
      isNew: true,
      description: 'حوض سمك شفاف مع نظام فلترة متكامل',
      descriptionEn: 'Crystal clear fish tank with integrated filtration system'
    },
    {
      id: 5,
      name: 'طعام قطط ويسكاس 1.5 كجم',
      nameEn: 'Whiskas Cat Food 1.5kg',
      price: 18.50,
      originalPrice: 18.50,
      category: 'قطط',
      categoryEn: 'Cats',
      image: 'https://images.unsplash.com/photo-1574158622682-e40e69881006?w=500&q=80',
      rating: 4.9,
      reviews: 312,
      discount: 0,
      inStock: true,
      isBestSeller: true,
      description: 'طعام جاف للقطط بطعم السمك، غني بالفيتامينات',
      descriptionEn: 'Dry cat food with fish flavor, rich in vitamins'
    },
    {
      id: 6,
      name: 'مقود كلاب قوي مع طوق',
      nameEn: 'Strong Dog Leash with Collar',
      price: 15.99,
      originalPrice: 19.99,
      category: 'كلاب',
      categoryEn: 'Dogs',
      image: 'https://images.unsplash.com/photo-1601758228041-f3b2795255f1?w=500&q=80',
      rating: 4.8,
      reviews: 267,
      discount: 20,
      inStock: true,
      description: 'مقود متين ومريح للمشي مع كلبك',
      descriptionEn: 'Durable and comfortable leash for walking your dog'
    },
    {
      id: 7,
      name: 'بيت خشبي للأرانب',
      nameEn: 'Wooden Rabbit Hutch',
      price: 55.00,
      originalPrice: 55.00,
      category: 'أرانب',
      categoryEn: 'Rabbits',
      image: 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?w=500&q=80',
      rating: 4.7,
      reviews: 89,
      discount: 0,
      inStock: true,
      isNew: true,
      description: 'بيت خشبي واسع ومريح للأرانب',
      descriptionEn: 'Spacious and comfortable wooden hutch for rabbits'
    },
    {
      id: 8,
      name: 'عجلة تمارين للهامستر',
      nameEn: 'Hamster Exercise Wheel',
      price: 12.99,
      originalPrice: 16.99,
      category: 'هامسترات',
      categoryEn: 'Hamsters',
      image: 'https://images.unsplash.com/photo-1425082661705-1834bfd09dca?w=500&q=80',
      rating: 4.5,
      reviews: 145,
      discount: 24,
      inStock: true,
      description: 'عجلة تمارين هادئة وآمنة للهامستر',
      descriptionEn: 'Quiet and safe exercise wheel for hamsters'
    },
    {
      id: 9,
      name: 'فرشاة تمشيط للكلاب والقطط',
      nameEn: 'Grooming Brush for Dogs & Cats',
      price: 14.99,
      originalPrice: 14.99,
      category: 'كلاب',
      categoryEn: 'Dogs',
      image: 'https://images.unsplash.com/photo-1623387641168-d9803ddd3f35?w=500&q=80',
      rating: 4.8,
      reviews: 178,
      discount: 0,
      inStock: true,
      description: 'فرشاة احترافية لتمشيط وتنظيف شعر حيوانك',
      descriptionEn: 'Professional brush for grooming and cleaning your pet\'s fur'
    },
    {
      id: 10,
      name: 'طعام طيور متنوع 2 كجم',
      nameEn: 'Mixed Bird Food 2kg',
      price: 11.50,
      originalPrice: 11.50,
      category: 'طيور',
      categoryEn: 'Birds',
      image: 'https://images.unsplash.com/photo-1444464666168-49d633b86797?w=500&q=80',
      rating: 4.6,
      reviews: 134,
      discount: 0,
      inStock: true,
      description: 'خليط متوازن من البذور المغذية للطيور',
      descriptionEn: 'Balanced mix of nutritious seeds for birds'
    },
    {
      id: 11,
      name: 'ألعاب قطط تفاعلية - طقم 5 قطع',
      nameEn: 'Interactive Cat Toys - 5 Pieces',
      price: 16.99,
      originalPrice: 21.25,
      category: 'قطط',
      categoryEn: 'Cats',
      image: 'https://images.unsplash.com/photo-1545249390-6bdfa286032f?w=500&q=80',
      rating: 4.7,
      reviews: 203,
      discount: 20,
      inStock: true,
      description: 'مجموعة ألعاب ممتعة لتسلية قطتك',
      descriptionEn: 'Fun toy set to entertain your cat'
    },
    {
      id: 12,
      name: 'فلتر مياه أحواض السمك',
      nameEn: 'Aquarium Water Filter',
      price: 35.99,
      originalPrice: 45.99,
      category: 'أسماك',
      categoryEn: 'Fish',
      image: 'https://images.unsplash.com/photo-1535591273668-578e31182c4f?w=500&q=80',
      rating: 4.5,
      reviews: 167,
      discount: 22,
      inStock: true,
      isNew: true,
      description: 'فلتر قوي للحفاظ على نظافة مياه الحوض',
      descriptionEn: 'Powerful filter to keep aquarium water clean'
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
