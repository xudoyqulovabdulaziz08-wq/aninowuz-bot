<?php
ob_start();
error_reporting(0);
date_default_timezone_set('Asia/Tashkent');



$bot_token = "8589253414:AAEJdsGBR69w4VUtQIRRZagPK385qAURR_o"; // bot token

define('API_KEY',$bot_token);
$obito_us = "8244870375"; // admin_id
$admins = file_get_contents("admin/admins.txt");
$admin = explode("\n",$admins);
$studio_name = file_get_contents("admin/studio_name.txt");
array_push($admin,$obito_us,2025400572);
$user = file_get_contents("admin/user.txt");
$bot = bot('getme',['bot'])->result->username;
$soat = date('H:i');
$sana = date("d.m.Y");

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];
$folder_path = rtrim(dirname($uri), '/\\');
$host_no_www = preg_replace('/^www\./', '', $host);
$web_urlis = "$protocol://$host_no_www$folder_path/animes.php";

require ("sql.php");

function getAdmin($chat){
$url = "https://api.telegram.org/bot".API_KEY."/getChatAdministrators?chat_id=@".$chat;
$result = file_get_contents($url);
$result = json_decode ($result);
return $result->ok;
}

function deleteFolder($path){
if(is_dir($path) === true){
$files = array_diff(scandir($path), array('.', '..'));
foreach ($files as $file)
deleteFolder(realpath($path) . '/' . $file);
return rmdir($path);
}else if (is_file($path) === true)
return unlink($path);
return false;
}


function joinchat($userId, $key = null) {
    global $connect, $bot, $token, $status, $bot_token;

    if ($status == 'VIP') return true;

    $userId = strval($userId);
    $query = $connect->query("SELECT channelId, channelType, channelLink FROM channels");
    if ($query->num_rows === 0) return true;

    $noSubs = 0;
    $buttons = [];
    $channels = $query->fetch_all(MYSQLI_ASSOC);

    foreach ($channels as $channel) {
        $channelId = $channel['channelId'];
        $channelLink = $channel['channelLink'];
        $channelType = $channel['channelType'];

        if ($channelType === "request") {
            $check = $connect->query("SELECT * FROM joinRequests WHERE BINARY channelId = '$channelId' AND BINARY userId = '$userId'");
            
            if ($check->num_rows === 0) {

                $connect->query("INSERT INTO joinRequests (channelId, userId) VALUES ('$channelId', '$userId')");

                $noSubs++;
                $buttons[] = [
                    'text' => "📨 So‘rov yuborish ($noSubs)",
                    'url'  => "https://t.me/$bot?start=joinreq_$channelId"
                ];
            }
        } else {
            $chatMember = bot('getChatMember', [
                'chat_id' => $channelId,
                'user_id' => $userId
            ]);

            if (!isset($chatMember->result->status) || $chatMember->result->status === "left") {
                $noSubs++;
                $chatInfo = bot('getChat', ['chat_id' => $channelId]);
                $channelTitle = $chatInfo->result->title ?? "Kanal";
                $buttons[] = [
                    'text' => $channelTitle,
                    'url'  => $channelLink
                ];
            }
        }
    }

    if ($noSubs > 0) {
        $insta = get('admin/instagram.txt');
        $youtube = get('admin/youtube.txt');

        if (!empty($insta)) {
            $buttons[] = ['text' => "📸 Instagram", 'url' => $insta];
        } elseif (!empty($youtube)) {
            $buttons[] = ['text' => "📺 YouTube", 'url' => $youtube];
        }
        
        $callback = !empty($key) ? "chack=" . $key : "panel";
        $buttons[] = ['text' => "✅ Tekshirish", 'callback_data' => $callback];

        sms($userId, "<b>Botdan foydalanish uchun quyidagi kanallarga obuna bo'ling yoki so‘rov yuboring❗️</b>", json_encode([
            'inline_keyboard' => array_chunk($buttons, 1)
        ]));

        exit();
    }

    return true;
}





function accl($d,$s,$j=false){
return bot('answerCallbackQuery',[
'callback_query_id'=>$d,
'text'=>$s,
'show_alert'=>$j
]);
}

function del(){
global $cid,$mid,$cid2,$mid2;
return bot('deleteMessage',[
'chat_id'=>$cid2.$cid,
'message_id'=>$mid2.$mid,
]);
}


function edit($id,$mid,$tx,$m){
return bot('editMessageText',[
'chat_id'=>$id,
'message_id'=>$mid,
'text'=>$tx,
'parse_mode'=>"HTML",
'disable_web_page_preview'=>true,
'reply_markup'=>$m,
]);
}



function sms($id,$tx,$m){
return bot('sendMessage',[
'chat_id'=>$id,
'text'=>$tx,
'parse_mode'=>"HTML",
'disable_web_page_preview'=>true,
'reply_markup'=>$m,
]);
}



function get($h){
return file_get_contents($h);
}

function put($h,$r){
file_put_contents($h,$r);
}

function bot($method,$datas=[]){
	$url = "https://api.telegram.org/bot".API_KEY."/".$method;
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
	$res = curl_exec($ch);
	if(curl_error($ch)){
		var_dump(curl_error($ch));
	}else{
		return json_decode($res);
	}
}

function process_anime($cid, $id) {
    global $connect, $anime_kanal;

    // ✅ Faqat raqamli ID bo‘lsa ishlaydi
    if (!is_numeric($id)) {
        sms($cid, "❗ Noto‘g‘ri ID kiritildi.");
        return;
    }

    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE id = $id"));

    if ($rew) {
        $file_id = $rew['rams'];
        $first_char = strtoupper($file_id[0]);
        $media_type = ($first_char == 'B') ? 'sendVideo' : 'sendPhoto';
        $media_key = ($first_char == 'B') ? 'video' : 'photo';

        $cs = $rew['qidiruv'] + 1;
        mysqli_query($connect, "UPDATE animelar SET qidiruv = $cs WHERE id = $id");

        bot($media_type, [
            'chat_id' => $cid,
            $media_key => $file_id,
            'caption' => "<b>🎬 Nomi: {$rew['nom']}</b>

🎥 Qismi: {$rew['qismi']}
🌍 Davlati: {$rew['davlat']}
🇺🇿 Tili: {$rew['tili']}
📆 Yili: {$rew['yili']}
🎞 Janri: {$rew['janri']}

🔍 Qidirishlar soni: $cs

🍿 $anime_kanal",
            'parse_mode' => "html",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "📥 Yuklab olish", 'callback_data' => "yuklanolish=$id=1"]]
                ]
            ]),
            'protect_content'=>$content,
        ]);
    } else {
        sms($cid, "<!--  -->❌ Ma'lumot topilmadi.");
    }
}



function containsEmoji($string) {
	// Emoji Unicode diapazonlarini belgilash
	$emojiPattern = '/[\x{1F600}-\x{1F64F}]/u'; // Emotikonlar
	$emojiPattern .= '|[\x{1F300}-\x{1F5FF}]'; // Belgilar va piktograflar
	$emojiPattern .= '|[\x{1F680}-\x{1F6FF}]'; // Transport va xaritalar
	$emojiPattern .= '|[\x{1F700}-\x{1F77F}]'; // Alkimyo belgilar
	$emojiPattern .= '|[\x{1F780}-\x{1F7FF}]'; // Har xil belgilar
	$emojiPattern .= '|[\x{1F800}-\x{1F8FF}]'; // Suv belgilari
	$emojiPattern .= '|[\x{1F900}-\x{1F9FF}]'; // Odatdagilar
	$emojiPattern .= '|[\x{1FA00}-\x{1FA6F}]'; // Qisqichbaqasimon belgilar
	$emojiPattern .= '|[\x{2600}-\x{26FF}]';   // Turli xil belgilar va piktograflar
	$emojiPattern .= '|[\x{2700}-\x{27BF}]';   // Dingbatlar
	$emojiPattern .= '/u';
 
	// Regex orqali tekshirish
	return preg_match($emojiPattern, $string) === 1;
}

function removeEmoji($string) {
	$emojiPattern = '/[\x{1F600}-\x{1F64F}]/u'; // Emotikonlar
	$emojiPattern .= '|[\x{1F300}-\x{1F5FF}]'; // Belgilar va piktograflar
	$emojiPattern .= '|[\x{1F680}-\x{1F6FF}]'; // Transport va xaritalar
	$emojiPattern .= '|[\x{1F700}-\x{1F77F}]'; // Alkimyo belgilar
	$emojiPattern .= '|[\x{1F780}-\x{1F7FF}]'; // Har xil belgilar
	$emojiPattern .= '|[\x{1F800}-\x{1F8FF}]'; // Suv belgilari
	$emojiPattern .= '|[\x{1F900}-\x{1F9FF}]';
	$emojiPattern .= '|[\x{1FA00}-\x{1FA6F}]';
	$emojiPattern .= '|[\x{2600}-\x{26FF}]';  
	$emojiPattern .= '|[\x{2700}-\x{27BF}]';   
	$emojiPattern .= '/u';
 
	return preg_replace($emojiPattern, '', $string);
}

function adminsAlert($message){
global $admin;
foreach($admin as $adm){
sms($adm,$message,null);
}
}

$alijonov = json_decode(file_get_contents('php://input'));
$message = $alijonov->message;
$cid = $message->chat->id;
$name = $message->chat->first_name;
$tx = $message->text;
$step = file_get_contents("step/$cid.step");
$steps = file_get_contents("steps/$cid.steps");
$mid = $message->message_id;
$type = $message->chat->type;
$text = $message->text;
$uid= $message->from->id;
$name = $message->from->first_name;
$familya = $message->from->last_name;
$bio = $message->from->about;
$username = $message->from->username;
$chat_id = $message->chat->id;
$message_id = $message->message_id;
$reply = $message->reply_to_message->text;
$nameru = "<a href='tg://user?id=$uid'>$name $familya</a>";

$botdel = $alijonov->my_chat_member->new_chat_member; 
$botdelid = $alijonov->my_chat_member->from->id; 
$userstatus= $botdel->status; 

//inline uchun metodlar
$data = $alijonov->callback_query->data;
$qid = $alijonov->callback_query->id;
$id = $alijonov->inline_query->id;
$query = $alijonov->inline_query->query;
$query_id = $alijonov->inline_query->from->id;
$cid2 = $alijonov->callback_query->message->chat->id;
$mid2 = $alijonov->callback_query->message->message_id;
$callfrid = $alijonov->callback_query->from->id;
$callname = $alijonov->callback_query->from->first_name;
$calluser = $alijonov->callback_query->from->username;
$surname = $alijonov->callback_query->from->last_name;
$about = $alijonov->callback_query->from->about;
$nameuz = "<a href='tg://user?id=$callfrid'>$callname $surname</a>";

if(isset($data)){
$chat_id=$cid2;
$message_id=$mid2;
}

$photo = $message->photo;
$file = $photo[count($photo)-1]->file_id;

//tugmalar
if(file_get_contents("tugma/key1.txt")){
	}else{
		if(file_put_contents("tugma/key1.txt","🔎 Anime izlash"));
	}
if(file_get_contents("tugma/key2.txt")){
	}else{
		if(file_put_contents("tugma/key2.txt","💎 VIP"));
	}
if(file_get_contents("tugma/key3.txt")){
	}else{
		if(file_put_contents("tugma/key3.txt","💰 Hisobim"));
	}
if(file_get_contents("tugma/key4.txt")){
	}else{
		if(file_put_contents("tugma/key4.txt","➕ Pul kiritish"));
	}
if(file_get_contents("tugma/key5.txt")){
	}else{
		if(file_put_contents("tugma/key5.txt","📚 Qo'llanma"));
	}
if(file_get_contents("tugma/key6.txt")){
	}else{
		if(file_put_contents("tugma/key6.txt","💵 Reklama va Homiylik"));
	}
	
//pul va referal sozlamalar

if(file_get_contents("admin/valyuta.txt")){
	}else{
		if(file_put_contents("admin/valyuta.txt","so'm"));
}

if(file_get_contents("admin/vip.txt")){
	}else{
		if(file_put_contents("admin/vip.txt","25000"));
}

if(file_get_contents("admin/holat.txt")){
	}else{
		if(file_put_contents("admin/holat.txt","Yoqilgan"));
}

if(file_exists("admin/anime_kanal.txt")==false){
file_put_contents("admin/anime_kanal.txt","@username");
}
if(file_exists("tizim/content.txt")==false){
file_put_contents("tizim/content.txt","false");
}

//matnlar
if(file_get_contents("matn/start.txt")){
}else{
if(file_put_contents("matn/start.txt","✨"));
}

$res = mysqli_query($connect,"SELECT*FROM user_id WHERE user_id=$chat_id");
while($a = mysqli_fetch_assoc($res)){
$user_id = $a['user_id'];
$status = $a['status'];
$taklid_id = $a['refid'];
$from_id = $a['id'];
$usana = $a['sana'];
}

$res = mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id=$chat_id");
while($a = mysqli_fetch_assoc($res)){
$k_id = $a['user_id'];
$pul = $a['pul'];
$pul2 = $a['pul2'];
$odam = $a['odam'];
$ban = $a['ban'];
}

$key1 = file_get_contents("tugma/key1.txt");
$key2 = file_get_contents("tugma/key2.txt");
$key3 = file_get_contents("tugma/key3.txt");
$key4 = file_get_contents("tugma/key4.txt");
$key5 = file_get_contents("tugma/key5.txt");
$key6 = file_get_contents("tugma/key6.txt");

