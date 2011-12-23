<?php

/**
 * strip google code and change layout
 *
 * @author Craig Hoover
 */
$app->google_code = '';
$app->layout = 'admin';

/**
 * ckeditor default config
 *
 * @author Craig Hoover
 */
$richtextConfig = array(
	'height' => 150,
	'width' => 550,
	'toolbar' => array(array('Styles', 'Format'), array('Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Link'))
);


/**
 * scaffolding config
 *
 * change these if you want to establish specific permission settings for users or hide/show certain fields for each table
 *
 * configurable items
 * =================================
 *
 * submitRoute (string) this is the path that all admin functions are applied from - only change if your admin route is different
 *
 * allowedActions (array(array)) these are the actions allowed for admins to administer
 *		view - view all tables and all records
 *		new - create new records for table
 *		edit - edit all records for table
 *		copy - copy record for table
 *		remove - remove record from table
 * 	export - export record or table record
 *
 *		example:
 *			'allowedAactions' => array('tablename' => array('view','edit')
 *
 * columnsForView (array(array)) designates which columns will be displayed per table for VIEW
 *
 *		example:
 *			'columnsForView' => array('tablename' => array('title','body','active','created_on'))
 *
 * columnsForEdit (array(array)) designates which columns will be displayed per table for EDIT
 *
 *		example:
 *			'columnsForEdit' => array('tablename' => array('title','body','active'))
 *
 * richtextEditors (array(array)) desiginates which columns will have the CKEditor applied per table
 *
 * 	example:
 *			'richtextEditors' => array('tablename' => array('body' => $richtextOptions)
 *
 * NOTE: richtextConfig is not required.  you may just pass a valid column name as an array value	
 *
 * @author Craig Hoover
 */

$config = array(
	'submitRoute' => '/admin/',
	'ckEditorProcessor' => DOCROOT.'/ckeditor/ckeditor_php5.php',
	'resultsPerPage' => 20,
	'allowedActions' => array(
		'articles' => array('view','new','edit','copy','remove','export','empty'),
		'categories' => array('view','new','edit','copy','remove','export'),
		'language_codes' => array('view','new','edit','remove'),
	),
	'columnsForView' => array(
		'articles' => array('title' => 'Article Title','language','active','created_on','updated_on'),
		'categories' => array('name','parent_id' => 'Parent Category', 'created_on'),
		'language_codes' => array('code' => 'Language Code','name')
	),
	'columnsForEdit' => array(
		'articles' => array(	'title' => 'Enter the title here','language','body','active' => 'Display on site?','created_on'),
		'categories' => array('name','parent_id' => 'Parent Category', 'created_on'),
		'language_codes' => array('code' => 'Language Code','name')
	),
	'hasLookupColumn' => array(
		'categories' => array(
			'parent_id' => "SELECT id,name AS 'Parent Category' FROM `categories` ORDER BY name"
		),
		'articles' => array(
			'language' => "SELECT code,name AS 'Language Code' FROM `language_codes` ORDER BY name"
		)
	),
	'richtextEditors' => array(
		'articles' => array('body' => $richtextConfig)
	)
);

generateScaffold($config);

?>