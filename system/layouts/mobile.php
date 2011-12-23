<?php

$lang = substr($app->lang, 0, 2);
$dir = 'ltr';

switch($lang)
{
	case 'en': $dir = 'ltr';break;
	default : $dir = 'rtl';break;
}
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<!DOCTYPE html DOCROOT "-//WAPFORUM//DTD XHTML Mobile 1.2//EN"
"http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= $lang;?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= $app->charset;?>"/>
	<meta name="viewport" content="width=230,initial-scale=1.00, user-scalable=yes" />	
	<title><?= $app->title; ?></title>
	<link rel="stylesheet" type="text/css" href="/styles/mobile/global.css" media="screen,projection,handheld" charset="<?= $app->charset;?>" />
	<link rel="stylesheet" type="text/css" href="/styles/mobile/<?= $dir;?>.css" media="screen,projection,handheld" charset="<?= $app->charset;?>" />
	<link rel="shortcut icon" href="/favicon.ico" />
	<link rel="icon" type="image/vnd.microsoft.icon" href="/favicon.ico" />
	
	<script type="text/javascript" src="/scripts/jquery.js" charset="<?= $app->charset;?>"></script>
	<script type="text/javascript" src="/scripts/alshorfa.js" charset="<?= $app->charset;?>"></script>
</head>
<body class="<?=$dir;?> <?=$lang;?>">
<?php echo $app->content; ?>
</body>
</html>