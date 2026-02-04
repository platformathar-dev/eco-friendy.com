const express = require('express');
const path = require('path');
const app = express();

app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static('public'));

app.get('/', (req, res) => {
  res.send(`
    <!DOCTYPE html>
    <html lang="ar" dir="rtl">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Eco Friendly Store</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
          font-family: Arial, sans-serif;
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
        p { font-size: 20px; margin-bottom: 40px; }
        .buttons { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
        a {
          padding: 15px 40px;
          background: white;
          color: #667eea;
          text-decoration: none;
          border-radius: 10px;
          font-weight: bold;
          font-size: 18px;
          transition: all 0.3s ease;
        }
        a:hover { transform: translateY(-3px); }
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

app.get('/register', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'register.html'));
});

app.post('/api/register', (req, res) => {
  const { fullname, username, email, phone } = req.body;
  console.log('تسجيل جديد:', fullname, email);
  res.json({
    success: true,
    message: 'تم إنشاء الحساب بنجاح',
    user: { fullname, username, email, phone }
  });
});

app.get('/login', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'login.html'));
});

app.post('/api/login', (req, res) => {
  const { identifier, password } = req.body;
  console.log('تسجيل دخول:', identifier);
  res.json({
    success: true,
    message: 'تم تسجيل الدخول بنجاح',
    user: { fullname: 'مستخدم', email: identifier }
  });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log('Server running on port ' + PORT);
});
