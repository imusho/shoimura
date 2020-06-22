<?php
session_start();
require('dbconnect.php');

if (!empty($_SESSION['id'])) {
  //今までこの投稿にいいねボタンを押したことがあるかのチェック
  $check = $db->prepare('SELECT * FROM likes WHERE like_post_id=? AND like_member_id=?');
  $check->execute(array(
    $_GET['like_post_id'],
    $_SESSION['id']
  ));
  $check_result = $check->fetch();

  if (!$check_result) {
    //いいねを今まで押していない時に、ボタンを押した時にいいねを記録する処理
    $record = $db->prepare('INSERT INTO likes SET like_post_id=?, like_member_id=?, delete_flg=0, created=NOW()');
    $record->execute(array(
      $_GET['like_post_id'],
      $_SESSION['id']
    ));
  } else {
    if (intval($check_result['delete_flg']) === 1) {
      //いいねを今まで押したことがあるが現在は取り消されている時に、ボタンを押した時にいいねを記録する処理
      $record = $db->prepare('UPDATE likes SET delete_flg=0 WHERE like_post_id=? AND like_member_id=?');
      $record->execute(array(
        $_GET['like_post_id'],
        $_SESSION['id']
      ));
    } elseif (intval($check_result['delete_flg']) === 0) {
      //いいねを今まで押したことがあり現在もいいねされている時に、ボタンを押した時にいいねを取り消す処理
      $record = $db->prepare('UPDATE likes SET delete_flg=1 WHERE like_post_id=? AND like_member_id=?');
      $record->execute(array(
        $_GET['like_post_id'],
        $_SESSION['id']
      ));
    }
  }
}
header('Location: index.php'); exit();
 ?>
