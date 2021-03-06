<?php
session_start();
if (!isset($_SESSION['USER'])) {
    header('Location: index.php');
    exit;
}

function get_do($str1,$str2){
	$T = floatval($str1);
	$OP = floatval($str2);
	$S  = 35;

	$A1 = -173.4292;
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
$dArray;
if(file_exists("/var/www/html/infos/" . $dateStr . ".dat")){
	$data = File("/var/www/html/infos/" . $dateStr . ".dat");
	$label;
	$temperature;
	$humidity;
	$water_temp;
	foreach($data as $row){
		$tmp = explode(",",$row);
		$dArray{str_replace(":","",$tmp[0])} = $tmp;
	}
}

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

$mainImg = "img/Noimage_image.png";
if(file_exists("/var/www/html/images/" . $dateStr . "/" . $dateStr . "_" . $timeStr . ".jpg" )){
	$mainImg = "images/" . $dateStr . "/" . $dateStr . "_" . $timeStr . ".jpg";
}
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
	aForm.action = "main3.php";
	aForm.submit();
}
function onGraph(){
	aForm.action = "graph.php";
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
<form action="main3.php" method="post" name="aForm">
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
<?php echo substr($dateStr,0,4); ?>/<?php echo substr($dateStr,4,2); ?>/<?php echo substr($dateStr,6,2); ?>
　<?php
 $now = file_get_contents(dirname(__FILE__) . "/jma/now.dat");
 echo $now;
 ?>℃
<br>
<canvas id="myChart" style="position: relative; width: 1240px; height : 160px"></canvas>
</div>
<div style="padding:220px 0px 0px 0px;">

<!--測定値のリスト表示　開始---->
<table border="1" style="border-collapse: collapse" width=1240px>
<tr>
<td align="center" width="50">時間</td>
<td align="center" width="50">気温</td>
<td align="center" width="50">水温</td>
<td align="center" width="50">DO値</td>
<td align="center" style="border-top-style: hidden" width="3"></td>
<td align="center" width="50">時間</td>
<td align="center" width="50">気温</td>
<td align="center" width="50">水温</td>
<td align="center" width="50">DO値</td>
<td align="center" style="border-top-style: hidden" width="3"></td>
<td align="center" width="50">時間</td>
<td align="center" width="50">気温</td>
<td align="center" width="50">水温</td>
<td align="center" width="50">DO値</td>
</tr>

<?php
 $do_val = explode(',',$data[6]);
 $water_temp_val = explode(',',$data[5]);
 $temp_val = explode(',',$temperature);
for ($i = 0;$i < 48; $i=$i+12){
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
      data: [<?php echo $data[5]; ?>],
      borderColor: "rgba(25, 25, 112,0.4)", 
      backgroundColor: "rgba(25, 25, 112,0.4)", 
      fill: false, // 中の色を抜く
      yAxisID: "y-axis-1",
    },
    {
      type: 'line',
      label: '気温',
      data: [<?php echo $temperature; ?>],
      borderColor: "rgba(0, 100, 0,0.4)", 
      backgroundColor: "rgba(0,100,0,0.4)",
      fill: false, // 中の色を抜く
      yAxisID: "y-axis-1",
    },
    {
      label: 'DO',
      data: [<?php echo $data[6]; ?>],
      borderColor: "rgba(100, 100, 0,0.4)", 
      backgroundColor: "rgba(100,100,0,0.4)",
      fill: false, // 中の色を抜く
      yAxisID: "y-axis-2",
    }]
  },
  options: complexChartOption
});
</script>
