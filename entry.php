<?php
	$str = $_GET["str"];
	$tmp = explode("/",$str);
	var_dump($tmp);
	//file();
	unlink("/var/www/html/encode/" . $tmp[1] . ".dat");
	unlink("/var/www/html/download/" . $tmp[1] . ".dat");
	file_put_contents("/var/www/html/entry/" . $tmp[1] . ".ent","");
?>
