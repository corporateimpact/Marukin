<?php
function getFileList($dir) {
    $files = glob(rtrim($dir, '/') . '/*');
    $list = array();
    foreach ($files as $file) {
        if (is_file($file)) {
            $list[] = $file;
        }
        if (is_dir($file)) {
            $list = array_merge($list, getFileList($file));
        }
    }

    return $list;
}


$dir = "/home/upload/videos/";

echo "<html>";
foreach (getFileList($dir) as $values){
$value = str_replace("/home/upload","",$values) ;
echo "<a href=\"http://160.16.239.88".$value."\">".$value."</a><br>";
}
echo "</html>";
