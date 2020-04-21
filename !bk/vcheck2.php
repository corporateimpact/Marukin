<?php
$downcheck = file_get_contents("/home/upload/infos/battery/downflag2.ini");
$downcheck = str_replace("\r\n", '', $downcheck);
?>
<?php if (strcmp($downcheck, "DOWN") == 0): ?>
<img src="img/voltage.png" alt="down" />
<?php else: ?>
<p>Camera_down</p>
<?php elseif (strcmp($downcheck, "UP") == 0): ?>
<p>Camera_Recording.</p>
<?php else>
<p>ERROR</p>
