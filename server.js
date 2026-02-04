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
      <title>Eco Friendly Store - المتجر البيئي</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          min-height: 100vh;
          display: flex;
          justify-content: center;
          align-items: center;
          color: white;
          text-align: center;
        }
        .container {
          background: rgba(255,255,255,0.1);
          padding: 60px 40px;
          border-radius: 20px;
          backdrop-filter: blur(10px);
          box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { font-size: 48px; margin-bottom: 20px; }
        p { font-size: 20px; margin-bottom: 40px; opacity: 0.9; }
        .buttons { display: flex; gap: 20px; justify-content: center; }
        a {
          display: inline-block;
          padding: 15px 40px;
          background: white;
          color: #667eea;
          text-decoration: none;
          border-radius: 10px;
          font-weight: bold;
          font-size: 18px;
          transition: all 0.3s ease;
        }
        a:hover {
          transform: translateY(-3px);
          box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
      </style>
    </head>
    <body>
      <div class="container">
        <h1>🌱 Eco Friendly Store</h1>
        <p>متجرك البيئي الصديق للكوكب</p>
        <div class="buttons">
          <a href="/register">إنشاء حساب جديد</a>
          <a href="/login">تسجيل الدخول</a>
        </div>
      </div>
    </body>
    </html>
  `);
});

// 📝 صفحة التسجيل
app.get('/register', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'register.html'));
});

// ✅ استقبال بيانات التسجيل
app.post('/api/register', (req, res) => {
  const { fullname, username, email, phone, birthdate, gender, country, password } = req.body;
  
  console.log('📥 تم استقبال بيانات تسجيل جديدة:');
  console.log('الاسم:', fullname);
  console.log('اسم المستخدم:', username);
  console.log('البريد:', email);
  console.log('الهاتف:', phone);
  
  res.json({
    success: true,
    message: 'تم إنشاء الحساب بنجاح! 🎉',
    user: {
      fullname,
      username,
      email,
      phone
    }
  });
});

// 🔑 صفحة تسجيل الدخول
app.get('/login', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'login.html'));
});

// ✅ استقبال بيانات تسجيل الدخول
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

// 🚀 تشغيل السيرفر
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log('✅ السيرفر يعمل على المنفذ: ' + PORT);
  console.log('🌐 افتح المتصفح على: http://localhost:' + PORT);
});
```

6. **اضغط "Commit changes"**

---

### الخطوة 2️⃣: إعادة النشر في Hostinger

1. **اذهب إلى لوحة تحكم Hostinger**

2. **ابحث عن قسم:**
   - "Website" أو "الموقع"
   - ثم "Git" أو "GitHub"

3. **اضغط زر "Redeploy"** أو **"إعادة النشر"** أو **"Pull Changes"**

4. **انتظر 2-3 دقائق**

---

### الخطوة 3️⃣: اختبر مرة أخرى

افتح في المتصفح:
```
https://darkorange-pigeon-799805.hostingersite.com/login