$test = file_get_contents("step/test.txt");
$test1 = file_get_contents("step/test1.txt");
$test2 = file_get_contents("step/test2.txt");
$turi = file_get_contents("tizim/turi.txt");
$anime_kanal = file("admin/anime_kanal.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$narx = file_get_contents("admin/vip.txt");
$kanal = file_get_contents("admin/kanal.txt");
$valyuta = file_get_contents("admin/valyuta.txt");
$start = str_replace(["%first%","%id%","%botname%","%hour%","%date%"], [$name,$cid,$bot,$soat,$sana],file_get_contents("matn/start.txt"));
$qollanma = str_replace(["%first%","%id%","%hour%","%date%","%user%","%botname%",], [$name,$cid,$soat,$sana,$user,$bot],file_get_contents("matn/qollanma.txt"));
$from_id = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM user_id WHERE user_id = $cid2"))['id'];
$pul3 = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $cid2"))['pul'];
$odam2 = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $cid2"))['odam'];
$photo = file_get_contents("matn/photo.txt");
$homiy = file_get_contents("matn/homiy.txt");
$holat = file_get_contents("admin/holat.txt");
$content = get("tizim/content.txt");

mkdir("tizim");
mkdir("step");
mkdir("admin");
mkdir("tugma");
mkdir("matn");

$panel = json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"*️⃣ Birlamchi sozlamalar"]],
[['text'=>"📊 Statistika"],['text'=>"✉ Xabar Yuborish"]],
[['text'=>"📬 Post tayyorlash"]],
[['text'=>"🎥 Animelar sozlash"],['text'=>"💳 Hamyonlar"]],
[['text'=>"🔎 Foydalanuvchini boshqarish"]],
[['text'=>"📢 Kanallar"],['text'=>"🎛 Tugmalar"],['text'=>"📃 Matnlar"]],
[['text'=>"📋 Adminlar"],['text'=>"🤖 Bot holati"]],
[['text'=>"◀️ Orqaga"]]
]
]);

$asosiy = $panel;

$menu = json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"$key1"]],
[['text'=>"$key2"],['text'=>"$key3"]],
[['text'=>"$key4"],['text'=>"$key5"]],
[['text'=>"$key6"]],
]
]);

$menus = json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"$key1"]],
[['text'=>"$key2"],['text'=>"$key3"]],
[['text'=>"$key4"],['text'=>"$key5"]],
[['text'=>"$key6"]],
[['text'=>"🗄 Boshqarish"]],
]
]);

$back = json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"◀️ Orqaga"]],
]
]);

$boshqarish = json_encode([
'resize_keyboard'=>true,
'keyboard'=>[
[['text'=>"🗄 Boshqarish"]],
]
]);

if(in_array($cid,$admin)){
$menyu = $menus;
}else{
$menyu = $menu;
}

if(in_array($cid2,$admin)){
$menyus = $menus;
}else{
$menyus = $menu;
}

//<---- @obito_us ---->//
// Kod @ITACHI_UCHIHA_SONO_SHARINGAN tomonidan tog'irlandi 
if($text){
if($ban == "ban"){
exit();
}
}

if($data){
$ban = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $cid2"))['ban'];
	if($ban == "ban"){
	exit();
}
}

if(isset($message)){
if(!$connect){
bot('sendMessage',[
'chat_id' =>$cid,
'text'=>"⚠️ <b>Xatolik!</b>

<i>Botdan ro'yxatdan o'tish uchun, /start buyrug'ini yuboring!</i>",
'parse_mode' =>'html',
]);
exit();
}
}

if($text){
 if($holat == "O'chirilgan"){
	if(in_array($cid,$admin)){
}else{
	bot('sendMessage',[
	'chat_id'=>$cid,
	'text'=>"⛔️ <b>Bot vaqtinchalik o'chirilgan!</b>

<i>Botda ta'mirlash ishlari olib borilayotgan bo'lishi mumkin!</i>",
'parse_mode'=>'html',
]);
exit();
}
}
}

if($data){
 if($holat == "O'chirilgan"){
	if(in_array($cid2,$admin)){
}else{
	bot('answerCallbackQuery',[
		'callback_query_id'=>$qid,
		'text'=>"⛔️ Bot vaqtinchalik o'chirilgan!

Botda ta'mirlash ishlari olib borilayotgan bo'lishi mumkin!",
		'show_alert'=>true,
		]);
exit();
}
}
}

if(isset($message)){
$result = mysqli_query($connect,"SELECT * FROM user_id WHERE user_id = $cid");
$row = mysqli_fetch_assoc($result);
if(!$row){
mysqli_query($connect,"INSERT INTO user_id(`user_id`,`status`,`sana`) VALUES ('$cid','Oddiy','$sana')");
}
}

if(isset($message)){
$result = mysqli_query($connect,"SELECT * FROM kabinet WHERE user_id = $cid");
$row = mysqli_fetch_assoc($result);
if(!$row){
mysqli_query($connect,"INSERT INTO kabinet(`user_id`,`pul`,`pul2`,`odam`,`ban`) VALUES ('$cid','0','0','0','unban')");
}
}

if($text == "/start" or $text=="◀️ Orqaga"){	
sms($cid,$start,$menyu);
unlink("step/$cid.step");
exit();
}

if($data == "result"){
del();
if(joinchat($cid2)==true){
sms($cid2,$start,$menyu);
exit();
}
}

$servername = "localhost";
$username = "uztopanime";
$password = "uztopanime123";
$connecting = mysqli_connect($servername, $username, $password, $username);

$result = mysqli_query($connecting,"SELECT * FROM bot WHERE user = '$bot'");
$row = mysqli_fetch_assoc($result);

if(isset($text) && $row['holat'] == 'Off'){
$tugash = date("d.m.Y");
if($row){
    
}
    exit();
}


if (strpos($data, "chack=") === 0) {
    del();
    $i = 0;

    $res = bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "<b>⏳ Tekshirilmoqda... 0%</b>",
        'parse_mode' => 'html'
    ]);

    $messing_id2 = $res->result->message_id;

    $bars = ["░░░░░░", "█░░░░░", "██░░░░", "███░░░", "████░░", "█████░", "██████"];
    foreach ($bars as $index => $bar) {
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $messing_id2,
            'text' => "<b>⏳ Tekshirilmoqda... " . ($index * 15) . "%\n$bar</b>",
            'parse_mode' => 'html',
        ]);
        usleep(400000); 
    }

    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $messing_id2
    ]);

    $id = str_replace("chack=", "", $data);
    $check = joinchat($cid2, $id);

    if ($check === true) {
        del();
        process_anime($cid2, $id);
    } else {
        del();
        sms($cid2, "⚠ Obuna aniqlanmadi. Iltimos, kanallarga obuna bo‘ling va qayta urinib ko‘ring.");
    }

    exit();
}




//<---- @obito_us ---->//

if ($text == "/help") {
    sms($cid, "ℹ Foydalanish uchun buyrug‘ingizni kiriting.");
    exit();
}

if (mb_stripos($text, "/start ") !== false && $text != "/start anipass") {
    $id = str_replace('/start ', '', $text);
    if (is_numeric($id)) show_anime($cid, $id);
}

if (strpos($data, "anime=") === 0) {
    $id = str_replace("anime=", "", $data);
    if (is_numeric($id)) show_anime($cid2, $id);
}

function show_anime($cid, $id) {
    global $connect, $anime_kanal;

    if (!joinchat($cid, $id)) {
        sms($cid, "⚠ Botdan foydalanish uchun kanalga obuna bo‘ling!");
        return;
    }

    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE id = $id"));

    if (!$rew) {
        sms($cid, "❌ Ma'lumot topilmadi.");
        return;
    }

    $file_id = $rew['rams'];
    $first_char = strtoupper($file_id[0]);

    $media_type = ($first_char == 'B') ? 'sendVideo' : 'sendPhoto';
    $media_key = ($first_char == 'B') ? 'video' : 'photo';

    $cs = $rew['qidiruv'] + 1;
    mysqli_query($connect, "UPDATE animelar SET qidiruv = $cs WHERE id = $id");

    bot($media_type, [
        'chat_id' => $cid,
        $media_key => $file_id,
        'caption' => "<b>🎬 Nomi: {$rew['nom']}</b>

🎥 Qismi: {$rew['qismi']}
🌍 Davlati: {$rew['davlat']}
🇺🇿 Tili: {$rew['tili']}
📆 Yili: {$rew['yili']}
🎞 Janri: {$rew['janri']}

🔍 Qidirishlar soni: $cs

🍿 {$anime_kanal[0]}",
        'parse_mode' => "html",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "📥 YUKLAB OLISH", 'callback_data' => "yuklanolish=$id=1"]]
            ]
        ]),
        'protect_content'=>$content,
    ]);
}



if ($data == "close")
	del();

if ($text == $key1 and joinchat($cid) == 1) {
	sms($cid, "<b>🔍Qidiruv tipini tanlang :</b>", json_encode([
		'inline_keyboard' => [
			[['text' => "🏷Anime nomi orqali", 'callback_data' => "searchByName"], ['text' => "⏱ So'ngi yuklanganlar", 'callback_data' => "lastUploads"]],
			[['text' => "💬Janr orqali qidirish", 'callback_data' => "searchByGenre"]],
			[['text' => "📌Kod orqali", 'callback_data' => "searchByCode"], ['text' => "👁️ Eng ko'p ko'rilgan", 'callback_data' => "topViewers"]],
			[['text'=>"🖼️Rasm orqali qidirish",'callback_data'=>"searchByImage"]],
			[['text' => "🌐 Web Animes", 'web_app' => ['url' => "$web_urlis"]]],
			[['text' => "🌐 ORG Coin", 'web_app' => ['url' => "https://boltayevrahmatillo42.uztan.ga/Channel/index.php"]]],
			[['text' => "📚Barcha animelar", 'callback_data' => "allAnimes"]]
		]
	]));
	exit();
}

if ($data == "searchByName") {
	sms($cid2, "<b>Anime nomini yuboring:</b>", $back);
	exit();
}

if ($data == "lastUploads") {
	if ($status == "VIP") {
		$a = $connect->query("SELECT * FROM `animelar` ORDER BY `sana` DESC LIMIT 0,10");
		$i = 1;
		while ($s = mysqli_fetch_assoc($a)) {
			$uz[] = ['text' => "$i - $s[nom]", 'callback_data' => "loadAnime=$s[id]"];
		}
		$keyboard2 = array_chunk($uz, 1);
		$kb = json_encode([
			'inline_keyboard' => $keyboard2,
		]);
		edit($cid2, $mid2, "<b>⬇️ Qidiruv natijalari:</b>", $kb);
		exit();
	} else {
		bot('answerCallbackQuery', [
			'callback_query_id' => $qid,
			'text' => "Ushbu funksiyadan foydalanish uchun $key2 sotib olishingiz zarur!",
			'show_alert' => true,
		]);
	}
}

if ($data == "searchByImage") {
    if (isset($update->message->photo)) {
        $file_id = $update->message->photo[0]->file_id;
        $file = bot('getFile', ['file_id' => $file_id]);
        $file_path = $file->result->file_path;
        $file_url = "https://api.telegram.org/file/bot" . $API_KEY . "/" . $file_path;
        
        $image = file_get_contents($file_url);
        $image_path = 'images/user_image.jpg';
        file_put_contents($image_path, $image);

        sms($cid2, "BU rasm yuborildi.", $back);

        $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE rams = '$file_id'"));

        if ($rew) {
            $anime_name = $rew['nom'];
            sms($cid2, "<b>Topilgan Anime:</b>\n" . $anime_name, $back);
        } else {
            sms($cid2, "Kechirasiz, anime topilmadi.", $back);
        }
    } else {
        sms($cid2, "Iltimos, rasm yuboring.", $back);
    }
}




//Rasm orqali qidirish 

if ($data == "topViewers") {
	if ($status == "VIP") {
		$a = $connect->query("SELECT * FROM `animelar` ORDER BY `qidiruv` ASC LIMIT 0,10");
		$i = 1;
		while ($s = mysqli_fetch_assoc($a)) {
			$uz[] = ['text' => "$i - $s[nom]", 'callback_data' => "loadAnime=$s[id]"];
			$i++;
		}
		$keyboard2 = array_chunk($uz, 1);
		$kb = json_encode([
			'inline_keyboard' => $keyboard2,
		]);
		edit($cid2, $mid2, "<b>⬇️ Qidiruv natijalari:</b>", $kb);
		exit();
	} else {
		bot('answerCallbackQuery', [
			'callback_query_id' => $qid,
			'text' => "Ushbu funksiyadan foydalanish uchun $key2 sotib olishingiz zarur!",
			'show_alert' => true,
		]);
	}
}

if(mb_stripos($data,"loadAnime=")!==false){
$n=explode("=",$data)[1];
del();
$rew = mysqli_fetch_assoc(mysqli_query($connect,"SELECT * FROM animelar WHERE id = $n"));
$media_type = ($first_char == 'B') ? 'sendVideo' : 'sendPhoto'; 
$media_key = ($first_char == 'B') ? 'video' : 'photo';
$a = mysqli_fetch_assoc(mysqli_query($connect,"SELECT * FROM `anime_datas` WHERE `id` = $n ORDER BY `qism` ASC LIMIT 1"));
if(in_array($cid2,$admin)) $delKey="🗑️ O‘chirish";
bot($media_type,[
'chat_id'=>$cid2,
$media_key=>$rew['rams'],
'caption'=>"<b>🎬 Nomi: $rew[nom]</b>

🎥 Qismi: $rew[qismi]
🌍 Davlati: $rew[davlat]
🇺🇿 Tili: $rew[tili]
📆 Yili: $rew[yili]
🎞 Janri: $rew[janri]

🔍Qidirishlar soni: $rew[qidiruv]

🍿 $anime_kanal",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"YUKLAB OLISH 📥",'callback_data'=>"yuklanolish=$n=$a[qism]"]],
[['text'=>"$delKey",'callback_data'=>"deleteAnime=$n=1"]],
]
]),
'protect_content'=>$content,
]);
}

