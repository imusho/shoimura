<?php
session_start();
require('dbconnect.php');

//ログインしているかを確認する関数
function isLogin() {
	return isset($_SESSION['id']) && $_SESSION['time'] +3600 > time();
}

if (isLogin()) {
	//ログインしている
	$_SESSION['time'] = time();

	$members = $db->prepare('SELECT * FROM members WHERE id=?');
	$members->execute(array($_SESSION['id']));
	$member = $members->fetch();

	//メンバーが見つからない場合
	if (empty($member)) {
		header('Location: logout.php'); exit();
	}
} else {
	//ログインしていない
	header('Location: login.php'); exit();
}

//投稿を記録する
if (!empty($_POST)) {
	if ($_POST['message'] != '') {
		if (!isset($_POST['reply_post_id'])) {
			$message = $db->prepare('INSERT INTO posts SET message=?, member_id=?, reply_post_id=?, created=NOW(), rt_post_id=0, post_delete_flg=0');
			$message->execute(array(
				$_POST['message'],
				$member['id'],
				$_POST['reply_post_id']
			));
		} else {
			$message = $db->prepare('INSERT INTO posts SET message=?, member_id=?, created=NOW(), rt_post_id=0, post_delete_flg=0');
			$message->execute(array(
				$_POST['message'],
				$member['id']
			));
		}
		header('Location: index.php'); exit();
	}
}

//投稿を取得する
if (isset($_GET['page'])) {
	$page = $_GET['page'];
} else {
	$page = 1;
}
$page = max($page, 1);

//最終ページを取得する
$counts = $db->query('SELECT COUNT(*) AS cnt FROM posts');
$cnt = $counts->fetch();
$maxPage = ceil($cnt['cnt'] / 5);
$page = min($page, $maxPage);

$start = ($page-1) * 5;

$posts = $db->prepare('SELECT p.*, m.name, m.picture FROM posts p, members m WHERE p.member_id=m.id AND post_delete_flg=0 ORDER BY p.created DESC LIMIT ?,5');
$posts->bindParam(1, $start, PDO::PARAM_INT);
$posts->execute();

//返信の場合
if (isset($_GET['res'])) {
	$response = $db->prepare('SELECT p.*, m.name FROM posts p, members m WHERE p.id=? AND p.member_id=m.id ORDER BY p.created DESC');
	$response->execute(array($_GET['res']));

	$table = $response->fetch();
	$message = '@' . $table['name'] . ' ' . $table['message'];
}

//htmlspecialcharsのショートカット
function h($value) {
	return htmlspecialchars($value, ENT_QUOTES);
}

//本文内のURLにリンクを設定する
function makeLink($value) {
	return mb_ereg_replace('(https?://[-_.!~*\'()a-zA-Z0-9;/?:@&=+$,%#]+)', '<a href="\1" target="_blank">\1</a>', $value);
}

 ?>

<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title>ひとこと掲示板</title>

	<link rel="stylesheet" href="style.css" />
	<script src="https://kit.fontawesome.com/88931390c8.js" crossorigin="anonymous"></script>
</head>

