<?php
$dateStr = date("Ymd");
$timeStr = date("Hi00");
if(isset($_POST['date'])){
	if($_POST['date'] != ""){
		$dateStr = str_replace("/","",$_POST['date']);
		$timeStr = "000000";
	}
}
if(isset($_GET['date'])){
	if($_GET['date'] != ""){
		$dateStr = str_replace("/","",$_GET['date']);
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
if(file_exists("/var/www/html/videos/" . $dateStr . "/" . $dateStr . "_" . $timeStr . ".mp4" )){
//	$fstat = stat("/var/www/html/videos/" . $dateStr . "/" . $dateStr . "_" . $timeStr . ".mp4");
//	$mp4 = $fstat["ctime"] . "  ";
//	$now = time();
//	if($mp4 + 60 > $now){
//		echo '<table><td>再生準備中</td><td><img src="./3.gif" height="25px"></td></table>';
//	}else{
		echo '<input type="button" value="　再　生　" onClick="viewVideo(\'' . "videos/" . $dateStr . "/" . $dateStr . "_" . $timeStr . ".mp4" . '\');">';
//	}
}else{
	if(file_exists("/var/www/html/download/" . $dateStr . "_" . $timeStr . ".dat" )){
		$fstat = stat("/var/www/html/download/" . $dateStr . "_" . $timeStr . ".dat");
		if(time()>1800+$fstat["mtime"]){
			echo '動画の生成に失敗しました<br>';
			echo '<input type="button" value="リトライ" onClick="entryVideo(\'' . "" . $dateStr . "/" . $dateStr . "_" . $timeStr . '\');">';
		}else{
			echo '<table><td>動画受信中</td><td><img src="./2.gif" height="25px"></td></table>';
		}
	}else if(file_exists("/var/www/html/encode/" . $dateStr . "_" . $timeStr . ".dat" )){
		echo '<table><td>エンコード中</td><td><img src="./3.gif" height="25px"></td></table>';
	}else if(file_exists("/var/www/html/entry/" . $dateStr . "_" . $timeStr . ".ent" )){
		echo '<table><td>リクエスト送信中</td><td><img src="./1.gif" height="25px"></td></table>';
	}else{
		echo '<input type="button" value="ダウンロード" onClick="entryVideo(\'' . "" . $dateStr . "/" . $dateStr . "_" . $timeStr . '\');">';
	}
}
?>