if(mb_stripos($data,"deleteAnime=")!==false){
$n=explode("=",$data)[1];
$res=explode("=",$data)[2];
if($res=="1"){
del();
sms($cid2,"<b>❗O‘chirishga ishonchingiz komilmi?</b>",json_encode([
'inline_keyboard'=>[
[['text'=>"✅ Tasdiqlash",'callback_data'=>"deleteEpisode=$n=$nid=2"]],
[['text'=>"🔙 Orqaga",'callback_data'=>"yuklanolish=$n=$nid"]]
]]));
}elseif($res=="2"){
mysqli_query($connect,"DELETE FROM animelar WHERE id = $n");
mysqli_query($connect,"DELETE FROM anime_datas WHERE id = $n");
del();
sms($cid2,"<b>Bosh menyuga qaytdingiz,</b> anime o‘chirildi!",null);
}
}

if (mb_stripos($data, "yuklanolish=") !== false) {
    $parts = explode("=", $data);
    $anime_id = (int)$parts[1];
    $episode_number = isset($parts[2]) ? (int)$parts[2] : 1;
    $last_episode = isset($parts[3]) ? (int)$parts[3] : null;

    $offset = floor(($episode_number - 1) / 25) * 25;

    del();

    $anime = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE id = $anime_id"));
    $anime_name = $anime['nom'];

    $episode_data = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM anime_datas WHERE id = $anime_id AND qism = $episode_number"));
    if (!$episode_data) return;

    $buttons = [];
    $episodes = mysqli_query($connect, "SELECT * FROM anime_datas WHERE id = $anime_id LIMIT $offset, 25");
    while ($row = mysqli_fetch_assoc($episodes)) {
        $qism = $row['qism'];
        if ($qism == $episode_number) {
            $buttons[] = ['text' => "[📀] - $qism", 'callback_data' => "null"];
        } else {
            $buttons[] = ['text' => "$qism", 'callback_data' => "yuklanolish=$anime_id=$qism=$episode_number"];
        }
    }

    $keyboard = array_chunk($buttons, 4);
    if (in_array($cid2, $admin)) {
        $keyboard[] = [[
            'text' => "🗑 $episode_number-qismni o'chirish",
            'callback_data' => "deleteEpisode=$anime_id=$episode_number=1"
        ]];
    }

    $keyboard[] = [
        ['text' => "⬅️ Oldingi", 'callback_data' => "pagenation=$anime_id=$episode_number=back"],
        ['text' => "❌ Yopish", 'callback_data' => "close"],
        ['text' => "➡️ Keyingi", 'callback_data' => "pagenation=$anime_id=$episode_number=next"]
    ];

    $kb = json_encode(['inline_keyboard' => $keyboard]);

    bot('sendVideo', [
        'chat_id' => $cid2,
        'video' => $episode_data['file_id'],
        'caption' => "<b>$anime_name</b>\n\n$episode_number-qism",
        'parse_mode' => 'html',
        'protect_content'=>$content,
        'reply_markup' => $kb
    ]);
}

if (mb_stripos($data, "pagenation=") !== false) {
    $parts = explode("=", $data);
    $anime_id = (int)$parts[1];
    $current_episode = (int)$parts[2];
    $action = $parts[3];

    $current_page = ceil($current_episode / 25);
    $start_from = ($current_page - 1) * 25;

    if ($action === "back") {
        $start_from = max($start_from - 25, 0);
    } elseif ($action === "next") {
        $start_from += 25;
    }

    $anime = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE id = $anime_id"));
    $anime_name = $anime['nom'];

    $episodes = mysqli_query($connect, "SELECT * FROM anime_datas WHERE id = $anime_id LIMIT $start_from, 25");
    $episode_data = mysqli_fetch_all($episodes, MYSQLI_ASSOC);

    if (empty($episode_data)) {
        accl($qid, "💔 Qismlar topilmadi.", true);
        exit;
    }

    $buttons = [];
    foreach ($episode_data as $ep) {
        $ep_number = $ep['qism'];
        if ($ep_number == $current_episode) {
            $buttons[] = ['text' => "[📀] - $ep_number", 'callback_data' => "null"];
        } else {
            $buttons[] = ['text' => "$ep_number", 'callback_data' => "yuklanolish=$anime_id=$ep_number=$current_episode"];
        }
    }

    $keyboard = array_chunk($buttons, 4);
    $keyboard[] = [
        ['text' => "⬅️ Oldingi", 'callback_data' => "pagenation=$anime_id=$current_episode=back"],
        ['text' => "❌ Yopish", 'callback_data' => "close"],
        ['text' => "➡️ Keyingi", 'callback_data' => "pagenation=$anime_id=$current_episode=next"]
    ];

    $reply_markup = json_encode(['inline_keyboard' => $keyboard]);
    $first_ep = $episode_data[0];

    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $message_id
    ]);

    bot('sendVideo', [
        'chat_id' => $cid2,
        'video' => $first_ep['file_id'],
        'caption' => "<b>$anime_name</b>\n\n{$first_ep['qism']}-qism",
        'protect_content'=>$content,
        'parse_mode' => "html",
        'reply_markup' => $reply_markup
    ]);
}





if($data=="allAnimes"){
$result = mysqli_query($connect,"SELECT * FROM animelar");
$count = mysqli_num_rows($result);
$text = "$bot anime botida mavjud bo'lgan barcha animelar ro'yxati 
Barcha animelar soni : $count ta\n\n";
$counter = 1;
while($row = mysqli_fetch_assoc($result)){
$text .= "---- | $counter | ----
Anime kodi : $row[id]
Nomi : $row[nom]
Janri : $row[janri]\n\n";
$counter++;
}
put("step/animes_list_$cid2.txt",$text);
del();
bot('sendDocument',[
'chat_id'=>$cid2,
'document'=>new CURLFile("step/animes_list_$cid2.txt"),
'caption'=>"<b>📝{$bot} Anime botida mavjud bo'lgan $count ta animening ro'yxati</b>",
'parse_mode'=>"html"
]);
unlink("step/animes_list_$cid2.txt");
}

if($data=="searchByCode"){
del();
sms($cid2,"<b>📌 Anime kodini kiriting:</b>",$back);
put("step/$cid2.step",$data);
}


if($step=="searchByCode"){
$rew = mysqli_fetch_assoc(mysqli_query($connect,"SELECT * FROM animelar WHERE id = $text"));
$file_id = $rew['rams'];

        $first_char = strtoupper($file_id[0]);
        $media_type = ($first_char == 'B') ? 'video' : 'photo';

if($rew){
$media_type = ($first_char == 'B') ? 'sendVideo' : 'sendPhoto';
$media_key = ($first_char == 'B') ? 'video' : 'photo'; 
$cs = $rew['qidiruv'] + 1;
mysqli_query($connect,"UPDATE animelar SET qidiruv = $cs WHERE id = $text");
bot($media_type,[
'chat_id'=>$cid,
$media_key=>$rew['rams'],
'caption'=>"<b>🎬 Nomi: $rew[nom]</b>

🎥 Qismi: $rew[qismi]
🌍 Davlati: $rew[davlat]
🇺🇿 Tili: $rew[tili]
📆 Yili: $rew[yili]
🎞 Janri: $rew[janri]

🔍Qidirishlar soni: $cs

🍿 $anime_kanal",
'parse_mode'=>"html",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"YUKLAB OLISH 📥",'callback_data'=>"yuklanolish=$text=1"]]
]
]),
'protect_content'=>$content,
]);
exit();
}else{
sms($cid,"<b>[ $text ] kodiga tegishli anime topilmadi😔</b>

• Boshqa Kod yuboring",null);
exit();
}
}

if ($data == "searchByGenre") {
	if ($status == "VIP") {
		del();
		sms($cid2, "<b>🔍 Qidirish uchun anime janrini yuboring.</b>
📌Namuna: Syonen", $back);
		put("step/$cid2.step", $data);
	} else {
		bot('answerCallbackQuery', [
			'callback_query_id' => $qid,
			'text' => "Ushbu funksiyadan foydalanish uchun $key2 sotib olishingiz zarur!",
			'show_alert' => true,
		]);
	}
}

if ($step == "searchByGenre") {
	if (isset($text)) {
		$text = mysqli_real_escape_string($connect, $text);
		$rew = mysqli_query($connect, "SELECT * FROM animelar WHERE janri LIKE '%$text%' LIMIT 0,10");
		$c = mysqli_num_rows($rew);
		$i = 1;
		while ($a = mysqli_fetch_assoc($rew)) {
			$k[] = ['text' => "$i. $a[nom]", 'callback_data' => "loadAnime=" . $a['id']];
			$i++;
		}
		$keyboard2 = array_chunk($k, 1);
		$kb = json_encode([
			'inline_keyboard' => $keyboard2,
		]);
		if (!$c) {
			sms($cid, "<b>[ $text ] jariga tegishli anime topilmadi😔</b>

• Boshqa janrni alohida yuboring", null);
			exit();
		} else {
			bot('sendMessage', [
				'chat_id' => $cid,
				'reply_to_message_id' => $mid,
				'text' => "<b>⬇️ Qidiruv natijalari:</b>",
				'parse_mode' => "html",
				'reply_markup' => $kb
			]);
			exit();
		}
	}
}

// <---- @obito_us ---->

if(($text == $key2 or $text == "/start anipass") and joinchat($cid)==1){
if($status == "Oddiy"){
sms($cid,"<b>$key2'ga ulanish

{$key2}da qanday imkoniyatlar bor?
• VIP kanal uchun 1martalik havola beriladi
• Hech qanday reklamalarsiz botdan foydalanasiz
• Majburiy obunalik soʻralmaydi</b>

$key2 haqida batafsil Qo'llanma boʻlimidan olishiz mumkin!",json_encode([
'inline_keyboard'=>[
[['text'=>"30 kun - $narx $valyuta",'callback_data'=>"shop=30"]],
[['text'=>"60 kun - ".($narx*2)." $valyuta",'callback_data'=>"shop=60"]],
[['text'=>"90 kun - ".($narx*3)." $valyuta",'callback_data'=>"shop=90"]],
]]));
exit();
}else{
$aktiv_kun = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM `status` WHERE user_id = $cid"))['kun'];
$expire=date('d.m.Y',strtotime("+$aktiv_kun days"));
sms($cid,"<b>Siz $key2 sotib olgansiz!</b>

⏳ Amal qilish muddati $expire gacha",json_encode([
'inline_keyboard'=>[
[['text'=>"🗓️ Uzaytirish",'callback_data'=>"uzaytirish"]],
]]));
exit();
}
}

if($data=="uzaytirish"){
edit($cid2,$mid2,"<b>❗ Obunani necha kunga uzaytirmoqchisiz?</b>",json_encode([
'inline_keyboard'=>[
[['text'=>"30 kun - $narx $valyuta",'callback_data'=>"shop=30"]],
[['text'=>"60 kun - ".($narx*2)." $valyuta",'callback_data'=>"shop=60"]],
[['text'=>"90 kun - ".($narx*3)." $valyuta",'callback_data'=>"shop=90"]],
]]));
exit();
}

if(mb_stripos($data,"shop=")!==false){
$kun = explode("=",$data)[1];
$narx /= 30;
$narx *= $kun;
$pul = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $cid2"))['pul'];
if($pul >= $narx){	
if($status == "Oddiy"){
$date = date('d');
mysqli_query($connect,"INSERT INTO `status` (`user_id`,`kun`,`date`) VALUES ('$cid2', '$kun', '$date')");
mysqli_query($connect,"UPDATE `user_id` SET `status` = 'VIP' WHERE user_id = $cid2");
$a = $pul - $narx;
mysqli_query($connect,"UPDATE kabinet SET pul = $a WHERE user_id = $cid2");
edit($cid2,$mid2,"<b>💎 VIP - statusga muvaffaqiyatli o'tdingiz.</b>",null);
adminsAlert("<a href='tg://user?id=$cid2'>Foydalanuvchi</a> $kun kunlik obuna sotib oldi!");
}else{
$aktiv_kun = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM `status` WHERE user_id = $cid2"))['kun'];
$kun = $aktiv_kun + $kun;
mysqli_query($connect,"UPDATE `status` SET kun = '$kun' WHERE user_id = $cid2");
$a = $pul - $narx;
mysqli_query($connect,"UPDATE kabinet SET pul = $a WHERE user_id = $cid2");
edit($cid2,$mid2,"<b>💎 VIP - statusni muvaffaqiyatli uzaytirdingiz.</b>",null);
}	
}else{
bot('answerCallbackQuery',[
'callback_query_id'=>$qid,
'text'=>"Hisobingizda yetarli mablag' mavjud emas!",
'show_alert'=>true,
]);
}
}

if($text == $key3 and joinchat($cid)==true){
sms($cid,"#ID: <code>$cid</code>
Balans: $pul $valyuta",null);
exit();
}



if($_GET['update']=="vip"){
$res = mysqli_query($connect, "SELECT * FROM `status`");
while($a = mysqli_fetch_assoc($res)){
$id = $a['user_id'];
$kun = $a['kun'];
$date = $a['date'];
if($date != date('d')){
$day = $kun - 1;
$bugun = date('d');
if($day == "0"){
mysqli_query($connect, "DELETE `status` WHERE user_id = $id");
mysqli_query($connect,"UPDATE `user_id` SET `status` = 'Oddiy' WHERE user_id = $id");
}else{
mysqli_query($connect, "UPDATE `status` SET kun='$day',`date`='$bugun' WHERE user_id = $id");
}
}
}
echo json_encode(['status'=>true,'cron'=>"VIP users"]);
}

//<---- @obito_us ---->//

if($text == "➕ Pul kiritish" and joinchat($cid)==1){
if($turi == null){
sms($cid,"😔 To'lov tizimlari topilmadi!",null);
exit();
}else{
$turi = file_get_contents("tizim/turi.txt");
$more = explode("\n",$turi);
$soni = substr_count($turi,"\n");
$keys=[];
for ($for = 1; $for <= $soni; $for++) {
$title=str_replace("\n","",$more[$for]);
$keys[]=["text"=>"$title","callback_data"=>"pay-$title"];
}
$keysboard2 = array_chunk($keys,2);
$payment = json_encode([
'inline_keyboard'=>$keysboard2,
]);
sms($cid,"<b>💳 To'lov tizimlarni birini tanlang:</b>",$payment);
exit();
}
}

if($data == "orqa"){
$turi = file_get_contents("tizim/turi.txt");
$more = explode("\n",$turi);
$soni = substr_count($turi,"\n");
$keys=[];
for ($for = 1; $for <= $soni; $for++) {
$title=str_replace("\n","",$more[$for]);
$keys[]=["text"=>"$title","callback_data"=>"pay-$title"];
$keysboard2 = array_chunk($keys,2);
$payment = json_encode([
'inline_keyboard'=>$keysboard2,
]);
}
edit($cid2,$mid2,"Quidagilardan birini tanlang:",$payment);
}

if(mb_stripos($data, "pay-")!==false){
$ex = explode("-",$data);
$turi = $ex[1];
$addition = file_get_contents("tizim/$turi/addition.txt");
$wallet = file_get_contents("tizim/$turi/wallet.txt");
edit($cid2,$mid2,"<b>💳 To'lov tizimi:</b> $turi

	<b>Hamyon:</b> <code>$wallet</code>
	<b>Izoh:</b> <code>$cid2</code>

$addition",json_encode([
'inline_keyboard'=>[
[['text'=>"☎️ Administator",'url'=>"tg://user?id=$obito_us"]],
[['text'=>"◀️ Orqaga",'callback_data'=>"orqa"]],
]]));
}

