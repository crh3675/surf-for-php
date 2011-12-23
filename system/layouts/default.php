<!DOCTYPE html>
<html lang="<?= $lang;?>">
<head>
	<meta charset="<?= $app->charset;?>"/>
	<title><?=$app->title; ?></title>
	<link rel="stylesheet" type="text/css" href="/styles/global.css" media="screen,projection" charset="<?= $app->charset;?>" />
	<link rel="shortcut icon" href="/favicon.ico" />
	<link rel="icon" type="image/vnd.microsoft.icon" href="/favicon.ico" />
	<script type="text/javascript" src="/javascripts/jquery.js" charset="utf-8"></script>
	<script type="text/javascript" src="/javascripts/app.js" charset="utf-8"></script>
</head>

<body class="<?=$lang;?>">
<div id="scalable">
	<img src="/images/bg.jpg" alt="Bg" />
</div>
<div id="container">
<?=$app->content;?>
</div>
<?=$app->google_code; ?>
<script>
app.init();
</script>
</body>
</html>
