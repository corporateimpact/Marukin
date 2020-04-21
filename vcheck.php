<div style="position:absolute; top:-3px; left:440px">
<?php
$downcheck = file_get_contents("/home/upload/infos/battery/downflag2.ini");
$downcheck = str_replace("\r\n", '', $downcheck);
if (strcmp($downcheck, "DOWN") == 0){
	echo '<img src="img/voltage.png" alt="down." />';
}elseif (strcmp($downcheck, "UP") == 0){
	echo '<img src="img/recording.png" alt="recording." />';
}else{
	echo "ERROR";
}
?>
</div>