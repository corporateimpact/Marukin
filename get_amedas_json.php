<?php
//date_default_timezone_set('Asia/Tokyo');

/* 気象庁HPからのスクレイピングが困難になってしまった為JSONから取得する形へ変更 */
#https://www.jma.go.jp/bosai/amedas/data/point/拠点番号/日付_時間帯番号(00, 03, 06, 12, 15, 18, 21).json#
/* 変数制限まとめて　使いまわし考慮の為出来るだけここを直すだけにする */
$host_name = "127.0.0.1";
$user_name = "root";
$db_password = "pm#corporate1";
$db_name = "marukin";
$table_name = "amedas_temp";
$area_no = "34296";
//$what=date( "H", time()+32400 );
// タイムゾーンがずれているので修正:20200207 伊藤
$what = date( "H", time() );
$which = (int)$what;

//参照用日付格納‘
$set_json_date = date("Ymd");
$set_sql_date = '"'. date("Y-m-d"). '"';
$set_sql_time = '"'. $which. ':00:00"';

// 参照するjsonファイル番号を設定(毎日3時間ごとに番号を振られて作成される)
if(0 <= $which && $which <= 3) {
    $json_no = "00";
} elseif (3 <= $which && $which <= 3) {
    $json_no = "03";
} elseif (6 <= $which && $which <= 6) {
    $json_no = "06";
} elseif (9 <= $which && $which <= 12) {
    $json_no = "09";
} elseif (12 <= $which && $which <= 15) {
    $json_no = "12";
} elseif (15 <= $which && $which <= 18) {
    $json_no = "15";
} elseif (18 <= $which && $which <= 21) {
    $json_no = "18";
} elseif (21 <= $which) {
    $json_no = "21";
} else {
    $json_no = "00";
    echo "jsonfile set error!";
}

$url = "https://www.jma.go.jp/bosai/amedas/data/point/". $area_no. "/". $set_json_date. "_". $json_no. ".json";
$json_get = file_get_contents($url);
$json_data = json_decode($json_get, TRUE);
$set = $json_data[$set_json_date. $which. "0000"];
$temp = $set["temp"];
echo $set_sql_date. "\n";
echo $set_sql_time. "\n";
echo $temp[0]. "\n";

//MySQLへ接続（DB_HOST, DB_USER, DB_PASS）
$mysqli = new mysqli ($host_name, $user_name, $db_password, $db_name);
if ($mysqli->connect_error) {
    //echo $mysqli->connect_error;
    exit();
}
else {
    $mysqli->set_charset("utf8");
}

//mysql構文 データ登録用
$sql = 'replace into ' . $table_name . ' values (' .$set_sql_date . ', ' . $set_sql_time . ', "' . (float)$temp[0] . '");';
//echo $sql;
$mysqli_result = $mysqli->query($sql);
if (!$mysqli_result) {
    die('insert fault'.mysql_error() . "\n");
}
$mysqli->close();//DB.close();
echo "\n"
?>
