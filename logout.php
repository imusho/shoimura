<?php
session_start();

//セッション情報を削除
$_SESSION = array();
if (ini_get("session.use_cookies")) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time()-42000,
	$params["path"], $params["domain"],
	$params["secure"], $params["httponly"]);
}
session_destroy();

//cookie情報も削除
setcookie('email', '', time()-3600);
setcookie('password', '', time()-3600);

header('Location: login.php');
exit();
 ?>

<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>ひとこと掲示板</title>

	<link rel="stylesheet" href="../style.css" />
</head>

<body>
<div id="wrap">
  <div id="head">
    <h1>ひとこと掲示板</h1>
  </div>
  <div id="content">

  </div>

</div>
</body>
</html>
