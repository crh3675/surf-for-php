<?
$app->title = $i18n->title;
?>
<div id="error-message">
	<h1>Whoa!</h1>The document (<?php echo strip_tags(urldecode($_SERVER['QUERY_STRING']));?>) or resource you are trying to access does not exist at the specified location.

	<p class="note">I can be found under /public/errors/index.php.</p>
</div>

