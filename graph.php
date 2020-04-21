<?php
session_start();
if (!isset($_SESSION['USER'])) {
    header('Location: index.php');
    exit;
}

if(isset($_POST['logout'])){
    session_destroy();
    header('Location: index.php');
    exit;
}

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
		$row = preg_replace("/\n/","",$row);
		$tmp = explode(",",$row);
		$dArray{str_replace(":","",$tmp[0])} = $tmp;
	}
}
$max =  array_fill(1, 10, -999);
$min =  array_fill(1, 10, 999);
$data = array();
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
				if(isset($dArray{$h . $m . "00"}[$j]) && $dArray{$h . $m . "00"}[$j] != ""){
					$data[$j] .= "'" . $dArray{$h . $m . "00"}[$j] . "',";
					if($max[$j] < $dArray{$h . $m . "00"}[$j]){
						$max[$j] = ceil($dArray{$h . $m . "00"}[$j]);
					}
					if($min[$j] > $dArray{$h . $m . "00"}[$j]){
						$min[$j] = floor($dArray{$h . $m . "00"}[$j]);
					}
				}else{
					$data[$j] .= ",";
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

?>
<!DOCTYPE html>
<html>
<head>
    <title>グラフ</title>
<meta name="viewport" content="width=device-width">
<link rel="stylesheet" href="css/jquery-ui.min.css" />

<script src="js/jquery-1.11.0.min.js"></script>
<script src="js/chart.js"></script>

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
	aForm.action = "main.php";
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
<?php echo substr($dateStr,0,4); ?>/<?php echo substr($dateStr,4,2); ?>/<?php echo substr($dateStr,6,2); ?>
　<?php
 $now = file_get_contents(dirname(__FILE__) . "/jma/now.dat");
 echo $now;
 ?>℃
<br>

<canvas id="myChart1"></canvas>
<canvas id="myChart2"></canvas>

</body>
</html>
<script>
var complexChartOption1 = {
    responsive: false,
    maintainAspectRatio: false,
    scales: {
        xAxes: [                           // Ｘ軸設定
            {
                display: true,
                barPercentage: 1,
                //categoryPercentage: 1.8,
                gridLines: {
                    display: false
                },
            }
        ],
        yAxes: [{
            id: "y-axis-1",
            type: "linear",
            position: "left",
            ticks: {
                max: 200, //<?php echo $max[1] + 10; ?>,
                min: 0, //<?php echo $min[1] - 10; ?>,
                stepSize: 20
            },
        }, {
            id: "y-axis-2",
            type: "linear", 
            position: "right",
            ticks: {
                max: 15, //<?php echo $max[2] + 10; ?>,
                min: 10, //<?php echo $min[2] - 10; ?>,
                stepSize: 1
            },
            gridLines: {
                drawOnChartArea: false, 
            }
        }],
    }
};
var complexChartOption2 = {
    responsive: false,
    maintainAspectRatio: false,
    scales: {
        xAxes: [                           // Ｘ軸設定
            {
                display: true,
                barPercentage: 0.9,
                //categoryPercentage: 1,
                gridLines: {
                    display: false
                },
            }
        ],
        yAxes: [{
            id: "y-axis-3",
            type: "linear",
            position: "left",
            ticks: {
                max: 60, //<?php echo $max[3] + 10; ?>,
                min: -10, //<?php echo $min[3] - 10; ?>,
                stepSize: 10
            },
        }]
//        , {
//            id: "y-axis-4",
//            type: "linear", 
//            position: "right",
//            ticks: {
//                max: 60, //<?php echo $max[4] + 10; ?>,
//                min: -10, //<?php echo $min[4] - 10; ?>,
//                stepSize: 10
//            },
//            gridLines: {
//                drawOnChartArea: false, 
//            }
//       }],
    }
};

</script>

<script>
var ctx = document.getElementById('myChart1').getContext('2d');
ctx.canvas.width = window.innerWidth - 20;
ctx.canvas.height = 250;
var myChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: [<?php echo $label; ?>],
    datasets: [{
      type: 'line',
      label: '発電量(W)',
      data: [<?php echo $data[1]; ?>],
      borderColor: "rgba(255, 241, 0,0.4)", 
      backgroundColor: "rgba(255, 241, 0,0.4)", 
      fill: true, // 中の色を抜く
      yAxisID: "y-axis-1",
    },
    {
      label: 'バッテリー残量(V)',
      data: [<?php echo $data[2]; ?>],
      borderColor: "rgba(228,0,127,0.4)", 
      backgroundColor: "rgba(228,0,127,0.4)",
      fill: false, // 中の色を抜く
      yAxisID: "y-axis-2",
    }]
  },
  options: complexChartOption1
});

var ctx = document.getElementById('myChart2').getContext('2d');
ctx.canvas.width = window.innerWidth - 20;
ctx.canvas.height = 250;
var myChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: [<?php echo $label; ?>],
    datasets: [{
      type: 'line',
      label: 'バッテリー温度(℃)',
      data: [<?php echo $data[3]; ?>],
      borderColor: "rgba(25, 25, 112,0.4)", 
      backgroundColor: "rgba(25, 25, 112,0.4)", 
      fill: false, // 中の色を抜く
      yAxisID: "y-axis-3",
    },
    {
      label: 'コントローラー内部温度(℃)',
      data: [<?php echo $data[4]; ?>],
      borderColor: "rgba(0, 100, 0,0.4)", 
      backgroundColor: "rgba(0,100,0,0.4)",
      fill: false, // 中の色を抜く
      yAxisID: "y-axis-3",
    }]
  },
  options: complexChartOption2
});


</script>