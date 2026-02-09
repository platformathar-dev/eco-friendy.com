const express = require('express');
const path = require('path');
const app = express();

// ⚙️ الإعدادات الأساسية
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// 📁 تحديد مجلد الملفات العامة
app.use(express.static('public'));

// 🏠 الصفحة الرئيسية
app.get('/', (req, res) => {
  res.send(`
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>HS Syria - الصفحة الرئيسية</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { background: white; padding: 3rem; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 600px; text-align: center; }
        h1 { color: #667eea; font-size: 2.5rem; margin-bottom: 1rem; }
        p { color: #555; font-size: 1.2rem; margin-bottom: 2rem; }
        .status { background: #10b981; color: white; padding: 1rem 2rem; border-radius: 10px; display: inline-block; margin-bottom: 2rem; }
        .links { display: flex; flex-direction: column; gap: 1rem; }
        a { background: #667eea; color: white; padding: 1rem 2rem; border-radius: 10px; text-decoration: none; transition: all 0.3s; }
        a:hover { background: #764ba2; transform: translateY(-2px); }
      </style>
    </head>
    <body>
      <div class="container">
        <h1>🚚 HS Syria API</h1>
        <p>نظام إدارة الشحن والتوصيل</p>
        <div class="status">✅ السيرفر يعمل بنجاح</div>
        <div class="links">
          <a href="/api/products">📦 عرض المنتجات</a>
          <a href="/api/test">🔧 اختبار API</a>
        </div>
      </div>
    </body>
    </html>
  `);
});

// ====================
// 🔌 API ENDPOINTS
// ====================

// 🛒 API للمنتجات - جلب كل المنتجات
app.get('/api/products', (req, res) => {
  const products = [
    {
      id: 1,
      name: 'شحنة دمشق - حلب',
      nameEn: 'Damascus - Aleppo Shipment',
      price: 150,
      weight: '50kg',
      status: 'قيد التوصيل',
      statusEn: 'In Transit'
    },
    {
      id: 2,
      name: 'شحنة حمص - اللاذقية',
      nameEn: 'Homs - Latakia Shipment',
      price: 120,
      weight: '30kg',
      status: 'تم التسليم',
      statusEn: 'Delivered'
    },
    {
      id: 3,
      name: 'شحنة طرطوس - دمشق',
      nameEn: 'Tartus - Damascus Shipment',
      price: 180,
      weight: '75kg',
      status: 'قيد الانتظار',
      statusEn: 'Pending'
    }
  ];

  res.json({
    success: true,
    message: 'تم جلب البيانات بنجاح',
    products: products,
    total: products.length
  });
});

// 🔍 API اختبار
app.get('/api/test', (req, res) => {
  res.json({
    success: true,
    message: 'HS Syria API is running! 🚚',
    timestamp: new Date().toISOString(),
    server: 'sy.hs-exp.net'
  });
});

// 📦 API لجلب شحنة واحدة
app.get('/api/products/:id', (req, res) => {
  const shipmentId = parseInt(req.params.id);
  
  res.json({
    success: true,
    shipment: {
      id: shipmentId,
      name: 'شحنة تجريبية',
      price: 150,
      status: 'قيد التوصيل'
    }
  });
});

// 📝 API إنشاء شحنة جديدة
app.post('/api/shipments', (req, res) => {
  const { from, to, weight, price } = req.body;
  
  res.json({
    success: true,
    message: 'تم إنشاء الشحنة بنجاح! 📦',
    shipment: {
      id: Date.now(),
      from,
      to,
      weight,
      price,
      status: 'قيد الانتظار'
    }
  });
});

// 📧 API التواصل
app.post('/api/contact', (req, res) => {
  const { name, email, phone, message } = req.body;
  
  res.json({
    success: true,
    message: 'تم إرسال رسالتك بنجاح! سنتواصل معك قريباً 📧'
  });
});

// 🚀 تشغيل السيرفر
const PORT = process.env.PORT || 3000;
app.listen(PORT, '0.0.0.0', () => {
  console.log('✅ HS Syria Server is running on port: ' + PORT);
  console.log('🌐 Server: http://sy.hs-exp.net');
  console.log('');
  console.log('🔌 API Endpoints:');
  console.log('   GET  /api/products - جلب كل الشحنات');
  console.log('   GET  /api/products/:id - جلب شحنة واحدة');
  console.log('   POST /api/shipments - إنشاء شحنة جديدة');
  console.log('   POST /api/contact - إرسال رسالة');
  console.log('   GET  /api/test - اختبار API');
});
