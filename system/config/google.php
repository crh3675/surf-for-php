<?php
/**
 * returns google code to be used in the site, pretty simple
 *
 * @author Craig Hoover
 */

return '
	<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
		var pageTracker = _gat._getTracker(\'UA-XXXXXX-1\');
		pageTracker._initData();
		pageTracker._trackPageview();
	</script>
';