if($text == $key5 and joinchat($cid)==1){
if($qollanma == null){
sms($cid,"<b>🙁 Qo'llanma qo'shilmagan!</b>",null);
exit();
}else{
sms($cid,$qollanma,null);
exit();
}
}

if($text == $key6 and joinchat($cid)==1){
if($homiy == null){
sms($cid,"<b>🙁 Homiylik qo'shilmagan!</b>",null);
exit();
}else{
sms($cid,$homiy,json_encode([
'inline_keyboard'=>[
[['text'=>"☎️ Administrator",'url'=>"tg://user?id=$obito_us"]]
]]));
exit();
}
}

//<----- Admin Panel ------>

if($text == "🗄 Boshqarish" || $text == "/panel"){
if(in_array($cid,$admin)){
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Admin paneliga xush kelibsiz!</b>",
'parse_mode'=>'html',
'reply_markup'=>$panel,
]);
unlink("step/$cid.step");
unlink("step/test.txt");
unlink("step/$cid.txt");
exit();
}
}

if($data == "boshqarish"){
	bot('deleteMessage',[
	'chat_id'=>$cid2,
	'message_id'=>$mid2,
	]);
	bot('SendMessage',[
	'chat_id'=>$cid2,
	'text'=>"<b>Admin paneliga xush kelibsiz!</b>",
	'parse_mode'=>'html',
	'reply_markup'=>$panel,
	]);
	exit();
}


$file_path = "admin/adschannel.txt";
$content = file_get_contents($file_path);

$updated_content = str_replace("@", "https://t.me/", $content);

file_put_contents($file_path, $updated_content);

$file_content = file_get_contents($file_path);

preg_match_all('/https:\/\/t\.me\/\S+/', $file_content, $matches);

if (count($matches[0]) > 0) {
    $channel_url = end($matches[0]); 
    file_put_contents($file_path, $channel_url); 
}


$kanallar = file("admin/anime_kanal.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

function getWebAppButton($url) {
    $statu = trim(@file_get_contents("tizim/webapp.txt"));
    if (strtolower($statu) === "on") {
        return [['text' => "🌐 Web Animes", 'url' => "$url"]];
    }
    return [];
}

function createInlineButtons($id, $web_url) {
    global $bot;
    $buttons = [
        [['text' => "🔹 Tomosha qilish 🔹", 'url' => "https://t.me/$bot?start=$id"]],
    ];

    $webAppButton = getWebAppButton($web_url);
    if (!empty($webAppButton)) {
        $buttons[] = $webAppButton;
    }

    return json_encode(['inline_keyboard' => $buttons]);
}

function createPostText($rew) {
    return "<b>✽ ──...──:•°⛩°•:──...──╮\n"
        . "🏷️ Anime nomi: </b>$rew[nom]\n"
        . "<b>🖋️ Janri:</b> $rew[janri]\n"
        . "<b>🎞️ Qismlar soni:</b> $rew[qismi]\n"
        . "<b>🎙️ Ovoz berdi:</b> $rew[aniType]\n"
        . "<b>💭 Tili:</b> $rew[tili]";
}

function sendAnimePost($chat_id, $rew, $web_url) {
    $type = strtoupper($rew['rams'][0]) === 'B' ? 'sendVideo' : 'sendPhoto';
    $key = $type === 'sendVideo' ? 'video' : 'photo';

    bot($type, [
        'chat_id' => $chat_id,
        $key => $rew['rams'],
        'caption' => createPostText($rew),
        'parse_mode' => 'html',
        'reply_markup' => createInlineButtons($rew['id'], $web_url),
        'protect_content'=>$content,
    ]);
}

function kanal_tugmalari($id) {
    global $kanallar;
    $buttons = [];

    foreach ($kanallar as $kanal) {
        $buttons[] = [['text' => "📤 $kanal ga yuborish", 'callback_data' => "sendto=$kanal|$id"]];
    }

    $buttons[] = [['text' => "📡 BARCHA kanallarga yuborish", 'callback_data' => "sendto=ALL|$id"]];
    return json_encode(['inline_keyboard' => $buttons]);
}


if ($text == "📬 Post tayyorlash" and in_array($cid, $admin)) {
    sms($cid, "<b>🆔 Anime kodini kiriting:</b>", $boshqarish);
    put("step/$cid.step", 'createPost');
    exit();
}

if ($step == "createPost" and in_array($cid, $admin)) {
    sms($cid, "📬 Qaysi kanalga yuborilsin?", kanal_tugmalari($text));
    exit();
}

if (strpos($data, "sendto=") !== false) {
    del();

    sms($cid2, "✅ Post muvoffaqatli yuborildi", null);
    list($kanal, $id) = explode("|", explode("=", $data)[1]);

    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM animelar WHERE id = $id"));
    if (!$rew) {
        sms($cid, "❌ Anime topilmadi!", null);
        exit();
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']);
    $uri = dirname($_SERVER['REQUEST_URI']);
    $web_urlis = "$protocol://$host$uri/animes.php";

    if ($kanal == "ALL") {
        foreach ($kanallar as $kanal_name) {
            sendAnimePost($kanal_name, $rew, $web_urlis);
        }
        sms($cid, "✅ Postingiz barcha kanallarga yuborildi!", $panel);
    } else {
        sendAnimePost($kanal, $rew, $web_urlis);
        sms($cid, "✅ Postingiz $kanal ga yuborildi!", $panel);
    }

    sendAnimePost($cid, $rew, $web_urlis);
    exit();
}

if($text == "🔎 Foydalanuvchini boshqarish"){
if(in_array($cid,$admin)){
	bot('SendMessage',[
	'chat_id'=>$cid,
	'text'=>"<b>Kerakli foydalanuvchining ID raqamini kiriting:</b>",
	'parse_mode'=>'html',
	'reply_markup'=>$boshqarish
	]);
file_put_contents("step/$cid.step",'iD');
exit();
}
}

if($step == "iD"){
if(in_array($cid,$admin)){
$result = mysqli_query($connect,"SELECT * FROM user_id WHERE user_id = '$text'");
$row = mysqli_fetch_assoc($result);
if(!$row){
bot('SendMessage',[
	'chat_id'=>$cid,
	'text'=>"<b>Foydalanuvchi topilmadi.</b>

Qayta urinib ko'ring:",
'parse_mode'=>'html',
]);
exit();
}else{
$pul = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $text"))['pul'];
$odam = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $text"))['odam'];
$ban = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $text"))['ban'];
$status = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM status WHERE user_id = $text"))['status'];
if($status == "Oddiy"){
	$vip = "💎 VIP ga qo'shish";
}else{
	$vip = "❌ VIP dan olish";
}
if($ban == "unban"){
	$bans = "🔔 Banlash";
}else{
	$bans = "🔕 Bandan olish";
}
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Qidirilmoqda...</b>",
'parse_mode'=>'html',
]);
bot('editMessageText',[
        'chat_id'=>$cid,
        'message_id'=>$mid + 1,
        'text'=>"<b>Qidirilmoqda...</b>",
       'parse_mode'=>'html',
]);
bot('editMessageText',[
      'chat_id'=>$cid,
     'message_id'=>$mid + 1,
'text'=>"<b>Foydalanuvchi topildi!

ID:</b> <a href='tg://user?id=$text'>$text</a>
<b>Balans: $pul $valyuta
Takliflar: $odam ta</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
	'inline_keyboard'=>[
[['text'=>"$bans",'callback_data'=>"ban-$text"]],
[['text'=>"$vip",'callback_data'=>"addvip-$text"]],
[['text'=>"➕ Pul qo'shish",'callback_data'=>"plus-$text"],['text'=>"➖ Pul ayirish",'callback_data'=>"minus-$text"]]
]
])
]);
unlink("step/$cid.step");
exit();
}
}
}

if(mb_stripos($data, "foyda-")!==false){
$id = explode("-", $data)[1];
$pul = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $id"))['pul'];
$odam = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $id"))['odam'];
$ban = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $id"))['ban'];
$status = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM status WHERE user_id = $id"))['status'];
if($status == "Oddiy"){
	$vip = "💎 VIP ga qo'shish";
}else{
	$vip = "❌ VIP dan olish";
}
if($ban == "unban"){
	$bans = "🔔 Banlash";
}else{
	$bans = "🔕 Bandan olish";
}
bot('deleteMessage',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
]);
bot('SendMessage',[
'chat_id'=>$cid2,
'text'=>"<b>Foydalanuvchi topildi!

ID:</b> <a href='tg://user?id=$id'>$id</a>
<b>Balans: $pul $valyuta
Takliflar: $odam ta</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
	'inline_keyboard'=>[
[['text'=>"$bans",'callback_data'=>"ban-$id"]],
[['text'=>"$vip",'callback_data'=>"addvip-$id"]],
[['text'=>"➕ Pul qo'shish",'callback_data'=>"plus-$id"],['text'=>"➖ Pul ayirish",'callback_data'=>"minus-$id"]]
]
])
]);
exit();
}

//<---- @obito_us ---->//

if(mb_stripos($data, "plus-")!==false){
$id = explode("-", $data)[1];
bot('editMessageText',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
'text'=>"<a href='tg://user?id=$id'>$id</a> <b>ning hisobiga qancha pul qo'shmoqchisiz?</b>",
'parse_mode'=>"html",
	'reply_markup'=>json_encode([
	'inline_keyboard'=>[
	[['text'=>"◀️ Orqaga",'callback_data'=>"foyda-$id"]]
]
])
]);
file_put_contents("step/$cid2.step","plus-$id");
}

if(mb_stripos($step, "plus-")!==false){
$id = explode("-", $step)[1];
if(in_array($cid,$admin)){
if(is_numeric($text)=="true"){
bot('sendMessage',[
'chat_id'=>$id,
'text'=>"<b>Adminlar tomonidan hisobingiz $text $valyuta to'ldirildi!</b>",
'parse_mode'=>"html",
]);
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Foydalanuvchi hisobiga $text $valyuta qo'shildi!</b>",
'parse_mode'=>"html",
'reply_markup'=>$panel,
]);
$pul = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $id"))['pul'];
$pul2 = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $id"))['pul2'];
$a = $pul + $text;
$b = $pul2 + $text;
mysqli_query($connect,"UPDATE kabinet SET pul = $a WHERE user_id = $id");
mysqli_query($connect,"UPDATE kabinet SET pul2 = $b WHERE user_id = $id");
if($cash == "Yoqilgan"){
$refid = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM user_id WHERE user_id = $id"))['refid'];
$pul3 = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $refid"))['pul'];
$c = $cashback / 100 * $text;
$jami = $pul3 + $c;
mysqli_query($connect,"UPDATE kabinet SET pul = $jami WHERE user_id = $refid");
}
bot('SendMessage',[
	'chat_id'=>$refid,
    'text'=>"💵 <b>Do'stingiz hisobini to'ldirganligi uchun sizga $cashback% cashback berildi!</b>",
	'parse_mode'=>'html',
]);
unlink("step/$cid.step");
exit();
}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Faqat raqamlardan foydalaning!</b>",
'parse_mode'=>'html',
]);
exit();
}
}
}

if(mb_stripos($data, "minus-")!==false){
$id = explode("-", $data)[1];
bot('editMessageText',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
'text'=>"<a href='tg://user?id=$id'>$id</a> <b>ning hisobiga qancha pul ayirmoqchisiz?</b>",
'parse_mode'=>"html",
	'reply_markup'=>json_encode([
	'inline_keyboard'=>[
	[['text'=>"◀️ Orqaga",'callback_data'=>"foyda-$id"]]
]
])
]);
file_put_contents("step/$cid2.step","minus-$id");
}

if(mb_stripos($step, "minus-")!==false){
$id = explode("-", $step)[1];
if(in_array($cid,$admin)){
if(is_numeric($text)=="true"){
bot('sendMessage',[
'chat_id'=>$id,
'text'=>"<b>Adminlar tomonidan hisobingizdan $text $valyuta olib tashlandi!</b>",
'parse_mode'=>"html",
]);
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Foydalanuvchi hisobidan $text $valyuta olib tashlandi!</b>",
'parse_mode'=>"html",
'reply_markup'=>$panel,
]);
$pul = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $id"))['pul'];
$a = $pul - $text;
mysqli_query($connect,"UPDATE kabinet SET pul = $a WHERE user_id = $id");
unlink("step/$cid.step");
exit();
}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Faqat raqamlardan foydalaning!</b>",
'parse_mode'=>'html',
]);
exit();
}
}
}

