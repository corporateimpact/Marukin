<?php
 
    session_start();
    $message = '';
    if(isset($_POST['login'])){
        if ($_POST['id'] == 'user' && $_POST['password'] == 'marukin'){
            $_SESSION["USER"] = 'user';
            header("Location: main.php");
            exit;
        }
        else{
            $message = 'IDかPWが間違っています。';
        }
    }
 
?>
 
<!DOCTYPE html>
<html>
<head>
    <title>ログイン</title>
    <link rel="stylesheet" type="text/css" href="login.css">
    <meta charset="utf-8">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400,700">
</head>
<body>
<?php echo $message ?>
<form method="post" action="index2.php">
<h1>モニタリング</h1>
    <div id="login">
      <form name='form-login'>
        <span class="fontawesome-user"></span>
    <input type="text" id="user" placeholder="UserID">
        <span class="fontawesome-lock"></span>
    <input type="password" name="password" placeholder="Password">
    <input type="submit" name="login" value="ログイン">Login
    </div>
</form>
</bo