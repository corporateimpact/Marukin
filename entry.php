<?php
    // 20220629修正　直接ファイル要求するように変更　--袰岩
    //$str = 'videos/20220628_062700';  //テスト用
	$str = $_GET["str"];
	$tmp = explode("/",$str);
	$logfile = '/home/upload/videos.log';
	//var_dump($tmp);
	$daytime = explode("_", $tmp[1]);
	//file();
    //unlink("/var/www/html/encode/" . $tmp[1] . ".dat");
	//unlink("/var/www/html/download/" . $tmp[1] . ".dat");

    $msg_log = date('[Y-m-d H:i:s]'). ' - Request a Video file. ['. $tmp[1]. '.mp4]'. "\n";
    $log = fopen($logfile, 'a');
    fwrite($log, $msg_log);
    fclose($log);

	// ↓はダウンロードボタンがリクエスト中に表示変更されるようになるので一旦そのまま残します　20220629袰岩
	file_put_contents("/var/www/html/entry/" . $tmp[1] . ".ent","");

	$cmd = 'sudo scp -i "/root/.ssh/id_rsa2"';
	$cmd = $cmd. ' marukinpi@210.156.171.241:/mnt/usbssd/data/video_org/1/'. $daytime[0]. '/'. $tmp[1]. '.mp4';
	$cmd = $cmd. ' /home/upload/'. $tmp[1]. '.mp4';
    exec($cmd, $opt, $return_ver);
    if($return_ver===0){
        $msg_log = date('[Y-m-d H:i:s]'). ' - ['. $tmp[1]. '.mp4] Finished.'. "\n";
        $log = fopen($logfile, 'a');
        fwrite($log, $msg_log);
        fclose($log);
    } else {
        $msg_log = date('[Y-m-d H:i:s]'). ' - ['. $tmp[1]. '.mp4] Request error.'. "\n";
        $log = fopen($logfile, 'a');
        fwrite($log, $msg_log);
        fclose($log);
    };

    $cmd = 'mv /home/upload/'. $tmp[1]. '.mp4 /home/upload/videos/'. $daytime[0]. '/'. $tmp[1]. '.mp4';
    $msg_log = date('[Y-m-d'). ' '. time('H:M:S]'). ' - Finished.';
    exec($cmd, $opt, $return_ver);
    unlink("/var/www/html/entry/" . $tmp[1] . ".ent");

	//exec()

?>
