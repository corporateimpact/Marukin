<?php
session_start();
//if (!isset($_SESSION['USER'])) {
//    header('Location: index.php');
//    exit;
//}
// 仕様変更後未使用
function get_do($str1,$str2){
	$T = floatval($str1);
	$OP = floatval($str2);
	$S  = 35;

	$A1 = -173.4292;
	$A2 = 249.6339;
	$A2 = 249.6339;
	$A3 = 143.3483;
	$A4 = -21.8492;
	$B1 = -0.033096;
	$B2 = 0.014259;
	$B3 = -0.0017;
	$e = 2.718281828;

	$TS = 273.15 + $T;
	
	$O1 = $A1 + $A2 * (100/$TS) + $A3 * log($TS/100) + $A4 * ($TS/100) + $S * ($B1 + $B2 * ($TS/100) + $B3 * pow(($TS/100),2));
	$O2 = pow($e,$O1);

	$OU = $OP/100*$O2*44.660;
	$OM = $OP/100*$O2*1.42903;

	return $OM;
}
// 仕様変更後未使用

if(isset($_POST['logout'])){
    session_destroy();
    header('Location: index.php');
    exit;
}
$org_date = "";
$dateStr = date("Ymd");
$timeStr = date("Hi00");
if(isset($_POST['date'])){
	if($_POST['date'] != ""){
		$dateStr = str_replace("/","",$_POST['date']);
		$org_date = $_POST['date'];
		$timeStr = "000000";
	}
}
if(isset($_GET['date'])){
	if($_GET['date'] != ""){
		$dateStr = str_replace("/","",$_GET['date']);
		$org_date = $_GET['date'];
		$timeStr = "000000";
	}
}
if(isset($_POST['time'])){
	if($_POST['time'] != ""){
		$timeStr = str_replace(":","",$_POST['time']);
	}
}
if(isset($_GET['time'])){
	if($_GET['time'] != ""){
		$timeStr = str_replace(":","",$_GET['time']);
	}
}
//旧amedas気温DATの読み込み処理ここから
$dArray;
if(file_exists("/var/www/html/infos/" . $dateStr . ".dat")){
	$data = File("/var/www/html/infos/" . $dateStr . ".dat");
	$label;
	$temperature;
	$humidity;
	//$water_temp;
	foreach($data as $row){
		$tmp = explode(",",$row);
		$dArray{str_replace(":","",$tmp[0])} = $tmp;
	}
}
//格納処理
if(file_exists("/var/www/html/jma/" . $dateStr . ".dat")){
	$datas = File("/var/www/html/jma/" . $dateStr . ".dat");
	//$label = $data[0];
	$tmp = explode(",",$datas[1]);
	$temperature = "";
	foreach($tmp as $row){
		for($i = 0;$i < 6;$i++){
			$temperature .= $row . ",";
		}
	}
}
//AMeDAS気温の格納処理ここまで
//
//202011袰岩追加 AMeDASからのデータ処理
$mysqli = new mysqli('localhost', 'root', 'pm#corporate1', 'marukin');
$sql2 = "select substring(date_format(times,'%H:%i'),1,4) AS JIKAN, round(air_temp, 1) as temp from amedas_temp where days = '";
$sql2 = $sql2 . str_replace("/", "-", $org_date);
$sql2 = $sql2 . "' group by substring(date_format(times,'%H:%i'),1,4) order by JIKAN;";
$res2 = $mysqli->query($sql2);
$air_temp = "";            // 志津川気温

$i_next = 0;
$j_next = 0;
while ($row2 = $res2->fetch_array()) {
    for ($i = $i_next; $i < 25; $i++) {
    for ($j = $j_next; $j < 6; $j++) {
        if (substr($row2[0], 0, 2) == $i and substr($row2[0], 3, 1) == $j) {
        $air_temp = $air_temp . $row2[1] . ",";
        if ($j == 5) {
            $j_next = 0;
            $i_next = $i + 1;
        } else {
            $j_next = $j + 1;
            $i_next = $i;
        }
        break 2;
        } elseif (substr($row2[0], 0, 2) > $i) {
        $air_temp = $air_temp . ",";
        if ($j == 5) {
            $j_next = 0;
        }
        } elseif (substr($row2[0], 0, 2) >= $i and substr($row2[0], 3, 1) > $j) {
        $air_temp = $air_temp . ",";
        if ($j == 5) {
            $j_next = 0;
        }
        }
    }
    }
}
//