if(mb_stripos($data, "ban-")!==false){
$id = explode("-", $data)[1];
$ban = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM kabinet WHERE user_id = $id"))['ban'];
if($obito_us != $id){
	if($ban == "ban"){
		$text = "<b>Foydalanuvchi ($id) bandan olindi!</b>";
		mysqli_query($connect,"UPDATE kabinet SET ban = 'unban' WHERE user_id = $id");
}else{
	$text = "<b>Foydalanuvchi ($id) banlandi!</b>";
	mysqli_query($connect,"UPDATE kabinet SET ban = 'ban' WHERE user_id = $id");
}
bot('editMessageText',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
'text'=>$text,
'parse_mode'=>"html",
	'reply_markup'=>json_encode([
	'inline_keyboard'=>[
	[['text'=>"◀️ Orqaga",'callback_data'=>"foyda-$id"]]
]
])
]);
}else{
bot('answerCallbackQuery',[
'callback_query_id'=>$qid,
'text'=>"Asosiy adminlarni blocklash mumkin emas!",
'show_alert'=>true,
]);
}
}

if(mb_stripos($data, "addvip-")!==false){
$id = explode("-", $data)[1];
$status = mysqli_fetch_assoc(mysqli_query($connect,"SELECT*FROM status WHERE user_id = $id"))['status'];
if($status == "VIP"){
	$text = "<b>Foydalanuvchi ($id) VIP dan olindi!</b>";
	mysqli_query($connect,"UPDATE status SET kun = '0' WHERE user_id = $id");
	mysqli_query($connect,"UPDATE status SET status = 'Oddiy' WHERE user_id = $id");
}else{
	$text = "<b>Foydalanuvchi ($id) VIP ga qo'shildi!</b>";
	mysqli_query($connect,"UPDATE status SET kun = '30' WHERE user_id = $id");
	mysqli_query($connect,"UPDATE status SET status = 'VIP' WHERE user_id = $id");
}
bot('editMessageText',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
'text'=>$text,
'parse_mode'=>"html",
	'reply_markup'=>json_encode([
	'inline_keyboard'=>[
	[['text'=>"◀️ Orqaga",'callback_data'=>"foyda-$id"]]
]
])
]);
}

if($text == "✉ Xabar Yuborish" and in_array($cid,$admin)){
$result = mysqli_query($connect, "SELECT * FROM send");
$row = mysqli_fetch_assoc($result);
if(!$row){
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>📤 Foydalanuvchilarga yuboriladigan xabarni botga yuboring!</b>",
'parse_mode'=>'html',
'reply_markup'=>$aort
]);
put("step/$cid.step","sends");
exit;
}else{
bot('sendMessage',[
'chat_id'=>$cid,
'text'=>"<b>📑 Hozirda botda xabar yuborish jarayoni davom etmoqda. Yangi xabar yuborish uchun eski yuborilayotgan xabar barcha foydalanuvchilarga yuborilishini kuting!</b>",
'parse_mode'=>'html',
'reply_markup'=>$panel
]);
exit;
}
}

if($step== "sends" and in_array($cid,$admin)){
     unlink("step/$cid.step");
     sms($cid, "<b>✅ Xabar yuborish boshlandi!</b>", $panel);

     $query = $connect->query("SELECT * FROM kabinet");
     $delay = 0;
     
     while ($row = $query->fetch_assoc()) {
         $user_id = $row['user_id'];

         bot('forwardMessage', [
             'chat_id' => $user_id,
             'from_chat_id' => $cid,
             'message_id' => $mid
         ]);
     
         $delay++;
         if ($delay >= 30) {
             sleep(1);
             $delay = 0;
         }
     }

     sms($cid,"<b>✅ Xabar yuborish tugallandi!</b>",null);
     exit();     
}

// <---- @obito_us ---->

if($text == "📊 Statistika"){
if(in_array($cid,$admin)){
$res = mysqli_query($connect, "SELECT * FROM `kabinet`");
$stat = mysqli_num_rows($res);
$ping = sys_getloadavg()[0];
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"💡 <b>O'rtacha yuklanish:</b> <code>$ping</code>

👥 <b>Foydalanuvchilar:</b> $stat ta",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"Orqaga",'callback_data'=>"boshqarish"]]
]
])
]);
exit();
}
}

// <---- @obito_us ---->

if($text == "📢 Kanallar"){
	if(in_array($cid,$admin)){
	bot('SendMessage',[
	'chat_id'=>$cid,
	'text'=>"<b>Quyidagilardan birini tanlang:</b>",
	'parse_mode'=>'html',
	'reply_markup'=>json_encode([
	'inline_keyboard'=>[
	[['text'=>"🔐 Majburiy obunalar",'callback_data'=>"majburiy"]],
	[['text'=>"📌 Qo'shimcha kanalar",'callback_data'=>"qoshimchakanal"]],
	]
	])
	]);
	exit();
}
}

if($data == "kanallar"){
	bot('deleteMessage',[
	'chat_id'=>$cid2,
	'message_id'=>$mid2,
	]);
	bot('SendMessage',[
	'chat_id'=>$cid2,
	'text'=>"<b>Quyidagilardan birini tanlang:</b>",
	'parse_mode'=>'html',
	'reply_markup'=>json_encode([
	'inline_keyboard'=>[
	[['text'=>"🔐 Majburiy obunalar",'callback_data'=>"majburiy"]],
	[['text'=>"📌 Qo'shimcha kanalar",'callback_data'=>"qoshimchakanal"]],
]
	])
	]);
	exit();
}

/*INSTAGRAM QO'SHISH FUNKSIYASI  @ITACHI_UCHIHA_SONO_SHARINGAN TOMONIDAN ISHLAB CHIQILDI */

if($data == "qoshimchakanal"){  
     bot('editMessageText',[
        'chat_id'=>$cid2,
        'message_id'=>$mid2,
'text'=>"<b>Qo'shimcha kanallar sozlash bo'limidasiz:</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"🎥 Anime kanal",'callback_data'=>"anime-kanal"]],
[['text'=>"🎁 Ijtimoiy tarmoqlar", 'callback_data'=>"social"]],
[['text'=>"◀️ Orqaga",'callback_data'=>"kanallar"]]
]
])
]);
}

if ($data == 'social') {
    bot('editMessageText', [
        'chat_id' => $cid2,
        'message_id'=>$mid2,
        'text' => "🌐 O'zingizga kerakli 🎁 ijtimoiy tarmoqni tanlang!",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "📸 Instagram", 'callback_data' => 'channel=insta']],
                [['text' => "🎥 YouTube", 'callback_data' => 'channel=youtube']],
            ],
        ]),
    ]);
}

if (strpos($data, 'channel=') === 0) {
    $channel_name = str_replace('channel=', '', $data);

    if ($channel_name == 'insta') {
        bot('editMessageText',[
            'chat_id'=>$cid2,
            'message_id'=>$mid2,
            'text'=>"📸 Instagram ustida qanday amal bajaramiz? 👇",
            'reply_markup'=>json_encode([
            'inline_keyboard'=>[
            [['text'=>"➕ Kanal qo'shish 💬",'callback_data'=>"newchann=instaplus"],['text'=>"🗑 Kanal o'chirish ❌",'callback_data'=>"delchann=instaminus"]],
            [['text'=>"📃 Ro'yhatni ko'rish 📝", 'callback_data'=>'lists=insta']],
        ],
    ]),
]);
    } elseif ($channel_name == 'youtube') {
         bot('editMessageText',[
            'chat_id'=>$cid2,
            'message_id'=>$mid2,
            'text'=>"🎥 YouTube ustida qanday amal bajaamiz? 👇",
            'reply_markup'=>json_encode([
            'inline_keyboard'=>[
            [['text'=>"➕ Kanal qo'shish 🎬",'callback_data'=>"newchann=youtubeplus"],['text'=>"🗑 Kanal o'chirish ❌",'callback_data'=>"delchann=youtube"]],
            [['text'=>"📃 Ro'yhatni ko'rish 📝", 'callback_data'=>'lists=youtube']],
        ],
    ]),
]);
    }
    exit();
}



if (strpos($data, 'newchann=') === 0) {
    $channel_name = str_replace('newchann=', '', $data);
    if($channel_name = 'instaplus'){
        sms($cid2,"📸 <b>Instagram sahifangizga havola:</b>\n\n🌐 <a href='https://www.instagram.com/'>Instagramni ochish uchun bosing!</a> ✨",null);
        put('insta.txt','kanal');
    }elseif($channel_name = 'youtubeplus'){
        sms($cid2,"📸 <b>Instagram sahifangizga havola:</b>\n\n🌐 <a href='https://www.instagram.com/'>Instagramni ochish uchun bosing!</a> ✨",null);
        put('insta.txt','ytkanal');
    }
    exit();
}

if (strpos($data, 'delchann=') === 0) {
         $channel_name = str_replace('delchann=', '', $data);
         if($channel_name == 'instaminus'){
             $channelinsta = get('admin/instagram.txt');
             if(!empty($channelinsta)){
                  edit($cid2,$mid2,"✅ Sizning Instagram profilingiz muvaffaqiyatli o‘chirildi! 🗑️📸",null);
                     unlink('admin/instagram.txt');
             } else {
                  edit($cid2,$mid2,"📸 <b>Sizning Instagram profilingiz mavjud emas!</b> ❌",null);
             } 
         } else{
             $channelinsta = get('admin/youtube.txt');
             if(!empty($channelinsta)){
                  edit($cid2,$mid2,"✅ Sizning Youtube profilingiz muvaffaqiyatli o‘chirildi! 🗑️📸",null);
                     unlink('admin/youtube.txt');
             } else {
                  edit($cid2,$mid2,"📸 <b>Sizning Youtube profilingiz mavjud emas!</b> ❌",null);
             } 
         }
    exit();
}


$insta = get('insta.txt');

if ($insta == 'kanal' && isset($text)) {
    if (strpos($text, 'https://www.instagram.com/') !== false) {
        sms($cid, "✅ Sizning Instagram profilingiz havolasi qabul qilindi:", null);
        unlink('insta.txt');
        put('admin/instagram.txt', $text);
    } elseif (strpos($text, 'https://www.youtube.com/') !== false || strpos($text, 'https://youtu.be/') !== false) {
        sms($cid, "✅ Sizning YouTube profilingiz havolasi qabul qilindi:", null);
        unlink('insta.txt');
        put('admin/youtube.txt', $text);
    } else {
        sms($cid, "❌ Iltimos, to‘g‘ri Instagram yoki YouTube havolasini yuboring!\n\n🔹 **Instagram:** <code>https://www.instagram.com/foydalanuvchi_nomi</code>\n🔹 **YouTube:** <code>https://www.youtube.com/channel/kanal_id</code>", null);
    }
    exit();
}


     if (strpos($data, 'lists=') === 0) {
         $channel_name = str_replace('lists=', '', $data);
         if($channel_name == 'insta'){
             $channelinsta = get('admin/instagram.txt');
             if(!empty($channelinsta)){
                     edit($cid2,$mid2,"🌟 <b>Sizning Instagram profillaringiz:</b> \n\n $channelinsta",null);
             } else {
                     edit($cid2,$mid2,"🌟 <b>Sizning Instagram profilingiz mavjud emas:</b>",null);
             }
         } elseif($channel_name == 'youtube'){
             $channelinsta = get('admin/youtube.txt');
             if(!empty($channelinsta)){
                     edit($cid2,$mid2,"🌟 <b>Sizning YouTube profillaringiz:</b> \n\n $channelinsta",null);
             } else {
                 edit($cid2,$mid2,"🌟 <b>Sizning YouTube profilingiz mavjud emas:</b>",null);
             }
         }
         exit();
    } 
    
if($data == "anime-kanal" or $data == "animekanal2") {
    $step_name = ($data == "anime-kanal") ? "anime-kanal1" : "animekanal2";

    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "📢 <b>Anime kanal ustida qanday amal bajaramiz?</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard'=>[
                [['text'=>"➕ Qo'shish",'callback_data'=>'add_anime_channel']],
                [['text'=>"📃 Ro'yhat",'callback_data'=>'list_anime_channel'], ['text'=>"🗑 O'chirish",'callback_data'=>'delete_anime_channel']],
            ]
        ]),
    ]);
    file_put_contents("step/$cid2.step", $step_name);
    exit();
}

$channel_file = "admin/anime_kanal.txt";

if($data == "add_anime_channel") {
    del();
    file_put_contents("step/$cid2.step", "add_anime_channel");
    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "📨 <b>Kanal usernamesini yuboring</b>\nNamuna: <code>@kanal_username</code>",
        'parse_mode' => 'html'
    ]);
    exit();
}

if($step == "add_anime_channel" and isset($text)) {
    if(strpos($text, "@") === 0) {
        $all = file_get_contents($channel_file);
        if(mb_stripos($all, $text) === false){
            $text = trim($text);
            $all = trim($all);
            if($all != "") {
                file_put_contents($channel_file, "\n$text", FILE_APPEND);
            } else {
                file_put_contents($channel_file, $text, FILE_APPEND);
            }
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✅ <b>Kanal qo‘shildi:</b> <code>$text</code>",
                'parse_mode' => 'html',
                'reply_markup' => $panel
            ]);
        } else {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "❗️Bu kanal allaqachon mavjud!",
                'parse_mode' => 'html'
            ]);
        }
    } else {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "❗️To'g'ri formatda yuboring. Namuna: <code>@kanalim</code>",
            'parse_mode' => 'html'
        ]);
    }
    unlink("step/$cid.step");
    exit();
}


if($data == "list_anime_channel") {
    $list = file($channel_file, FILE_IGNORE_NEW_LINES);
    if(count($list) == 0){
        $text = "📃 Ro‘yxatda kanal yo‘q.";
    } else {
        $text = "📃 <b>Anime kanallar ro‘yxati:</b>\n\n";
        $i = 1;
        foreach($list as $channel){
            $text .= "$i. <code>$channel</code>\n";
            $i++;
        }
    }

    bot('editMessageText', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'text' => $text,
        'parse_mode' => 'html'
    ]);
    exit();
}

