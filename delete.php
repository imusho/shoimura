<?php
session_start();
require('dbconnect.php');

if (isset($_SESSION['id'])) {
	$id = $_GET['id'];

	//検査をする
	$messages = $db->prepare('SELECT * FROM posts WHERE id=?');
	$messages->execute(array($id));
	$message = $messages->fetch();

	if ($message['member_id'] === $_SESSION['id']) {

		$db->beginTransaction();
		//削除する
		$del = $db->prepare('DELETE FROM posts WHERE id=?');
		$del->execute(array($id));

		//リツイートを削除する
		$del_rt = $db->prepare('DELETE FROM posts WHERE rt_post_id=?');
		$del_rt->execute(array($id));

		$db->commit();
	}
}

header('Location: index.php'); exit();
