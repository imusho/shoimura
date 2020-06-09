<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id'])) {
	$id = $_GET['id'];

	//検査をする
	$messages = $db->prepare('SELECT * FROM posts WHERE id=?');
	$messages->execute(array($id));
	$message = $messages->fetch();

	if ($message['member_id'] == $_SESSION['id']) {
		//削除する
		$del = $db->prepare('DELETE FROM posts WHERE id=?');
		$del->execute(array($id));
	}
}

header('Location: index.php'); exit();
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
