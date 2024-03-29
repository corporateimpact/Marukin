<?php

// タイムアウト時間を変更する
ini_set("max_execution_time", 600);

$dateStr = date("Y/m/d");
$dl_date_from = $dateStr;
if (isset($_POST['date_from'])) {
  if ($_POST['date_from'] != "") {
    $dateStr = str_replace("/", "", $_POST['date_from']);
    $dl_date_from = $_POST['date_from'];
    $timeStr = "000000";
  }
}
if (isset($_GET['date_from'])) {
  if ($_GET['date_from'] != "") {
    $dateStr = str_replace("/", "", $_GET['date_from']);
    $dl_date_from = $_GET['date_from'];
    $timeStr = "000000";
  }
}

$dateStr = date("Y/m/d");
$dl_date_to = $dateStr;
if (isset($_POST['date_to'])) {
  if ($_POST['date_to'] != "") {
    $dateStr = str_replace("/", "", $_POST['date_to']);
    $dl_date_to = $_POST['date_to'];
    $timeStr = "000000";
  }
}
if (isset($_GET['date_to'])) {
  if ($_GET['date_to'] != "") {
    $dateStr = str_replace("/", "", $_GET['date_to']);
    $dl_date_to = $_GET['date_to'];
    $timeStr = "000000";
  }
}


//送信されたfromとtoの日付をチェック
if ($dl_date_to < $dl_date_from) {
  $dummy_date = $dl_date_from;
  $dl_date_from = $dl_date_to;
  $dl_date_to = $dummy_date;
}


//ＣＳＶ出力
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=" . str_replace("/", "", $dl_date_from) . "-" . str_replace("/", "", $dl_date_to) . ".csv");


$mysqli = new mysqli('localhost', 'root', 'pm#corporate1', 'marukin');
$mysqli->set_charset('utf8');

//測定値テーブル抽出クエリ
$sql = "SELECT data.days, data.times, data.water_temp, data.do, amedas_temp.air_temp, do_average.do_average, do_average.do_7days_ave, avgs.avg_wtemp
            FROM ((data LEFT JOIN amedas_temp ON data.days = amedas_temp.days AND data.times = amedas_temp.times)
            LEFT JOIN do_average ON data.days = do_average.days AND data.times = do_average.times
            LEFT JOIN (select days, times, avg(water_temp) as avg_wtemp from data group by days order by days desc, times desc) as avgs on data.days = avgs.days and data.times = avgs.times)
            WHERE data.days BETWEEN '" . $dl_date_from . "' AND '" . $dl_date_to . "' ORDER BY data.days, data.times";

$res = $mysqli->query($sql);

// ヘッダー作成

echo "\"day\",\"time\",\"water_temp\",\"do\",\"onagawa_temp\",\"water_temp_ave\",\"do_ave\",\"do_7days_ave\"\r\n";


while ($row = $res->fetch_array()) {
  print("\"" . $row[0] . "\",\""  //日付
    . $row[1] . "\",\""  //時刻
    . $row[2] . "\",\""  //水温
    . $row[3] . "\",\""  //溶存酸素
    . $row[4] . "\",\"" //気温
    . $row[7] . "\",\"" //水温日ごと平均
    . $row[5] . "\",\"" //DO日ごと平均
    . $row[6] . "\"\r\n"); //DO7日ごと平均
}

$mysqli->close();