<body>
<div id="wrap">
  <div id="head">
    <h1>ひとこと掲示板</h1>
  </div>
  <div id="content">
		<div style="text-align: right">
			<a href="logout.php">ログアウト</a>
		</div>
		<form action="" method="post">
			<dl>
				<dt><?php print(h($member['name'])); ?>さんメッセージをどうぞ</dt>
				<dd>
					<textarea name="message" rows="5" cols="50"><?php if(isset($message)) {echo h($message);} ?></textarea>
					<input type="hidden" name="reply_post_id" value="<?php echo h($_GET['res']); ?>">
				</dd>
			</dl>
			<div>
				<p>
					<input type="submit" value="投稿する">
				</p>
			</div>
		</form>
		<?php foreach($posts as $post): ?>

			<!-- $postがリツイートの場合 -->
			<?php if($post['rt_post_id'] >= 1): ?>
				<div class="msg">
					<?php
					//リツイート時のリツイート元postの取得
						$rtPosts = $db->prepare('SELECT p.*, m.name, m.picture FROM posts p, members m WHERE p.member_id=m.id AND p.id=?');
						$rtPosts->execute(array($post['rt_post_id']));
						$rtPost = $rtPosts->fetch();
				 	?>
					<img src="member_picture/<?php echo h($rtPost['picture']) ?>" alt="<?php echo h($rtPost['name']) ?>のイメージ" width="48" height="48">
					<div>

						<p class="rt-message"><i class="fas fa-retweet"></i><?php print($post['name']); ?>さんがリツイート</p>
						<p><?php echo makeLink(h($post['message'])); ?><span class="name">（<?php echo h($rtPost['name']); ?>）</span>
							[<a href="index.php?res=<?php echo h($rtPost['id']) ?>">Re</a>]</p>
						<p class="day">

							<!-- いいねボタン -->
							<?php
							//いいねステータスチェック用のデータ取得
							$likeChecks = $db->prepare('SELECT delete_flg FROM likes WHERE like_post_id=? AND like_member_id =?');
							$likeChecks->execute(array(
								$rtPost['id'],
								$member['id']
							));
							$likeCheck = $likeChecks->fetch();
							?>

							<a class="like-link" href="like_do.php?like_post_id=<?php echo h($rtPost['id']); ?>">
								<!-- 未いいねの場合の分岐 -->
								<?php if (!isset($likeCheck['delete_flg']) || intval($likeCheck['delete_flg']) === 1): ?>
									<i class="far fa-heart"></i>
									<!-- いいね済の場合の分岐 -->
								<?php else: ?>
									<i class="fas fa-heart like-btn"></i>
								<?php endif; ?>
								<?php
								//いいね数の取得
								$likeCounts = $db->prepare('SELECT COUNT(*) AS cnt FROM likes WHERE like_post_id=? AND delete_flg=0');
								$likeCounts->execute(array($rtPost['id']));
								$likeCount = $likeCounts->fetch();

								print($likeCount['cnt']);
								?>
							</a>

							<!-- リツイートボタン -->
							<?php
							//リツイートステータスチェック用のデータ取得
							$rtChecks = $db->prepare('SELECT post_delete_flg FROM posts WHERE rt_post_id=? AND member_id=?');
							$rtChecks->execute(array(
								$rtPost['id'],
								$member['id']
							));
							$rtCheck = $rtChecks->fetch();
							?>
							<a class="rt-link" href="rt_do.php?rt_post_id=<?php print(h($rtPost['id'])); ?>">
								<?php if(!isset($rtCheck['post_delete_flg']) || intval($rtCheck['post_delete_flg']) === 1): ?>
									<i class="fas fa-retweet"></i>
								<?php else: ?>
									<i class="fas fa-retweet rt-btn"></i>
								<?php endif; ?>

								<?php
								//リツイート数の取得
								$rtCounts = $db->prepare('SELECT COUNT(*) AS cnt FROM posts WHERE rt_post_id=? AND post_delete_flg=0');
								$rtCounts->execute(array($rtPost['id']));
								$rtCount = $rtCounts->fetch();

								print($rtCount['cnt']);
								?>
							</a>


							<a href="view.php?id=<?php echo h($rtPost['id']); ?>"><?php echo h($rtPost['created']); ?></a>
							<?php if ($rtPost['reply_post_id'] > 0): ?>
								<a href="view.php?id=<?php echo h($rtPost['reply_post_id']); ?>">返信元のメッセージ</a>
							<?php endif; ?>
							<?php if ($_SESSION['id'] === $rtPost['member_id']): ?>
								[<a href="delete.php?id=<?php echo h($rtPost['id']); ?>" style="color: #F33;">削除</a>]
							<?php endif; ?>
						</p>
					</div>
				</div>

			<!-- $postがリツイート以外の場合 -->
			<?php else: ?>
				<div class="msg">
					<img src="member_picture/<?php echo h($post['picture']) ?>" alt="<?php echo h($post['name']) ?>のイメージ" width="48" height="48">
					<div class="">
						<p><?php echo makeLink(h($post['message'])); ?><span class="name">（<?php echo h($post['name']); ?>）</span>
							[<a href="index.php?res=<?php echo h($post['id']) ?>">Re</a>]</p>
						<p class="day">

							<!-- いいねボタン -->
							<?php
							//いいねステータスチェック用のデータ取得
							$likeChecks = $db->prepare('SELECT delete_flg FROM likes WHERE like_post_id=? AND like_member_id =?');
							$likeChecks->execute(array(
								$post['id'],
								$member['id']
							));
							$likeCheck = $likeChecks->fetch();
							?>

							<a class="like-link" href="like_do.php?like_post_id=<?php echo h($post['id']); ?>">
								<!-- 未いいねの場合の分岐 -->
								<?php	if (!isset($likeCheck['delete_flg']) || intval($likeCheck['delete_flg']) === 1): ?>
									<i class="far fa-heart"></i>
									<!-- いいね済の場合の分岐 -->
								<?php	else: ?>
									<i class="fas fa-heart like-btn"></i>
								<?php endif; ?>
								<?php
								//いいね数の取得
								$likeCounts = $db->prepare('SELECT COUNT(*) AS cnt FROM likes WHERE like_post_id=? AND delete_flg=0');
								$likeCounts->execute(array($post['id']));
								$likeCount = $likeCounts->fetch();

								print($likeCount['cnt']);
								?>
							</a>

							<!-- リツイートボタン -->
							<?php
							//リツイートステータスチェック用のデータ取得
							$rtChecks = $db->prepare('SELECT post_delete_flg FROM posts WHERE rt_post_id=? AND member_id=?');
							$rtChecks->execute(array(
								$post['id'],
								$member['id']
							));
							$rtCheck = $rtChecks->fetch();
							?>
							<a class="rt-link" href="rt_do.php?rt_post_id=<?php print(h($post['id'])); ?>">
								<?php if(!isset($rtCheck['post_delete_flg']) || intval($rtCheck['post_delete_flg']) === 1): ?>
									<i class="fas fa-retweet"></i>
								<?php else: ?>
									<i class="fas fa-retweet rt-btn"></i>
								<?php endif; ?>

								<?php
								//リツイート数の取得
								$rtCounts = $db->prepare('SELECT COUNT(*) AS cnt FROM posts WHERE rt_post_id=? AND post_delete_flg=0');
								$rtCounts->execute(array($post['id']));
								$rtCount = $rtCounts->fetch();

								print($rtCount['cnt']);
								?>
							</a>


							<a href="view.php?id=<?php echo h($post['id']); ?>"><?php echo h($post['created']); ?></a>
							<?php if ($post['reply_post_id'] > 0): ?>
								<a href="view.php?id=<?php echo h($post['reply_post_id']); ?>">返信元のメッセージ</a>
							<?php endif; ?>
							<?php if ($_SESSION['id'] === $post['member_id']): ?>
								[<a href="delete.php?id=<?php echo h($post['id']); ?>" style="color: #F33;">削除</a>]
							<?php endif; ?>
						</p>
					</div>
				</div>
			<?php endif ?>
		<?php endforeach; ?>
		<ul class="paging">
			<?php if($page > 1): ?>
				<li><a href="index.php?page=<?php print($page-1); ?>">前のページへ</a></li>
			<?php else: ?>
				<li>前のページへ</li>
			<?php endif; ?>
			<?php if($page < $maxPage): ?>
				<li><a href="index.php?page=<?php print($page+1); ?>">次のページへ</a></li>
			<?php else: ?>
				<li>次のページへ</li>
			<?php endif; ?>
		</ul>
  </div>

</div>
</body>
</html>
