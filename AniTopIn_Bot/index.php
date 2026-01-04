<!DOCTYPE html>
<html lang="uz">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Saytingiz tayyor!</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #6e8efb, #a777e3);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      overflow: hidden;
      position: relative;
    }

    .logo {
      position: absolute;
      top: 20px;
      left: 20px;
    }

    .logo img {
      height: 40px;
      opacity: 0.9;
    }

    .container {
      animation: fadeIn 2s ease-in-out;
      padding: 20px;
    }

    h1 {
      font-size: 48px;
      margin-bottom: 20px;
      animation: slideIn 1.2s ease-in-out;
    }

    p {
      font-size: 20px;
      max-width: 600px;
      margin: auto;
      animation: fadeIn 2.4s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }

    @keyframes slideIn {
      from { transform: translateY(-30px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    .pulse {
      margin-top: 40px;
      display: inline-block;
      padding: 12px 25px;
      font-size: 18px;
      background: rgba(255, 255, 255, 0.15);
      border: 2px solid white;
      border-radius: 50px;
      color: white;
      text-decoration: none;
      animation: pulse 2s infinite;
      transition: all 0.3s ease;
    }

    .pulse:hover {
      background: white;
      color: #6e8efb;
    }

    @keyframes pulse {
      0% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.05); opacity: 0.85; }
      100% { transform: scale(1); opacity: 1; }
    }
  </style>
</head>
<body>
<div class="logo">
    <img src="https://goxost.net/public/assets/images/brand-logos/desktop-dark.png" alt="GoXost Logo">
  </div>
  <div class="container">
    <h1>user17.hostx.uz domeni ulandi! 🎉</h1>
    <p>Bu sahifa avtomatik tarzda yaratildi. Sizning user17.hostx.uz domeningiz tizimga ulandi. Endi siz o'z loyixalaringizni ushbu domenda ishga tushurishingiz mumkin.</p>
    <a class="pulse" href="https://goxost.net">Bosh sahifaga qaytish</a>
  </div>
</body>
</html>