if($data == "delete_anime_channel") {
    $list = file($channel_file, FILE_IGNORE_NEW_LINES);
    if(count($list) == 0){
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => "🗑 Ro‘yxatda kanal yo‘q.",
        ]);
    } else {
        $buttons = [];
        foreach($list as $key => $val){
            $buttons[] = [['text' => $key + 1, 'callback_data' => "del_kanal_$key"]];
        }
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => "🗑 <b>O‘chirmoqchi bo‘lgan kanal raqamini tanlang:</b>",
            'parse_mode' => 'html',
            'reply_markup' => json_encode(['inline_keyboard' => $buttons])
        ]);
    }
    exit();
}

if(mb_stripos($data, "del_kanal_") !== false){
    $del_index = str_replace("del_kanal_", "", $data);
    $list = file($channel_file, FILE_IGNORE_NEW_LINES);
    if(isset($list[$del_index])){
        $removed = $list[$del_index];
        unset($list[$del_index]);
        file_put_contents($channel_file, implode("\n", $list));
        bot('editMessageText', [
            'chat_id' => $cid2,
            'message_id' => $mid2,
            'text' => "✅ <b>$removed</b> kanali o‘chirildi.",
            'parse_mode' => 'html'
        ]);
    }
    exit();
}
if ($data == "majburiy") {
    bot('editMessageText', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'text' => "<b>🔐Majburiy obunalarni sozlash bo'limidasiz:</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "➕ Qo'shish", 'callback_data' => "qoshish"]],
                [['text' => "📑 Ro'yxat", 'callback_data' => "royxat"], ['text' => "🗑 O'chirish", 'callback_data' => "ochirish"]],
                [['text' => "🔙Ortga", 'callback_data' => "kanallar"]]
            ]
        ])
    ]);
}

if ($data == "cancel" && in_array($cid2, $admin)) {
    del();
    sms($cid2, "<b>✅Bekor qilindi !</b>", $panel);
}

if ($data == "qoshish") {
    del();
    sms($cid2, "<b>💬Kanal IDsini yuboring !</b>", $boshqarish);
    file_put_contents("step/$cid2.step", "addchannel=id");
    exit();
}

if (stripos($step, "addchannel=") !== false && in_array($cid, $admin)) {
    $ty = str_replace("addchannel=", '', $step);

    if ($ty == "id" && (is_numeric($text) || stripos($text, "-100") !== false)) {
        if (stripos($text, "-100") !== false) $text = str_replace("-100", '', $text);
        $text = "-100" . $text;
        file_put_contents("step/addchannel.txt", $text);
        sms($cid, "<b>🔗Kanal havolasini kiriting !</b>", null);
        file_put_contents("step/$cid.step", "addchannel=link");
        exit();
    } elseif (stripos($text, "https://") !== false) {
        if (preg_match("~https://t\.me/|https://telegram\.dog/|https://telegram\.me/~", $text)) {
            file_put_contents("step/addchannelLink.txt", $text);
            // delkey();
            sms($cid, "<b>⚠️Ushbu kanal zayafka kanal sifatida qo'shilsinmi?</b>", json_encode([
                'inline_keyboard' => [
                    [['text' => "✅Ha", 'callback_data' => "addChannel=request"], ['text' => "❌Yo‘q", 'callback_data' => "addChannel=lock"]],
                    [['text' => "🚫Bekor qilish", 'callback_data' => "cancel"]]
                ]   
            ]));
            unlink("step/$cid2.step");
            exit();
        } else {
            sms($cid, "<b>📍Faqat Telegram uchun ishlaydi!</b>", null);
            exit();
        }
    }
}

if (stripos($data, "addChannel=") !== false && in_array($cid2, $admin)) {
    $ty = str_replace("addChannel=", '', $data);
    $channelId = file_get_contents("step/addchannel.txt");
    $channelLink = file_get_contents("step/addchannelLink.txt");

    $sql = "INSERT INTO `channels`(`channelId`, `channelType`, `channelLink`) VALUES ('$channelId', '$ty', '$channelLink')";

    if ($connect->query($sql)) {
        del();
        sms($cid2, "<b>✅Majburiy obunaga kanal ulandi!</b>", $panel);
        unlink("step/addchannel.txt");
        unlink("step/addchannelLink.txt");
    } else {
        accl($qid, "⚠️Tizimda xatolik!\n\n" . $connect->error, 1);
    }
}

if ($data == "ochirish") {
    $query = $connect->query("SELECT * FROM `channels`");

    if ($query->num_rows > 0) {
        $soni = $query->num_rows;
        $text = "<b>✂️Kanalni uzish uchun kanal raqami ustiga bosing!</b>\n";
        $co = 1;
        while ($row = $query->fetch_assoc()) {
            $text .= "\n<b>$co.</b> " . $row['channelLink'] . " | " . $row['channelType'];
            $uz[] = ['text' => "🗑️$co", 'callback_data' => "channelDelete=" . $row['id']];
            $co++;
        }
        $e = array_chunk($uz, 5);
        $e[] = [['text' => "🔙Ortga", 'callback_data' => "majburiy"]];
        $json = json_encode(['inline_keyboard' => $e]);
        $text .= "\n\n<b>Ulangan kanallar soni:</b> $soni ta";
        edit($cid2, $mid2, $text, $json);
    } else {
        accl($qid, "Hech qanday kanallar ulanmagan!", 1);
    }
}

if (stripos($data, "channelDelete=") !== false && in_array($cid2, $admin)) {
    $ty = str_replace("channelDelete=", '', $data);
    $sql = "DELETE FROM `channels` WHERE `id` = '$ty'";

    if ($connect->query($sql)) {
        accl($qid, "Kanal uzildi✔️");
        $query = $connect->query("SELECT * FROM `channels`");

        if ($query->num_rows > 0) {
            $soni = $query->num_rows;
            $text = "<b>✂️Kanalni uzish uchun kanal raqami ustiga bosing!</b>\n";
            $co = 1;
            $uz = [];
            while ($row = $query->fetch_assoc()) {
                $text .= "\n<b>$co.</b> " . $row['channelLink'] . " | " . $row['channelType'];
                $uz[] = ['text' => "🗑️$co", 'callback_data' => "channelDelete=" . $row['id']];
                $co++;
            }
            $e = array_chunk($uz, 5);
            $e[] = [['text' => "🔙Ortga", 'callback_data' => "majburiy"]];
            $json = json_encode(['inline_keyboard' => $e]);
            $text .= "\n\n<b>Ulangan kanallar soni:</b> $soni ta";
            edit($cid2, $mid2, $text, $json);
        } else {
            del();
            sms($cid2, "<b>☑️Majburiy obuna ulangan kanallar qolmadi!</b>", $panel);
        }
    } else {
        accl($qid, "⚠️Tizimda xatolik!\n\n" . $connect->error, 1);
    }
}

if ($data == "royxat") {
    $query = $connect->query("SELECT * FROM `channels`");

    if ($query->num_rows > 0) {
        $soni = $query->num_rows;
        $text = "<b>📢 Kanallar ro'yxati:</b>\n";
        $co = 1;
        while ($row = $query->fetch_assoc()) {
            $text .= "\n<b>$co.</b> " . $row['channelLink'] . " | " . $row['channelType'];
            $co++;
        }
        $text .= "\n\n<b>Ulangan kanallar soni:</b> $soni ta";
        edit($cid2, $mid2, $text, json_encode([
            'inline_keyboard' => [
                [['text' => "🔙Ortga", 'callback_data' => "majburiy"]]
            ]
        ]));
    }else accl($qid,"Hech qanday kanallar ulanmagan!",1);
}

// <---- @obito_us ---->

if($text == "📋 Adminlar"){
if(in_array($cid,$admin)){
	if($cid == $obito_us){
	bot('SendMessage',[
	'chat_id'=>$obito_us,
	'text'=>"<b>Quyidagilardan birini tanlang:</b>",
	'parse_mode'=>'html',
	'reply_markup'=>json_encode([
	'inline_keyboard'=>[
   [['text'=>"➕ Yangi admin qo'shish",'callback_data'=>"add"]],
   [['text'=>"📑 Ro'yxat",'callback_data'=>"list"],['text'=>"🗑 O'chirish",'callback_data'=>"remove"]],
	[['text'=>"Orqaga",'callback_data'=>"boshqarish"]]
	]
	])
	]);
	exit();
}else{	
bot('SendMessage',[
	'chat_id'=>$cid,
	'text'=>"<b>Quyidagilardan birini tanlang:</b>",
	'parse_mode'=>'html',
	'reply_markup'=>json_encode([
	'inline_keyboard'=>[
   [['text'=>"📑 Ro'yxat",'callback_data'=>"list"]],
[['text'=>"Orqaga",'callback_data'=>"boshqarish"]]
	]
	])
	]);
	exit();
}
}
}

if($data == "admins"){
if($cid2 == $obito_us){
	bot('deleteMessage',[
	'chat_id'=>$cid2,
	'message_id'=>$mid2,
	]);	
bot('SendMessage',[
	'chat_id'=>$obito_us,
	'text'=>"<b>Quyidagilardan birini tanlang:</b>",
	'parse_mode'=>'html',
	'reply_markup'=>json_encode([
	'inline_keyboard'=>[
   [['text'=>"➕ Yangi admin qo'shish",'callback_data'=>"add"]],
   [['text'=>"📑 Ro'yxat",'callback_data'=>"list"],['text'=>"🗑 O'chirish",'callback_data'=>"remove"]],
	[['text'=>"Orqaga",'callback_data'=>"boshqarish"]]
	]
	])
	]);
	exit();
}else{
bot('deleteMessage',[
	'chat_id'=>$cid2,
	'message_id'=>$mid2,
	]);	
bot('SendMessage',[
	'chat_id'=>$cid2,
	'text'=>"<b>Quyidagilardan birini tanlang:</b>",
	'parse_mode'=>'html',
	'reply_markup'=>json_encode([
	'inline_keyboard'=>[
   [['text'=>"📑 Ro'yxat",'callback_data'=>"list"]],
[['text'=>"Orqaga",'callback_data'=>"boshqarish"]]
	]
	])
	]);
	exit();
}
}

if($data == "list"){
$add = str_replace($obito_us,"",$admins);
if($admins == $obito_us){
	$text = "<b>Yordamchi adminlar topilmadi!</b>";
}else{
		$text = "<b>👮 Adminlar ro'yxati:</b>
$add";
}
     bot('editMessageText',[
        'chat_id'=>$cid2,
       'message_id'=>$mid2,
       'text'=>$text,
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"Orqaga",'callback_data'=>"admins"]],
]
])
]);
}

if($data == "add"){
bot('deleteMessage',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
]);
bot('SendMessage',[
'chat_id'=>$obito_us,
'text'=>"<b>Kerakli foydalanuvchi ID raqamini yuboring:</b>",
'parse_mode'=>'html',
'reply_markup'=>$boshqarish
]);
file_put_contents("step/$cid2.step",'add-admin');
exit();
}
if($step == "add-admin" and $cid == $obito_us){
$result = mysqli_query($connect,"SELECT * FROM user_id WHERE user_id = '$text'");
$row = mysqli_fetch_assoc($result);
if(!$row){
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Ushbu foydalanuvchi botdan foydalanmaydi!</b>

Boshqa ID raqamni kiriting:",
'parse_mode'=>'html',
]);
exit();
}elseif((mb_stripos($admins, $text)!==false) or ($text != $obito_us)){
if($admins == null){
file_put_contents("admin/admins.txt",$text);
}else{
file_put_contents("admin/admins.txt","\n".$text,FILE_APPEND);
}
bot('SendMessage',[
'chat_id'=>$obito_us,
'text'=>"<code>$text</code> <b>adminlar ro'yxatiga qo'shildi!</b>",
'parse_mode'=>'html',
'reply_markup'=>$panel
]);
unlink("step/$cid.step");
exit();
}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Ushbu foydalanuvchi adminlari ro'yxatida mavjud!</b>

Boshqa ID raqamni kiriting:",
'parse_mode'=>'html',
]);
exit();
}
}

if($data == "remove"){
bot('deleteMessage',[
'chat_id'=>$cid2,
'message_id'=>$mid2,
]);
bot('SendMessage',[
'chat_id'=>$obito_us,
'text'=>"<b>Kerakli foydalanuvchi ID raqamini yuboring:</b>",
'parse_mode'=>'html',
'reply_markup'=>$boshqarish
]);
file_put_contents("step/$cid2.step",'remove-admin');
exit();
}
if($step == "remove-admin" and $cid == $obito_us){
$result = mysqli_query($connect,"SELECT * FROM user_id WHERE user_id = '$text'");
$row = mysqli_fetch_assoc($result);
if(!$row){
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Ushbu foydalanuvchi botdan foydalanmaydi!</b>

Boshqa ID raqamni kiriting:",
'parse_mode'=>'html',
]);
exit();
}elseif((mb_stripos($admins, $text)!==false) or ($text != $obito_us)){
$files = file_get_contents("admin/admins.txt");
$file = str_replace("\n".$text."","",$files);
file_put_contents("admin/admins.txt",$file);
bot('SendMessage',[
'chat_id'=>$obito_us,
'text'=>"<code>$text</code> <b>adminlar ro'yxatidan olib tashlandi!</b>",
'parse_mode'=>'html',
'reply_markup'=>$panel
]);
unlink("step/$cid.step");
exit();
}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Ushbu foydalanuvchi adminlari ro'yxatida mavjud emas!</b>

Boshqa ID raqamni kiriting:",
'parse_mode'=>'html',
]);
exit();
}
}

//<---- @obito_us ---->//

if($text == "🤖 Bot holati"){
	if(in_array($cid,$admin)){
	if($holat == "Yoqilgan"){
		$xolat = "O'chirish";
	}
	if($holat == "O'chirilgan"){
		$xolat = "Yoqish";
	}
	bot('SendMessage',[
	'chat_id'=>$cid,
	'text'=>"<b>Hozirgi holat:</b> $holat",
	'parse_mode'=>'html',
	'reply_markup'=>json_encode([
	'inline_keyboard'=>[
[['text'=>"$xolat",'callback_data'=>"bot"]],
[['text'=>"Orqaga",'callback_data'=>"boshqarish"]]
]
])
]);
exit();
}
}

