<!DOCTYPE html>
<html lang="<?=$app->lang;?>">	
<head>
	<meta charset="<?=$app->charset;?>" />
	<title><?=$app->title; ?></title>
	<!--[if lt IE 9]><script src="/javascripts/html5.js"></script><![endif]-->
	<link rel="stylesheet" type="text/css" href="/styles/admin.css" media="screen,projection" charset="<?= $app->charset;?>" />
	<script type="text/javascript" src="/javascripts/jquery.js" charset="utf-8"></script>
	<script type="text/javascript" src="/javascripts/admin.js" charset="utf-8"></script>
</head>

<body>
	<header>Administration &raquo; Tables 
	<?php echo isset($_GET['table']) ? ' &raquo; ' . readable($_GET['table']) : ''?>
	<?php echo isset($_GET['action']) ? ' &raquo; '.readable($_GET['action']) : '';?>
	<?php echo isset($_GET['id']) ? ' &raquo; '.readable($_GET['id']) : '';?>	
	</header>
	<?=$app->content;?>
</body>
</html>
