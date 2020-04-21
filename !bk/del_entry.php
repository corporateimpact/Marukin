<?php
	if(isset($_GET)){
		if(isset($_GET["dat"])){
			unlink('/var/www/html/entry/' . $_GET["dat"] . ".ent");
			unlink('/var/www/html/encode/' . $_GET["dat"] . ".dat");
			file_put_contents("/var/www/html/download/" . $_GET["dat"] . ".dat","");
			echo "DEL:" . $_GET["dat"];
		}else{
			exec ('find /var/www/html/entry/ -name "*.ent" | xargs rm', $output);
		}
	}else{
		exec ('find /var/www/html/entry/ -name "*.ent" | xargs rm', $output);
	}
?>