//202011袰岩追加
// MySQLより該当日の測定値(平均)を取得（グラフ表示で使用）
$mysqli = new mysqli('localhost', 'root', 'pm#corporate1', 'marukin');
$sql = "select substring(date_format(times,'%H:%i:%s'),1,8) AS JIKAN, do, water_temp from data where days = '";
$sql = $sql . str_replace("/", "-", $org_date);
$sql = $sql . "' group by substring(date_format(times,'%H:%i:%s'),1,8) order by JIKAN";
$res = $mysqli->query($sql);
$do = "";             //溶存酸素濃度
$water_temp = "";

$i_next = 0;    //時間　MAX24
$j_next = 0;    //10分毎　MAX5回分（50分）
while ($row = $res->fetch_array()) {
    for ($i = $i_next; $i < 25; $i++) {   //24時まで　
    for ($j = $j_next; $j < 6; $j++) {    //50分まで
        if (substr($row[0], 0, 2) == $i and substr($row[0], 3, 1) == $j) {
        $do = $do . $row[1] . ",";
        $water_temp = $water_temp . $row[2] . ",";
        if ($j == 5) {                    //50分まで来たらゼロにする
            $j_next = 0;
            $i_next = $i + 1;
        } else {
            $j_next = $j + 1;
            $i_next = $i;
        }
        break 2;
        } elseif (substr($row[0], 0, 2) > $i) {
        $do = $do . ",";
        $water_temp = $water_temp . ",";
        if ($j == 5) {                    //50分まで来たらゼロにする
            $j_next = 0;
        }
        } elseif (substr($row[0], 0, 2) >= $i and substr($row[0], 3, 1) > $j) {
        $do = $do . ",";
        $water_temp = $water_temp . ",";
        if ($j == 5) {                    //50分まで来たらゼロにする
            $j_next = 0;
        }
        }
    }
    }
}
//

$data = array();

$sample=0;

$count=0;
for($i=0;$i<1440;$i++){
	$h = str_pad(floor($i / 60), 2, 0, STR_PAD_LEFT);
	$m = str_pad(floor($i % 60), 2, 0, STR_PAD_LEFT);
	if($m % 10 == 0){
		if($m == "00"){
			$label .= "'" . $h ."時',";
		}else{
			$label .= "'',";
		}
		if(isset($dArray{$h . $m . "00"})){
			for($j = 1;$j<10;$j++){
				if($sample==1){
					if($j==6){
						if($count % 2 ==0){
							$data[6] .= "4,";
						}else{
							$data[6] .= "8,";
						}
						$count++;
					}else{
						if(isset($dArray{$h . $m . "00"}[$j])){
							if($dArray{$h . $m . "00"}[$j] != "0.0"){
								$data[$j] .= $dArray{$h . $m . "00"}[$j] . ",";
							}else{
								$data[$j] .= ",";
							}
						}else{
							$data[$j] .= ",";
						}
					}
				}else{
					if(isset($dArray{$h . $m . "00"}[$j])){
						if($dArray{$h . $m . "00"}[$j] != "0.0"){
								$data[$j] .= $dArray{$h . $m . "00"}[$j] . ",";
						}else{
							$data[$j] .= ",";
						}
					}else{
						$data[$j] .= ",";
					}
				}
			}
		}else{
			for($j = 1;$j<10;$j++){
				$data[$j] .= ",";
			}
		}
	}
}


$mainImg = "img/Noimage_image.png";
if(file_exists("/var/www/html/images/" . $dateStr . "/" . $dateStr . "_" . $timeStr . ".jpg" )){
	$mainImg = "images/" . $dateStr . "/" . $dateStr . "_" . $timeStr . ".jpg";
}

// 接続終了
$mysqli->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>映像</title>
<meta name="viewport" content="width=device-width">
<link rel="stylesheet" href="css/jquery-ui.min.css" />

<script src="js/jquery-1.11.0.min.js"></script>
<script src="js/chart.js"></script>

<script>
var $times = "<?php echo $_GET["time"]; ?>";

