<?php
session_start();
require('dbconnect.php');

//リツイート元の投稿の取得
$rt_posts = $db->prepare('SELECT * FROM posts WHERE id=?');
$rt_posts->execute(array($_GET['rt_post_id']));
$rt_post = $rt_posts->fetch();

if (isset($_SESSION['id'])) {
  //今までにこの投稿にリツイートしたことがあるかのチェック
  $checks = $db->prepare('SELECT * FROM posts WHERE rt_post_id=? AND member_id=?');
  $checks->execute(array(
    $_GET['rt_post_id'],
    $_SESSION['id']
  ));
  $check = $checks->fetch();

  //今までにこの投稿にリツイートしたことがない場合
  if (!$check) {
    $record = $db->prepare('INSERT INTO posts SET message=?, member_id=?, created=NOW(), rt_post_id=?, post_delete_flg=0');
    $record->execute(array(
      $rt_post['message'],
      $_SESSION['id'],
      $_GET['rt_post_id']
    ));
  } else {
    //今までに押したことがあり、現在は未リツイートの時
    if ($check['post_delete_flg'] == 1) {
      $record = $db->prepare('UPDATE posts SET post_delete_flg=0, created=NOW() WHERE member_id=? AND rt_post_id=?');

    } elseif ($check['post_delete_flg'] == 0) {
      $record = $db->prepare('UPDATE posts SET post_delete_flg=1 WHERE member_id=? AND rt_post_id=?');
    }
    $record->execute(array(
      $_SESSION['id'],
      $_GET['rt_post_id']
    ));
  }
}

header('Location: index.php'); exit();
 ?>
