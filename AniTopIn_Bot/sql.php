<?php

$host = "gondola.proxy.rlwy.net";
$user = "root";
$pass = "qrNCyVGeNPfJGzHGkDRrzZvuzYIdFcbD";
$db = "railway";
$port = 37280;

$connect = mysqli_connect($host, $user, $pass, $db, $port);

if (!$connect) {
    die("Ulanishda xato: " . mysqli_connect_error());
}

// Charset o'rnatish
mysqli_set_charset($connect, "utf8mb4");

// Yordamchi funksiya: so'rovni bajar va xatoni chop et
function run_query($conn, $sql, $name = '') {
    if (!mysqli_query($conn, $sql)) {
        die("Xato ($name): " . mysqli_error($conn) . "\nSQL: " . $sql);
    }
}

// 1. Foydalanuvchilar jadvali
run_query($connect, "CREATE TABLE IF NOT EXISTS `user_id` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(250) NOT NULL,
  `status` text NOT NULL,
  `refid` varchar(11) NOT NULL,
  `sana` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", 'user_id');

// 2. Anime qismlari
run_query($connect, "CREATE TABLE IF NOT EXISTS `anime_datas` (
  `data_id` int(11) NOT NULL AUTO_INCREMENT,
  `id` text NOT NULL,
  `file_id` text NOT NULL,
  `qism` text NOT NULL,
  `sana` text DEFAULT NULL,
  PRIMARY KEY (`data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", 'anime_datas');

// 3. Animelar jadvali
run_query($connect, "CREATE TABLE IF NOT EXISTS `animelar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` text NOT NULL,
  `rams` text NOT NULL,
  `qismi` text NOT NULL,
  `davlat` text NOT NULL,
  `tili` text NOT NULL,
  `yili` text NOT NULL,
  `janri` text NOT NULL,
  `qidiruv` text NOT NULL,
  `sana` text NOT NULL,
  `aniType` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", 'animelar');

// 4. Send jadvali (send_id avtomatik oshishi uchun AUTO_INCREMENT qo'shildi)
run_query($connect, "CREATE TABLE IF NOT EXISTS `send` (
  `send_id` int(11) NOT NULL AUTO_INCREMENT,
  `step` text NOT NULL,
  `message_id` text NOT NULL,
  PRIMARY KEY (`send_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", 'send');

// 5. Kabinet jadvali - tuzatilgan (bir martada bitta yaratish)
run_query($connect, "CREATE TABLE IF NOT EXISTS `kabinet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(250) NOT NULL,
  `pul` varchar(250) DEFAULT '0',
  `ban` varchar(250) DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;", 'kabinet');

echo "Baza va barcha jadval(lar) muvaffaqiyatli sozlandi!";
?>