function viewImage($timeStr){
	$times = $timeStr.toString();
	document.getElementById("timeStr").innerHTML = $times.substring(0,2) + ":" + $times.substring(2,4);
	document.getElementById("mainImg").src = "<?php echo "images/" . $dateStr . "/" . $dateStr . "_"; ?>" + $times + ".jpg";
	var img = new Image();
	img.src = "http://160.16.239.88/" + "<?php echo "images/" . $dateStr . "/" . $dateStr . "_"; ?>" + $times + ".jpg";
	img.onerror = function() {
	    document.getElementById("mainImg").src = "img/Noimage_image.png";
	} 

	document.getElementById("mainImg").style.display="block";
	document.getElementById("mainVideo").style.display="none";

}
function viewVideo($url){
	document.getElementById("mainVideo").src = "/" + $url.toString();
	document.getElementById("mainImg").style.display="none";
	document.getElementById("mainVideo").style.display="block";
}
function entryVideo($str){
    $.ajax('entry.php',
      {
        type: 'get',
        data: { str: $str },
        dataType: 'html'
      }
    )
    .done(function(data) {
      document.getElementById("cmdBox").innerHTML = "";
    })
    // 検索失敗時には、その旨をダイアログ表示
    .fail(function() {
      document.getElementById("cmdBox").innerHTML = "";
    });

}
</script>

<script src="js/jquery.ui.core.min.js"></script>
<script src="js/jquery.ui.datepicker.min.js"></script>
<script src="js/jquery.ui.datepicker-ja.min.js"></script>
<!--単体フォーム用-->
<script type="text/javascript">
$(function() {
  $("#xxdate").datepicker( {
    changeYear: true,  // 年選択をプルダウン化
    changeMonth: true  // 月選択をプルダウン化
  } );
 
  // 日本語化
  $.datepicker.regional['ja'] = {
    closeText: '閉じる',
    prevText: '<前',
    nextText: '次>',
    currentText: '今日',
    monthNames: ['1月','2月','3月','4月','5月','6月',
    '7月','8月','9月','10月','11月','12月'],
    monthNamesShort: ['1月','2月','3月','4月','5月','6月',
    '7月','8月','9月','10月','11月','12月'],
    dayNames: ['日曜日','月曜日','火曜日','水曜日','木曜日','金曜日','土曜日'],
    dayNamesShort: ['日','月','火','水','木','金','土'],
    dayNamesMin: ['日','月','火','水','木','金','土'],
    weekHeader: '週',
    dateFormat: 'yy/mm/dd',
    firstDay: 0,
    isRTL: false,
    showMonthAfterYear: true,
    yearSuffix: '年'};
  $.datepicker.setDefaults($.datepicker.regional['ja']);
});

function goMovie(){
	aForm.action = "main_dev.php";
	aForm.submit();
}
function onGraph(){
	aForm.action = "graph_dev.php";
	aForm.submit();
}
function onList(){
	aForm.action = "list.php";
	aForm.submit();
}

var playButton = function(){
    $.ajax('cmdBox.php',
      {
        type: 'get',
        data: { date: <?php echo $dateStr; ?>,time: $times },
        dataType: 'html'
      }
    )
    .done(function(data) {
      document.getElementById("cmdBox").innerHTML = data;
      setTimeout(playButton, 1000);
    })
    // 検索失敗時には、その旨をダイアログ表示
    .fail(function() {
      document.getElementById("cmdBox").innerHTML = "";
    });
} 
playButton();

</script>
<style>
/* 年プルダウンの変更 */
select.ui-datepicker-year{
  height: 2em!important;      /* 高さ調整 */
  margin-right:5px!important; /* 「年」との余白設定 */
  width:70px!important;       /* 幅調整 */
}
/* 月プルダウンの変更 */
select.ui-datepicker-month{
  height: 2em!important;      /* 高さ調整 */
  margin-left:5px!important;  /* 「年」との余白設定 */
  width:70px!important;       /* 幅調整 */
}
</style>

</head>

<body>
<div style="position: fixed;background-color:#FFF;height: 220px;">
<table borde=0 width="100%">
<td>
<form action="main.php" method="post" name="aForm">
<input type="text" name="date" id="xxdate" readonly="readonly" value="<?php echo $org_date; ?>">
<input type="button" value="　映像　" onClick="goMovie();"><input type="button" value="　グラフ　" onClick="onGraph();"><input type="button" value="銀鮭養殖日報" onClick="onList();">
</form>
</td><td>
<!--form method="post" action="setting.php" target="main">
    <input type="submit" name="logout" value="設定">
