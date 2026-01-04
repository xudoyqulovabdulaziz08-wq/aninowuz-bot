<?php
$servername = "gondola.proxy.rlwy.net";
$username = "root";
$password = "qrNCyVGeNPfJGzHGkDRrzZvuzYIdFcbD";
$dbname = "railway"; 
$port = 37280;

$connect = mysqli_connect($servername, $username, $password, $dbname, $port);

if (!$connect) {
    die("Ulanishda xatolik: " . mysqli_connect_error());
}

// 1. Foydalanuvchilar jadvali
mysqli_query($connect,"CREATE TABLE IF NOT EXISTS `user_id` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(250) NOT NULL,
  `status` text NOT NULL,
  `refid` varchar(11) NOT NULL,
  `sana` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 2. Anime qismlari
mysqli_query($connect,"CREATE TABLE IF NOT EXISTS `anime_datas` (
  `data_id` int(11) NOT NULL AUTO_INCREMENT,
  `id` text NOT NULL,
  `file_id` text NOT NULL,
  `qism` text NOT NULL,
  `sana` text DEFAULT NULL,
  PRIMARY KEY (`data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 3. Animelar jadvali
mysqli_query($connect,"CREATE TABLE IF NOT EXISTS `animelar` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 4. Send jadvali
mysqli_query($connect,"CREATE TABLE IF NOT EXISTS `send` (
  `send_id` int(11) NOT NULL,
  `step` text NOT NULL,
  `message_id` text NOT NULL,
  PRIMARY KEY(`send_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 5. Kabinet jadvali - TUZATILGAN QISMI:
// ban ustunini VARCHAR qildik, shunda DEFAULT ishlaydi
mysqli_query($connect,"CREATE TABLE IF NOT EXISTS `kabinet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(250) NOT NULL,
  `pul` varchar(250) DEFAULT '0',
  `ban` varchar(250) DEFAULT 'active', 
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

echo "Baza va barcha 5 ta jadval muvaffaqiyatli sozlandi!";
?>
