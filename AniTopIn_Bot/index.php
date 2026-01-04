<?php
/*--- BOT QISMI (Telegram xabarlarini qabul qiladi) ---*/

// 1. Ma'lumotlar bazasi ma'lumotlari
$host = "gondola.proxy.rlwy.net";
$user = "root";
$pass = "qrNCyVGeNPfJGzHGkDRrzZvuzYIdFcbD";
$db = "railway";
$port = 37280;

$connect = mysqli_connect($host, $user, $pass, $db, $port);

// 2. Bot tokeni
define('API_KEY', '8589253414:AAEJdsGBR69w4VUtQIRRZagPK385qAURR_o');

function bot($method, $datas = []){
    $url = "https://api.telegram.org/bot".API_KEY."/".$method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    return json_decode($res);
}

// 3. Kelayotgan xabarlarni qabul qilish
$update = json_decode(file_get_contents('php://input'));

if (isset($update->message)) {
    $message = $update->message;
    $chat_id = $message->chat->id;
    $text = $message->text;
    $name = $message->from->first_name;
    $user_id = $message->from->id;

    // Foydalanuvchini bazaga qo'shish
    $check = mysqli_query($connect, "SELECT * FROM user_id WHERE user_id = '$user_id'");
    if (mysqli_num_rows($check) == 0) {
        $sana = date("d.m.Y H:i:s");
        mysqli_query($connect, "INSERT INTO user_id (user_id, status, refid, sana) VALUES ('$user_id', 'user', '0', '$sana')");
        mysqli_query($connect, "INSERT INTO kabinet (user_id, pul, ban) VALUES ('$user_id', '0', 'active')");
    }

    if ($text == "/start") {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "<b>Salom $name!</b>\n\nAniNowuz botingiz muvaffaqiyatli ishga tushdi! 🎉",
            'parse_mode' => 'html'
        ]);
    }
    // Bu yerda bot kodini tugatdik, die() qilmasak pastdagi HTML ham Telegramga ketib qolishi mumkin
    exit(); 
}

/*--- VIZUAL QISM (Brauzerda ochilganda ko'rinadigan qism) ---*/
?>
<!DOCTYPE html>
<html lang="uz">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bot Serveri Ishlamoqda</title>
  <style>
    body {
      height: 100vh;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #1e3c72, #2a5298);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      margin: 0;
    }
    .container { padding: 20px; }
    h1 { font-size: 40px; }
    .status { color: #00ff00; font-weight: bold; }
  </style>
</head>
<body>
  <div class="container">
    <h1>AniNowuz Bot Serveri 🎉</h1>
    <p>Botingiz hozirda <span class="status">ON-LINE</span> holatda!</p>
    <p>Webhook manzili: <code>https://aninowuz-bot.onrender.com/index.php</code></p>
  </div>
</body>
</html>