</form-->
</td><td align="right">
<form method="post" action="top.php" target="_top">
    <input type="submit" name="logout" value="ログアウト">
</form>
</table>
<hr>
<?php echo substr($dateStr,0,4); echo'/'; echo substr($dateStr,4,2); echo'/'; echo substr($dateStr,6,2); echo '　';

 $now = file_get_contents(dirname(__FILE__) . "/jma/now.dat");
 echo $now;
 echo '℃';
 echo '<br>';

//----- 基準日から１週間の平均値(Do値・水温・気温)を取得　開始 -----2020-02-11
 $enddate = substr($dateStr,0,4).'/'.substr($dateStr,4,2).'/'.substr($dateStr,6,2);
 $date = new DateTime($enddate);
 $startdate = $date->modify('-6 days')->format('Y/m/d');
 $currentdate = $startdate;
 $datafiledir ="/home/upload/infos/";
 $water_temp_all = 0;
 $do_all = 0;
 $count = 0;
 while ($currentdate <= $enddate){  //１週間分の水温・DO値データを読み込む
   $filename = $datafiledir.str_replace("/","",$currentdate).".dat";
   $fh = fopen($filename,"r");
   while ($line = fgets($fh)){
     $data1 = explode(',',$line);
     if ($data1[5] != 0 && $data1[6] != 0 ){
       $water_temp_all = $water_temp_all + $data1[5];  //水温の積算
       $do_all = $do_all + $data1[6];  //DO値の積算
       $count++;
     }
   }
   fclose($fh);
   $date = new DateTime($currentdate);
   $currentdate = $date->modify('+1 days')->format('Y/m/d');
 }
//----- 気温ファイルの読み込み -----
 $datafiledir ="/var/www/html/jma/";
 $currentdate = $startdate;
 $air_temp_all = 0;
 $count_temp = 0;
 while ($currentdate <= $enddate){  //１週間分の水温・DO値データを読み込む
   $filename = $datafiledir.str_replace("/","",$currentdate).".dat";
   $fh = fopen($filename,"r");
   $line = fgets($fh); //１行目は読み飛ばし
   $line = fgets($fh); //２行目に24時間の気温データが入っている
   $data1 = explode(',',$line);
   for ($count2 = 0; $count2 < 24; $count2++){
     if ($data1[$count2] <> ""){
       $air_temp_all = $air_temp_all + $data1[$count2];  //気温の積算
       $count_temp++;  //
     }
   }
   fclose($fh);
   $date = new DateTime($currentdate);
   $currentdate = $date->modify('+1 days')->format('Y/m/d');
 }

//echo "　開始日＝　".$startdate;
//echo "　終了日＝　".$enddate;
//echo "　水温合計＝　".$water_temp_all;
//echo "　水温平均＝　".$water_temp_all / $count;
//echo "　DO値合計＝　".$do_all;
//echo "　DO値平均＝　".$do_all / $count;
//echo "　件数　　＝　".$count;
//echo "　気温合計＝　".$air_temp_all;
//echo "　気温平均＝　".$air_temp_all / $count_temp;
//echo "　気温件数　　＝　".$count_temp;
//----- 基準日から１週間の平均値(Do値・水温・気温)を取得　終了 -----2020-02-11


 echo '<canvas id="myChart" style="position: relative; width: 1240px; height : 160px"></canvas>';

 echo '</div>';
 echo '<div style="padding:220px 0px 0px 0px;">';