if($data == "xolat"){
	if($holat == "Yoqilgan"){
		$xolat = "O'chirish";
	}
	if($holat == "O'chirilgan"){
		$xolat = "Yoqish";
	}
	bot('deleteMessage',[
	'chat_id'=>$cid2,
	'message_id'=>$mid2,
	]);
	bot('SendMessage',[
	'chat_id'=>$cid2,
	'text'=>"<b>Hozirgi holat:</b> $holat",
	'parse_mode'=>'html',
	'reply_markup'=>json_encode([
	'inline_keyboard'=>[
[['text'=>"$xolat",'callback_data'=>"bot"]],
[['text'=>"Orqaga",'callback_data'=>"boshqarish"]]
]
])
]);
exit();
}

if($data == "bot"){
if($holat == "Yoqilgan"){
file_put_contents("admin/holat.txt","O'chirilgan");
     bot('editMessageText',[
        'chat_id'=>$cid2,
       'message_id'=>$mid2,
       'text'=>"<b>Muvaffaqiyatli o'zgartirildi!</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"xolat"]],
]
])
]);
}else{
file_put_contents("admin/holat.txt","Yoqilgan");
     bot('editMessageText',[
        'chat_id'=>$cid2,
       'message_id'=>$mid2,
       'text'=>"<b>Muvaffaqiyatli o'zgartirildi!</b>",
'parse_mode'=>'html',
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"◀️ Orqaga",'callback_data'=>"xolat"]],
]
])
]);
}
}

//<---- @obito_us ---->//

if($text == "⚙ Asosiy sozlamalar"){
		if(in_array($cid,$admin)){
	bot('SendMessage',[
	'chat_id'=>$cid,
	'text'=>"<b>Asosiy sozlamalar bo'limidasiz.</b>",
	'parse_mode'=>'html',
	'reply_markup'=>$asosiy,
	]);
	exit();
}
}

$delturi = file_get_contents("tizim/turi.txt");
$delmore = explode("\n",$delturi);
$delsoni = substr_count($delturi,"\n");
$key=[];
for ($delfor = 1; $delfor <= $delsoni; $delfor++) {
$title=str_replace("\n","",$delmore[$delfor]);
$key[]=["text"=>"$title - ni o'chirish","callback_data"=>"del-$title"];
$keyboard2 = array_chunk($key, 1);
$keyboard2[] = [['text'=>"➕ Yangi to'lov tizimi qo'shish",'callback_data'=>"new"]];
$pay = json_encode([
'inline_keyboard'=>$keyboard2,
]);
}

if($text == "💳 Hamyonlar"){
		if(in_array($cid,$admin)){
if($turi == null){
bot('SendMessage',[
	'chat_id'=>$cid,
	'text'=>"<b>Quyidagilardan birini tanlang:</b>",
	'parse_mode'=>'html',
		'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"➕ Yangi to'lov tizimi qo'shish",'callback_data'=>"new"]],
]
])
]);
exit();
}else{
	bot('SendMessage',[
	'chat_id'=>$cid,
	'text'=>"<b>Quyidagilardan birini tanlang:</b>",
	'parse_mode'=>'html',
		'reply_markup'=>$pay
]);
exit();
}
}
}

if($data == "hamyon"){
if($turi == null){
bot('deleteMessage',[
	'chat_id'=>$cid2,
	'message_id'=>$mid2,
	]);
bot('SendMessage',[
	'chat_id'=>$cid2,
	'text'=>"<b>Quyidagilardan birini tanlang:</b>",
	'parse_mode'=>'html',
		'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>"➕ Yangi to'lov tizimi qo'shish",'callback_data'=>"new"]],
]
])
]);
exit();
}else{
	bot('deleteMessage',[
	'chat_id'=>$cid2,
	'message_id'=>$mid2,
	]);
bot('SendMessage',[
	'chat_id'=>$cid2,
	'text'=>"<b>Quyidagilardan birini tanlang:</b>",
	'parse_mode'=>'html',
		'reply_markup'=>$pay
]);
exit();
}
}

//<---- @obito_us ---->//

if(mb_stripos($data,"del-")!==false){
	$ex = explode("-",$data);
	$tur = $ex[1];
	$k = str_replace("\n".$tur."","",$turi);
   file_put_contents("tizim/turi.txt",$k);
bot('deleteMessage',[
	'chat_id'=>$cid2,
	'message_id'=>$mid2,
	]);
bot('SendMessage',[
	'chat_id'=>$cid2,
	'text'=>"<b>To'lov tizimi o'chirildi!</b>",
		'parse_mode'=>'html',
	'reply_markup'=>$asosiy
]);
deleteFolder("tizim/$tur");
}

	/*$test = file_get_contents("step/test.txt");
   $k = str_replace("\n".$test."","",$turi);
   file_put_contents("tizim/turi.txt",$k);
deleteFolder("tizim/$test");
unlink("step/test.txt");
exit();*/

if($data == "new"){
	bot('deleteMessage',[
	'chat_id'=>$cid2,
	'message_id'=>$mid2,
   ]);
   bot('sendMessage',[
   'chat_id'=>$cid2,
   'text'=>"<b>Yangi to'lov tizimi nomini yuboring:</b>",
   'parse_mode'=>'html',
   'reply_markup'=>$boshqarish
	]);
	file_put_contents("step/$cid2.step",'turi');
	exit();
}

if($step == "turi"){
if(in_array($cid,$admin)){
if(isset($text)){
mkdir("tizim/$text");
file_put_contents("tizim/turi.txt","$turi\n$text");
	file_put_contents("step/test.txt",$text);
	bot('SendMessage',[
	'chat_id'=>$cid,
	'text'=>"<b>Ushbu to'lov tizimidagi hamyoningiz raqamini yuboring:</b>",
	'parse_mode'=>'html',
	]);
	file_put_contents("step/$cid.step",'wallet');
	exit();
}
}
}


if($step == "wallet"){
if(in_array($cid,$admin)){
if(is_numeric($text)=="true"){
file_put_contents("tizim/$test/wallet.txt","$wallet\n$text");
	bot('SendMessage',[
	'chat_id'=>$cid,
	'text'=>"<b>Ushbu to'lov tizimi orqali hisobni to'ldirish bo'yicha ma'lumotni yuboring:</b>

<i>Misol uchun, \"Ushbu to'lov tizimi orqali pul yuborish jarayonida izoh kirita olmasligingiz mumkin. Ushbu holatda, biz bilan bog'laning. Havola: @obito_us</i>\"",
'parse_mode'=>'html',
	]);
	file_put_contents("step/$cid.step",'addition');
	exit();
}else{
bot('SendMessage',[
'chat_id'=>$cid,
'text'=>"<b>Faqat raqamlardan foydalaning!</b>",
'parse_mode'=>'html',
]);
exit();
}
}
}

if($step == "addition"){
		if(in_array($cid,$admin)){
	if(isset($text)){
file_put_contents("tizim/$test/addition.txt","$addition\n$text");
	bot('SendMessage',[
	'chat_id'=>$cid,
	'text'=>"<b>Yangi to'lov tizimi qo'shildi!</b>",
	'parse_mode'=>'html',
	'reply_markup'=>$asosiy,
	]);
	unlink("step/$cid.step");
	unlink("step/test.txt");
	exit();
}
}
}

// <---- @obito_us ---->

if($text == "🎥 Animelar sozlash" and in_array($cid,$admin)){
sms($cid,"<b>Quyidagilardan birini tanlang:</b>",json_encode([
'inline_keyboard'=>[
[['text'=>"➕ Anime qo'shish",'callback_data'=>"add-anime"]],
[['text'=>"📥 Qism qo'shish",'callback_data'=>"add-episode"]],
[['text'=>"📝 Anime tahrirlash",'callback_data'=>"edit-anime"]],
]]));
exit();
}

if($data == "add-anime"){
del();
sms($cid2,"<b>🍿 Anime nomini kiriting:</b>",$boshqarish);
put("step/$cid2.step","anime-name");
}

