<?php

error_reporting(E_ALL ^ (E_DEPRECATED|E_USER_DEPRECATED));

// define where the SuRF files are
define('SITEROOT', realpath('../'));
define('SYSTEMPATH', realpath('../system'));
define('DOCROOT', realpath('../public'));

// require the common library files
require_once(SYSTEMPATH.'/library/common.php');

// lets's start the app
// all values for these vars are available through
//
// $app->{varname}
//
startApp(
	array(
		'title'				=> 'Surf - Simple Routing Framework for PHP, Procedural style!',
		'content'			=> '',
		'charset'			=> 'utf-8',
		'i18n' 				=> true, 		// we want to internationalize
		'i18n_use'			=> 'short',		// 'short' or 'long'
		'i18n_accept'		=> 'both',   	// 'short', 'long', or 'both'
		'default_layout' 	=> 'default', 	// we have a default layout
		'default_route' 	=> '/home', 	// we have a default route
		'google_code'		=> loadConfig('google'), // we have google code to use
		'error_404'			=> '/errors/404',
		'db'					=>	loadConfig('database')
	)
);

// minor cleanup
endApp();

?>
  