//<*--測定値のリスト表示　開始---->
 echo '<table border="1" style="border-collapse: collapse" width=1240px>';
 echo '<tr>';
 echo '<td align="center" rowspan="2" style="font-size:small"  width="45">';
 echo date('Y/n/j',strtotime($startdate)).'<br>～<br>'.date('Y/n/j',strtotime($enddate)).'<br>の平均値';
 echo '</td>';
 echo '<td align="center" rowspan="2" style="border-top-style: hidden" width="3"></td>';


 echo '<td align="center" width="50">時刻</td>';
 echo '<td align="center" width="50">気温</td>';
 echo '<td align="center" width="50">水温</td>';
 echo '<td align="center" width="50">DO値</td>';
 echo '<td align="center" style="border-top-style: hidden" width="3"></td>';
 echo '<td align="center" width="50">時刻</td>';
 echo '<td align="center" width="50">気温</td>';
 echo '<td align="center" width="50">水温</td>';
 echo '<td align="center" width="50">DO値</td>';
 echo '<td align="center" style="border-top-style: hidden" width="3"></td>';
 echo '<td align="center" width="50">時刻</td>';
 echo '<td align="center" width="50">気温</td>';
 echo '<td align="center" width="50">水温</td>';
 echo '<td align="center" width="50">DO値</td>';

 echo '</tr>';


 $do_val = explode(',',$data[6]);
 $water_temp_val = explode(',',$data[5]);
 $temp_val = explode(',',$temperature);
for ($i = 0;$i < 48; $i=$i+12){

    if ($i == 12 ) {  //１週間の平均値を表示する為
       echo '<td align="center" rowspan="4" width="45">';
       echo '水温　';
       if ($count <> 0) {
          echo number_format(round($water_temp_all/$count,1),1);
       } else {
          echo '---';
       }
       echo '<br>気温　';
       if ($count_temp <> 0) {
          echo number_format(round($air_temp_all/$count_temp,1),1);
       } else {
          echo '---';
       }
       echo '<br>DO値　';
       if ($count <> 0) {
          echo number_format(round($do_all/$count,1),1);
       } else {
          echo '---';
       }
       echo '</td>';
       echo '<td align="center" rowspan="4" style="border-top-style:hidden ; border-bottom-style:hidden" width="3"></td>';
    }

    echo '<tr>';
    $flg1 = "off";
    for ($j = 0;$j < 3; $j++){
        if ($flg1 == "on") {
          echo '<td align="center" style="border-top-style:hidden ; border-bottom-style:hidden" width="3"></td>';
	}else{
          $flg1 = "on";
        }
        $moment = ($i/6)+($j*8);
        echo '<td align="center" width="50">  '. $moment . ':00 </td>';  //時刻
        echo '<td align="center" width="50">  '. $temp_val[$i+($j*48)] . '</td>';  //気温
        echo '<td align="center" width="50">  ';  //水温
        if ((empty($water_temp_val[$i+($j*48)]) && $moment < date("G")) || (empty($water_temp_val[$i+($j*48)]) && $dateStr < date('Ymd')) ) {
          echo '－';
        }else {
          echo $water_temp_val[$i+($j*48)];
        }
        echo '</td>';
        echo '<td align="center" width="50">  ';  //DO値
        if ((empty($do_val[$i+($j*48)]) && $moment < date("G")) || (empty($do_val[$i+($j*48)]) && $dateStr < date('Ymd')) ) {

          echo '－';
        }else {
          echo $do_val[$i+($j*48)];
        }
        echo '</td>';
    }
    echo '</tr>';
} 
?>
</table>
<!--測定値の表示　終了---->




<table width="100%">
<tr>
<td colspan="5" id="timeStr">
<?php echo substr($timeStr,0,2); ?>:<?php echo substr($timeStr,2,2); ?>
</td>
<td align="right" colspan="5" id="cmdBox"></td>
</tr>

<tr>
<td colspan="10" algin="center" style="text-align:center;">
<img src="<?php echo $mainImg; ?>" width="640" height="360" border=1 style="margin-left:auto;margin-right:auto;display:block" id="mainImg">
<video src="" width="640" height="360" style="margin-left:auto;margin-right:auto;display:none;" id="mainVideo" autoplay controls>
</td>
</tr>
<tr>
<?php 
$hh = substr($timeStr,0,2);
$m0 = substr($timeStr,2,1);
for($i = 0;$i < 10;$i++){
$mainImg = "img/Noimage_image.png";
if(file_exists("/var/www/html/images/" . $dateStr . "/" . $dateStr . "_" . $hh . $m0 . $i . "00.jpg" )){
	$mainImg = "images/" . $dateStr . "/" . $dateStr . "_" . $hh . $m0 . $i . "00.jpg";
}

 ?>
<td width="10%" algin="center" style="text-align:center;">
<!--追加部分-->
<?php echo substr($timeStr,0,2); ?>:<?php echo sprintf('%02d',substr($timeStr,2,2)+$i); ?><br />
<!--追加部分-->
<img src="<?php echo $mainImg; ?>" width="85" height="48"border=1 style="cursor:pointer;margin-left:auto;margin-right:auto;" onClick="viewImage('<?php echo $hh . $m0 . $i . "00"; ?>');">
</td>
<?php } ?>
</tr>
</table>

