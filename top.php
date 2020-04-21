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
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>管理画面</title>
</head>
<frameset rows="*" cols="0,*" framespacing="0" frameborder="NO" border="0">
  <frame src="menu.php" name="menu" scrolling="auto" noresize >
  <frame src="main.php" name="main" scrolling="auto">
</frameset>
<noframes>
<body>
</body>
</noframes>
</html>