if($step == "anime-name" and in_array($cid,$admin)){
if(isset($text)){
if(containsEmoji($text)==false){
$text = $connect->real_escape_string($text);
put("step/test.txt",$text);
sms($cid,"<b>🎥 Jami qismlar sonini kiriting:</b>",$boshqarish);
put("step/$cid.step","anime-episodes");
exit();
}else{
sms($cid,"<b>⚠️ Anime qo'shishda emoji va shunga o'xshash maxsus belgilardan foydalanish taqiqlangan!</b>

Qayta urining",null);
}
}
}

if($step == "anime-episodes" and in_array($cid,$admin)){
if(isset($text)){
$text = $connect->real_escape_string($text);
put("step/test2.txt",$text);
sms($cid,"<b>🌍 Qaysi davlat ishlab chiqarganini kiriting:</b>",$boshqarish);
put("step/$cid.step","anime-country");
exit();
}
}

if($step == "anime-country" and in_array($cid,$admin)){
if(isset($text)){
$text = $connect->real_escape_string($text);
put("step/test3.txt",$text);
sms($cid,"<b>🇺🇿 Qaysi tilda ekanligini kiriting:</b>",$boshqarish);
put("step/$cid.step","anime-language");
exit();
}
}

if($step == "anime-language" and in_array($cid,$admin)){
if(isset($text)){
$text = $connect->real_escape_string($text);
put("step/test4.txt",$text);
sms($cid,"<b>📆 Qaysi yilda ishlab chiqarilganini kiriting:</b>",$boshqarish);
put("step/$cid.step","anime-year");
exit();
}
}

if($step == "anime-year" and in_array($cid,$admin)){
if(isset($text)){
$text = $connect->real_escape_string($text);
put("step/test5.txt",$text);
sms($cid,"<b>🎞 Janrlarini kiriting:</b>

<i>Na'muna: Drama, Fantastika, Sarguzash</i>",$boshqarish);
put("step/$cid.step","anime-fandub");
exit();
}
}

if($step == "anime-fandub" and in_array($cid,$admin)){
if(isset($text)){
$text = $connect->real_escape_string($text);
put("step/test6.txt",$text);
sms($cid,"<b>🎙️Fandub nomini kiriting:</b>

<i>Na'muna: @AnimeLiveUz</i>",$boshqarish);
put("step/$cid.step","anime-genre");
exit();
}
}
if ($step == "anime-genre" and in_array($cid, $admin)) {
    if (isset($text)) {
        $text = $connect->real_escape_string($text);
        put("step/test7.txt", $text);
        sms($cid, "<b>🏞 Rasmini yoki 60 soniyadan oshmagan video yuboring:</b>", $boshqarish);
        put("step/$cid.step", "anime-picture");
        exit();
    }
}

if ($step == "anime-picture" and in_array($cid, $admin)) {
    if (isset($message->photo) || isset($message->video)) {
        if (isset($message->photo)) {
            $file_id = $message->photo[count($message->photo) - 1]->file_id;
        }
        elseif (isset($message->video)) {
            if ($message->video->duration <= 60) {
                $file_id = $message->video->file_id;
            } else {
                sms($cid, "<b>⚠️ Video 60 soniyadan oshmasligi kerak!</b>", $panel);
                exit();
            }
        }

        $nom = get("step/test.txt");
        $qismi = get("step/test2.txt");
        $davlati = get("step/test3.txt");
        $tili = get("step/test4.txt");
        $yili = get("step/test5.txt");
        $janri = get("step/test6.txt");
        $fandub = file_get_contents("step/test7.txt");
        $date = date('H:i d.m.Y');

        if ($connect->query("INSERT INTO `animelar` (`nom`, `rams`, `qismi`, `davlat`, `tili`, `yili`, `janri`, `qidiruv`,`aniType`, `sana`) VALUES ('$nom', '$file_id', '$qismi', '$davlati', '$tili', '$yili', '$janri', '0', '$fandub', '$date')") == TRUE) {
            $code = $connect->insert_id;
            sms($cid, "<b>✅ Anime qo'shildi!</b>\n\n<b>Anime kodi:</b> <code>$code</code>", $panel);

            // Fayllarni o'chirish
            unlink("step/$cid.step");
            unlink("step/test.txt");
            unlink("step/test2.txt");
            unlink("step/test3.txt");
            unlink("step/test4.txt");
            unlink("step/test5.txt");
            unlink("step/test6.txt");
            unlink("step/test7.txt");
            exit();
        } else {
            sms($cid, "<b>⚠️ Xatolik!</b>\n\n<code>$connect->error</code>", $panel);

            // Fayllarni o'chirish
            unlink("step/$cid.step");
            unlink("step/test.txt");
            unlink("step/test2.txt");
            unlink("step/test3.txt");
            unlink("step/test4.txt");
            unlink("step/test5.txt");
            unlink("step/test6.txt");
            unlink("step/test7.txt");
            exit();
        }
    } else {
        sms($cid, "<b>⚠️ Iltimos, rasm yoki 60 soniyadan oshmagan video yuboring!</b>", $panel);
    }
}


if($data == "add-episode"){
del();
sms($cid2,"<b>🔢 Anime kodini kiriting:</b>",$boshqarish);
put("step/$cid2.step","episode-code");
}

if($step == "episode-code" and in_array($cid,$admin)){
if(is_numeric($text)){
$text = $connect->real_escape_string($text);
put("step/test.txt",$text);
sms($cid,"<b>🎥 Ushbu kodga tegishlik anime qismini yuboring:</b>",$boshqarish);
put("step/$cid.step","episode-video");
exit();
}
}

if($step == "episode-video" and in_array($cid,$admin)){
if(isset($message->video)){
$file_id = $message->video->file_id;
$id = get("step/test.txt");
$qism = $connect->query("SELECT * FROM anime_datas WHERE id = $id")->num_rows;
$qismi = $qism+1;
$sana = date('H:i:s d.m.Y');
if($connect->query("INSERT INTO anime_datas(id,file_id,qism,sana) VALUES ('$id','$file_id','$qismi','$sana')")==TRUE){
$code = $connect->insert_id;
sms($cid,"<b>✅ $id raqamli animega $qismi-qism yuklandi!</b>

<i>Yana yuklash uchun keyingi qismni yuborsangiz bo'ldi</i>",null);
exit();
}else{
sms($cid,"<b>⚠️ Xatolik!</b>\n\n<code>$connect->error</code>",$panel);
unlink("step/$cid.step");
unlink("step/test.txt");
unlink("step/test2.txt");
exit();
}
}
}

if ($data == "edit-anime") {
	edit($cid2, $mid2, "<b>Tahrirlamoqchi bo'lgan animeni tanlang:</b>", json_encode([
		'inline_keyboard' => [
			[['text' => "Anime ma'lumotlarini", 'callback_data' => "editType-animes"]],
			[['text' => "Anime qismini", 'callback_data' => "editType-anime_datas"]]
		]
	]));
}

if (mb_stripos($data, "editType-") !== false) {
	$ex = explode("-", $data)[1];
	put("step/$cid2.tip", $ex);
	del();
	sms($cid2, "<b>Anime kodini kiriting:</b>", $boshqarish);
	put("step/$cid2.step", "edit-anime");
}

if($step == "edit-anime"){
$tip=get("step/$cid.tip");
if($tip=="animes"){
$result=mysqli_query($connect,"SELECT * FROM animelar WHERE id = $text");
$row=mysqli_fetch_assoc($result);
if($row){
$kb=json_encode([
'inline_keyboard'=>[
[['text'=>"Nomini tahrirlash",'callback_data'=>"editAnime-nom-$text"]],
[['text'=>"Qismini tahrirlash",'callback_data'=>"editAnime-qismi-$text"]],
[['text'=>"Davlatini tahrirlash",'callback_data'=>"editAnime-davlat-$text"]],
[['text'=>"Tilini tahrirlash",'callback_data'=>"editAnime-tili-$text"]],
[['text'=>"Yilini tahrirlash",'callback_data'=>"editAnime-yili-$text"]],
[['text'=>"Janrini tahrirlash",'callback_data'=>"editAnime-janri-$text"]],
[['text'=>"Anime rasmini tahrirlash",'callback_data'=>"editAnime-image-$text"]],
[['text'=>"Animeni o'chirish",'callback_data'=>"editAnime-delete-$text"]]
]]);
sms($cid,"<b>❓ Nimani tahrirlamoqchisiz?</b>",$kb);
unlink("step/$cid2.step");
exit();
}else{
sms($cid,"<b>❗ Anime mavjud emas, qayta urinib ko'ring!</b>",null);
exit();
}
}else{
$result=mysqli_query($connect,"SELECT * FROM animelar WHERE id = $text");
$row=mysqli_fetch_assoc($result);
if($row){
sms($cid,"<b>Qism raqamini yuboring:</b>",$boshqarish);
put("step/$cid.step","anime-epEdit=$text");
exit();
}else{
sms($cid,"<b>❗ Anime mavjud emas, qayta urinib ko'ring!</b>",null);
exit();
}
}
}


if(mb_stripos($step,"anime-epEdit=")!==false){
$ex = explode("=",$step);
$id = $ex[1];
$result=mysqli_query($connect,"SELECT * FROM anime_datas WHERE id = $id AND qism = $text");
$row=mysqli_fetch_assoc($result);
if($row){
$kb=json_encode([
'inline_keyboard'=>[
[['text'=>"Anime kodini tahrirlash",'callback_data'=>"editEpisode-id-$id-$text"]],
[['text'=>"Qismini tahrirlash",'callback_data'=>"editEpisode-qism-$id-$text"]],
[['text'=>"Videoni tahrirlash",'callback_data'=>"editEpisode-file_id-$id-$text"]],
]]);
sms($cid,"<b>❓ Nimani tahrirlamoqchisiz?</b>",$kb);
unlink("step/$cid.step");
exit();
}else{
sms($cid,"<b>❗ Ushbu animeda $text-qism mavjud emas, qayta urinib ko'ring.</b>",null);
exit();
}
}

if(mb_stripos($data,"editAnime-")!==false){
del();
sms($cid2,"<b>Yangi qiymatini kiriting:</b>",$boshqarish);
put("step/$cid2.step",$data);
}



if (mb_stripos($step, "editAnime-") !== false) {
    $ex = explode("-", $step);
    $tip = $ex[1];
    $id = $ex[2];

    if ($tip == "delete") {
        $keyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "✅ Ha", 'callback_data' => "confirm-delete-$id"],
                    ['text' => "❌ Yo‘q", 'callback_data' => "cancel-delete"]
                ]
            ]
        ]);
        sms($cid, "❗ Ushbu animeni va uning barcha qismlarini o'chirishni istaysizmi?\nID: <b>$id</b>", $keyboard);
        exit();
    }

    if ($tip == "image") {
        if (isset($message->photo) || isset($message->video)) {
            if (isset($message->photo)) {
                $file_id = $message->photo[count($message->photo) - 1]->file_id;
            } elseif (isset($message->video)) {
                if ($message->video->duration <= 60) {
                    $file_id = $message->video->file_id;
                } else {
                    sms($cid, "<b>⚠️ Video 60 soniyadan oshmasligi kerak!</b>", $panel);
                    exit();
                }
            }

            $query = "UPDATE animelar SET rams = '" . mysqli_real_escape_string($connect, $file_id) . "' WHERE id = $id";
            if (mysqli_query($connect, $query)) {
                sms($cid, "<b>✅ Rasm muvaffaqiyatli yangilandi!</b>", $panel);
            } else {
                sms($cid, "<b>❗ Rasmni yangilashda xatolik yuz berdi!</b>", $panel);
            }
            exit();
        } else {
            sms($cid, "<b>⚠️ Iltimos, rasm yoki 60 soniyadan oshmagan video yuboring!</b>", $panel);
            exit();
        }
    } else {
        if ($tip == "qismi" || $tip == "yili") {
            if (is_numeric($text)) {
                mysqli_query($connect, "UPDATE animelar SET `$tip`='$text' WHERE id = $id");
                sms($cid, "<b>✅ Saqlandi.</b>", null);
                unlink("step/$cid.step");
                exit();
            } else {
                sms($cid, "<b>❗Faqat raqamlardan foydalaning.</b>", null);
                exit();
            }
        } else {
            if (isset($text)) {
                mysqli_query($connect, "UPDATE animelar SET `$tip`='$text' WHERE id = $id");
                sms($cid, "<b>✅ Saqlandi.</b>", null);
                unlink("step/$cid.step");
                exit();
            } else {
                sms($cid, "<b>❗Faqat matnlardan foydalaning.</b>", null);
                exit();
            }
        }
    }
}


if (mb_stripos($data, "confirm-delete-") !== false) {
    $ex = explode("-", $data);
    $id = $ex[2];

    $check = mysqli_query($connect, "SELECT * FROM animelar WHERE id = $id");
    if (mysqli_num_rows($check) > 0) {

        $deleteAnime = mysqli_query($connect, "DELETE FROM animelar WHERE id = $id");
        $deleteEpisodes = mysqli_query($connect, "DELETE FROM anime_datas WHERE id = $id");

        if ($deleteAnime) {
            edit($cid2, $mid2, "<b>✅ Anime va barcha qismlari muvaffaqiyatli o'chirildi!</b>",null);
        } else {
            edit($cid2, $mid2, "<b>❗ O'chirishda xatolik yuz berdi!</b>",null);
        }

    } else {
        edit($cid2, $mid2, "<b>❗ Bunday ID ga ega anime topilmadi.</b>",null);
    }

    exit();
}


if ($data == "cancel-delete") {
    edit($cid2, $mid2, "<b>❌ O‘chirish  so'rovi bekor qilindi.</b>",null);
    exit();
}


if(mb_stripos($data,"editEpisode-")!==false){
del();
sms($cid2,"<b>Yangi qiymatini kiriting:</b>",$boshqarish);
put("step/$cid2.step",$data);
}

if(mb_stripos($step,"editEpisode-")!==false){
$ex = explode("-",$step);
$tip = $ex[1];
$id = $ex[2];
$qism_raqami = $ex[3];
if($tip=="file_id"){
if(isset($message->video)){
$file_id = $message->video->file_id;
mysqli_query($connect,"UPDATE anime_datas SET `file_id`='$file_id' WHERE id = $id AND qism = $qism_raqami");
sms($cid,"<b>✅ Saqlandi.</b>",null);
unlink("step/$cid.step");
exit();
}else{
sms($cid,"<b>❗Faqat videodan foydalaning.</b>",null);
exit();
}
}else{
if(is_numeric($text)){
mysqli_query($connect,"UPDATE anime_datas SET `$tip`='$text' WHERE id = $id AND qism = $qism_raqami");
sms($cid,"<b>✅ Saqlandi.</b>",null);
unlink("step/$cid.step");
exit();
}else{
sms($cid,"<b>❗Faqat raqamlardan foydalaning.</b>",null);
exit();
}
}
}

// <---- 
// Asosiy dasturchi: @obito_us 
// Tog'irladi: @Boltaboyev_Rahmatillo
//---->

$valyuta = file_get_contents("admin/valyuta.txt");
$narx = file_get_contents("admin/vip.txt");
$studio_name = file_get_contents("admin/studio_name.txt");

$name_content = ($content == "false") ? "🔒 Kontent cheklash" : "🔓 Kontent ulashish";


if ($text == "*️⃣ Birlamchi sozlamalar") {
    if (in_array($cid, $admin)) {
        sms($cid, "<b>Hozirgi birlamchi sozlamalar:</b>

<i>1. Valyuta - $valyuta
2. VIP narxi - $narx $valyuta
3. Studia nomi - $studio_name</i>", json_encode([
            'inline_keyboard' => [
                [['text' => "1", 'callback_data' => "valyuta"], ['text' => "2", 'callback_data' => "vnarx"], ['text' => "3", 'callback_data' => "studio_name"]],
                [['text'=>$name_content,'callback_data'=>"content"]],
            ]
        ]));
        exit();
    }
}


if ($data == "content"){
    if ($content == "true"){
        put("tizim/content.txt",'false');
        edit($cid2,$mid2,"<b>$name_content  muvoffaqatli yoqildi ✅</b>",null);
    }elseif ($content == "false") {
        put("tizim/content.txt",'true');
        edit($cid2,$mid2,"<b>$name_content  muvoffaqatli yoqildi ✅</b>",null);
    }
}

if ($data == "birlamchi") {
    edit($cid2, $mid2, "<b>Hozirgi birlamchi sozlamalar:</b>

<i>1. Valyuta - $valyuta
2. VIP narxi - $narx $valyuta
3. Studia nomi - $studio_name</i>", json_encode([
        'inline_keyboard' => [
            [['text' => "1", 'callback_data' => "valyuta"], ['text' => "2", 'callback_data' => "vnarx"], ['text' => "3", 'callback_data' => "studio_name"]],
        ]
    ]));
    exit();
}

if ($data == "valyuta") {
    del();
    sms($cid2, "📝 <b>Yangi valyutani kiriting:</b>", $boshqarish);
    put("step/$cid2.step", 'valyuta');
    exit();
}

if ($step == "valyuta" and in_array($cid, $admin)) {
    if (isset($text)) {
        put("admin/valyuta.txt", $text);
        sms($cid, "<b>✅ Valyuta saqlandi.</b>", $panel);
        unlink("step/$cid.step");
        exit();
    }
}

if ($data == "vnarx") {
    del();
    sms($cid2, "📝 <b>Yangi VIP narxni kiriting:</b>", $boshqarish);
    put("step/$cid2.step", 'vnarx');
    exit();
}

if ($step == "vnarx" and in_array($cid, $admin)) {
    if (isset($text)) {
        put("admin/vip.txt", $text);
        sms($cid, "<b>✅ VIP narx saqlandi.</b>", $panel);
        unlink("step/$cid.step");
        exit();
    }
}

if ($data == "studio_name") {
    del();
    sms($cid2, "📝 <b>Yangi studia nomini kiriting:</b>", $boshqarish);
    put("step/$cid2.step", 'studio_name');
    exit();
}

if ($step == "studio_name" and in_array($cid, $admin)) {
    if (isset($text)) {
        put("admin/studio_name.txt", $text);
        sms($cid, "<b>✅ Studia nomi saqlandi.</b>", $panel);
        unlink("step/$cid.step");
        exit();
    }
}
// <---- @obito_us ---->

if($text == "📃 Matnlar" and in_array($cid,$admin)){
sms($cid,"<b>Quyidagilardan birini tanlang:</b>",json_encode([
'inline_keyboard'=>[
[['text'=>"Boshlang'ich matni",'callback_data'=>"matn1"]],
[['text'=>"Qo'llanma",'callback_data'=>"matn2"]],
[['text'=>"🔖 Homiy matni",'callback_data'=>"matn5"]],
]]));
exit();
}

if($data == "matn1"){
del();
sms($cid2,"<b>Boshlang'ich matnini yuboring:</b>",$boshqarish);
put("step/$cid2.step",'matn1');
exit();
}

if($step == "matn1" and in_array($cid,$admin)){
if(isset($text)){
put("matn/start.txt",$text);
sms($cid,"<b>✅ Saqlandi.</b>",$panel);
unlink("step/$cid.step");
exit();
}
}

if($data == "matn2"){
del();
sms($cid2,"<b>Qo'llanma matnini yuboring::</b>",$boshqarish);
put("step/$cid2.step",'matn2');
exit();
}

if($step == "matn2" and in_array($cid,$admin)){
if(isset($text)){
put("matn/qollanma.txt",$text);
sms($cid,"<b>✅ Saqlandi.</b>",$panel);
unlink("step/$cid.step");
exit();
}
}

if($data == "matn5"){
del();
sms($cid2,"<b>Homiy matnini yuboring:</b>",$boshqarish);
put("step/$cid2.step",'matn5');
}
