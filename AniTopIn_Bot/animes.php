<!DOCTYPE html>
<html lang="uz">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Anime Ro'yxati</title>
  <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    * {
      box-sizing: border-box;
      font-family: 'Rubik', sans-serif;
    }
    body {
      margin: 0;
      background: linear-gradient(to right, #0f172a, #1e3a8a);
    }
    .card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      background: linear-gradient(145deg, #1e293b, #334155);
    }
    .card:hover {
      transform: translateY(-8px);
      box-shadow: 0 16px 32px rgba(0, 0, 0, 0.5);
    }
    .watch-btn {
      transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.5);
    }
    .watch-btn:hover {
      background-color: #1d4ed8;
      transform: scale(1.05);
      box-shadow: 0 8px 20px rgba(59, 130, 246, 0.7);
    }
    .header {
      animation: fadeIn 1s ease-in-out;
    }
    @keyframes fadeIn {
      0% { opacity: 0; transform: translateY(-20px); }
      100% { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body class="min-h-screen text-white p-4 md:p-8">
  <div class="max-w-7xl mx-auto">
    <h2 class="header text-3xl md:text-4xl font-bold text-center mb-10 text-blue-300 drop-shadow-lg">🎬 Anime Ro'yxati</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <?php
      include 'sql.php';

      // Bot tokenni bu yerda o'zgartiring
      $bot_token = '7339268021:AAEsW5DaEe-eyBQOZHyQyiKShwl1shvAC6A';

      function getFilePath($file_id, $bot_token) {
        $api_url = "https://api.telegram.org/bot$bot_token/getFile?file_id=$file_id";
        $result = file_get_contents($api_url);
        $result_json = json_decode($result, true);

        if ($result_json['ok']) {
          return $result_json['result']['file_path'];
        }
        return null;
      }

      $sql = mysqli_query($connect, "SELECT * FROM animelar ORDER BY id DESC");
      while($row = mysqli_fetch_assoc($sql)) {
        $id = htmlspecialchars($row['id']);
        $nom = htmlspecialchars($row['nom']);
        $file_id = htmlspecialchars($row['rams']);
        $qismi = htmlspecialchars($row['qismi']);
        $davlat = htmlspecialchars($row['davlat']);
        $tili = htmlspecialchars($row['tili']);
        $yili = htmlspecialchars($row['yili']);
        $janr = htmlspecialchars($row['janri']);

        $file_path = getFilePath($file_id, $bot_token);
        $file_link = $file_path ? "https://api.telegram.org/file/bot$bot_token/$file_path" : '';
        $telegram_link = "https://t.me/anizona_rasmiy_bot?start=$id";

        $media_html = '';
        if ($file_link) {
          if (substr($file_path, -4) === '.mp4') {
            $media_html = "<video controls class='w-full h-48 object-cover rounded-t-xl'><source src='$file_link' type='video/mp4'>Video ko‘rsatilmayapti.</video>";
          } else {
            $media_html = "<img src='$file_link' alt='$nom' class='w-full h-48 object-cover rounded-t-xl'>";
          }
        } else {
          $media_html = "<div class='w-full h-48 flex items-center justify-center bg-red-950 rounded-t-xl text-red-300'>❌ Fayl yuklanmadi</div>";
        }

        echo "
          <a href='$telegram_link' class='card rounded-xl overflow-hidden cursor-pointer'>
            $media_html
            <div class='p-5'>
              <h3 class='text-lg font-semibold text-blue-200 mb-3'>$nom</h3>
              <p class='text-sm text-blue-300 mb-1'>🎞 Qismlar: $qismi</p>
              <p class='text-sm text-blue-300 mb-1'>🌍 Davlat: $davlat</p>
              <p class='text-sm text-blue-300 mb-1'>🗣 Til: $tili</p>
              <p class='text-sm text-blue-300 mb-1'>📅 Yili: $yili</p>
              <p class='text-sm text-blue-300 mb-4'>🏷 Janr: $janr</p>
              <button onclick='window.location.href=\"$telegram_link\"' class='watch-btn w-full bg-blue-500 text-white font-medium py-2 rounded-lg text-center'>Tomosha qilish</button>
            </div>
          </a>
        ";
      }
      ?>
    </div>
  </div>
</body>
</html>