<div style="text-align:center;width:100%;height:200px;">
<table style="margin-left:auto;margin-right:auto;">
<!--追加部分-->
<tr><td></td><td>00分</td><td>10分</td><td>20分</td><td>30分</td><td>40分</td><td>50分</td></tr>
<!--追加部分-->
<?php for($i=0;$i<24;$i++){ ?>
<tr>
<td align="right"><?php echo str_pad($i, 2, 0, STR_PAD_LEFT); ?>時</td>
<?php
$hh = str_pad($i, 2, 0, STR_PAD_LEFT);
 for($j=0;$j<6;$j++){
$m0 = $j;
$mainImg = "img/Noimage_image.png";
if(file_exists("/var/www/html/images/" . $dateStr . "/" . $dateStr . "_" . $hh . $m0 . "000.jpg" )){
	$mainImg = "images/" . $dateStr . "/" . $dateStr . "_" . $hh . $m0 . "000.jpg";
}
  ?>
<td>
<a href="?date=<?php echo $dateStr; ?>&time=<?php echo $hh . $m0 ?>000"><img src="<?php echo $mainImg; ?>" width="85" height="48" border=1 style="margin-left:auto;margin-right:auto;"></a>
</td>
<?php } ?>
</tr>
<?php } ?>
</table>
</div>

</div>
</body>
</html>
<script>
var complexChartOption = {
    responsive: false,
    maintainAspectRatio: false,
    scales: {
        xAxes: [                           // Ｘ軸設定
            {
                display: true,
                barPercentage: 1,
                categoryPercentage: 0.9,
	            ticks: {          // スケール
	                stepSize: 1
	            },
                gridLines: {
                    display: false
                },
            }
        ],
        yAxes: [{
            id: "y-axis-1",   // Y軸のID
            type: "linear",   // linear固定 
            position: "left", // どちら側に表示される軸か？
            ticks: {          // スケール
                max: 40,
                min: -10,
                stepSize: 5
            },
        }, {
            id: "y-axis-2",
            type: "linear", 
            position: "right",
            ticks: {
                max: 100,
                min: 0,
                stepSize: 10
            },
            gridLines: { // このオプションを追加
                drawOnChartArea: false, 
            },
        }],
    }
};
</script>
<script>
var ctx = document.getElementById('myChart').getContext('2d');
ctx.canvas.width = window.innerWidth - 20;
ctx.canvas.height = 160;
var myChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: [<?php echo $label; ?>],
    datasets: [
    {
      type: 'line',
      label: '水温(-10m)',
      data: [<?php echo $water_temp; ?>],
      borderColor: "rgba(25, 25, 112,0.4)", 
      backgroundColor: "rgba(25, 25, 112,0.4)", 
      fill: false, // 中の色を抜く
      yAxisID: "y-axis-1",
    },
    {
      type: 'line',
      label: '気温',
      data: [<?php echo $air_temp; ?>],
      borderColor: "rgba(0, 100, 0,0.4)", 
      backgroundColor: "rgba(0,100,0,0.4)",
      fill: false, // 中の色を抜く
      yAxisID: "y-axis-1",
    },
    {
      label: 'DO',
      data: [<?php echo $do; ?>],
      borderColor: "rgba(100, 100, 0,0.4)", 
      backgroundColor: "rgba(100,100,0,0.4)",
      fill: false, // 中の色を抜く
      yAxisID: "y-axis-2",
    },
    {
      type: 'line',
      label: 'DO（80％ライン）',
      data: [80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80, 80],
      borderColor: "rgba(255, 9, 232, 1)", 
      backgroundColor: "rgba(255, 9, 232, 1)",
      pointRadius: 0,
      pointHoverRadius: 0,
      fill: false, // 中の色を抜く
      yAxisID: "y-axis-2",
    }]
  },
  options: complexChartOption
});
</script>
