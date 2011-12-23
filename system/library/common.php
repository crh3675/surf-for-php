<?php

/**
 * set global identifiers for application instance.
 * we are safely using globals in this fashion as the instance is uniquely generated
 *
 * @author Craig Hoover
 */

$GLOBALS['SURFAPP'] = 'SURF_'.md5(uniqid());
$GLOBALS[$GLOBALS['SURFAPP']] = new stdClass();

/**
 * set an application variable
 *
 * @param string $key 
 * @param string $value 
 * @return mixed value of var
 * @author Craig Hoover
 */
function setAppVar($key, $value)
{
	$GLOBALS[$GLOBALS['SURFAPP']]->$key = $value;
	return $value;
}

/**
 * returns value of application variable 
 *
 * @param string $key 
 * @return mixed application variable
 * @author Craig Hoover
 */
function getAppVar($key)
{
	if(appVarIsset($key))
	{
		return $GLOBALS[$GLOBALS['SURFAPP']]->$key;
	}
	else
	{
		return null;
	}		
}

/**
 * test to see if application variable is set
 *
 * @param string $key 
 * @return boolean
 * @author Craig Hoover
 */
function appVarIsset($key)
{
	return property_exists($GLOBALS[$GLOBALS['SURFAPP']], $key);
}

/**
 * start application, initialize global var
 *
 * @return void
 * @author Craig Hoover
 */
function startApp($vars = array())
{
	// set a starting process time
	setAppVar('started', microtime(true));
	
	loadLibraryFuncs();
	
	// create some required vars
	$required = array(
		'i18n_accept' => 'both',
		'i18n_use' => 'short',
		'charset' => 'utf-8',
		'default_layout' => 'default',
		'default_route' => '/',
		'content' => '',
		'error_404' => '/errors/404'
	);
	
	// merge our required with input vars
	$vars = array_merge($required, $vars);

	// apply settings passed into application
	foreach($vars as $key => $value)
	{
		setAppVar($key, $value);
	}
	
	// if i18n support is being requested, preload lang config
	// this also preprocesss the routes
	if(getAppVar('i18n'))
	{
		loadLangs();
	}
	
	if($db = getAppVar('database') && !isset($db['name']))
	{
		connectToDb($vars['charset']);
	}
	
	// grab our basic route info
	$uri = getURIComponents();

	// whoa! routes must be valid, setup in config/routes.php
	if(isRouteValid($uri->route))
	{
		$result = processRoute($uri);
		echo $result->output;
	}
	else
	{
		// 404 on invalid routes
		header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
		$_SERVER['REDIRECT_STATUS'] = 404;	
		
		if(appVarIsset('error_404'))
		{
			header('Location:'.getAppVar('error_404').'/?'.$_SERVER['REDIRECT_URL']);
			exit(0);
		}
		else
		{
			die('<h1>Oops! 404 Not Found</h1>The document or resource you are trying to access does not exist at the specified location.');
		}
	}
}

/**
 * unsets global reference to application
 *
 * @return void
 * @author Craig Hoover
 */
function endApp()
{
	unset($GLOBALS[$GLOBALS['SURFAPP']]);
}

/**
 * extracts URI components from browser location bar
 *
 * @return object
 * @author Craig Hoover
 */
function getURIComponents()
{
	$result = new stdClass();
	$request = preg_replace('/\/$/', '', str_replace('?'.$_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI'])); 
	$segments = preg_split('/\//', trim($request));
	$lang  = 'en';

	array_shift($segments); // drop our first match, it's blank
	
	if(getAppVar('i18n') == true)
	{
		if(isset($segments[0]) && in_array($segments[0], getAppVar('langs')))
		{
			$lang = array_shift($segments); // extract/drop our new first match, it's our language
			$lang = array_search($lang, getAppVar('langs'), false); // get long version
		}
		else
		{
			// no language made it into our querystring se let's assume the default
			$lang = getAppVar('default_lang');
		}		
	} 
	
	setAppVar('current_lang', $lang);
	
	// some helpful info to send to our page
	$result->route = $request == '' ? '/' : $request;
	$result->segments = $segments;
	$result->query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
	$result->lang = $lang;
	return $result;
}

/**
 * tests if route is in acceptable routes list
 *
 * @param string $route 
 * @param string $routes 
 * @return void
 * @author Craig Hoover
 */
function isRouteValid($route)
{
	$routes = loadConfig('routes');		
	
	if(preg_match("/^\/errors?/i", $route))
	{
		return true;
	}
	else
	{
		if(in_array($route, $routes) || array_key_exists($route, $routes))
		{
			return true;
		}
		else
		{			
			foreach($routes as $key => $value)
			{
				if(strstr($key,'*'))
				{					
					$expr = '^'.str_replace('\*','\w+', preg_quote($key,'/')).'$';

					if(preg_match('/'.$expr.'/', $route, $matches)){						
						return true;
					}
				}
			}			
		}		
	}
}


/**
 * process all commands and functions for outputting route
 *
 * @param string $route 
 * @param string $lang 
 * @return void
 * @author Craig Hoover
 */
function processRoute($uri)
{	
	$route= $uri->route;
	$lang = $uri->lang;
	$segments = $uri->segments;
	$routes = loadConfig('routes');
	$useroute = $route;
	
	$app = new stdClass();
	$app->content = '';
	$app->lang  = $lang;

	foreach($GLOBALS[$GLOBALS['SURFAPP']] as $key => $value)
	{
		extractToObject($app, $key, $value);
	}
	
	// normalize routes that are not key => value pairs
	foreach($routes as $key => $path)
	{	
		if(strstr($key,'*'))
		{
			$expr = '^'.str_replace('\*','(\w+)', preg_quote($key,'/')).'$';
			
			if(preg_match('/'.$expr.'/',$useroute, $matches))
			{
				array_shift($matches);
				$useroute = vsprintf($path, $matches);
				break;
			}
		}
		else
		{				
			if(!is_numeric($key) && $route == $key){
				$useroute = $path;
				break;
			}
		}
	}
	
	// our route is asking for the default, so get it
	if($useroute == '/')
	{
		$useroute = getAppVar('default_route');
	}

	// catch empty routes for default index.surf.php
	if(preg_match("/\/$/",$useroute))
	{
		$useroute .= "index";
	}
		
	if(file_exists(DOCROOT."$useroute.surf.php"))
	{		
		// retrieve i18n dictionary.  can be accessed within your pages
		// as properties: $i18n->varname
		if(getAppVar('i18n'))
		{
			$i18n = new stdClass();
			$i18n->lang = $lang;
			$dict = getAppVar('dictionary');
			
			$app->lang = $lang;
			
			// vars can be nested if the lang/[lang].php file has
			// nested arrays:
			// $i18n->registration->title
			//
			
			foreach($dict[$lang] as $key => $value)
			{
				extractToObject($i18n, $key, $value);				
			}
		}
		
		$app->self = $_SERVER['REQUEST_URI'];
		$app->base = str_replace('?'.$_SERVER['QUERY_STRING'],'', $app->self);
		
		// lets process our requested route now
		// routes expect the final file to have a .php extension
		// i.e. /company/about would go to /routes/company/about.php
		ob_start();
		include(DOCROOT."$useroute.surf.php");
		$app->content = ob_get_clean();
		
		// determine which layout to use
		$_layout = property_exists($app, 'layout') ? $app->layout : getAppVar('default_layout');

		// add some helpful info into the $app scope
		$app->processtime = microtime(true) - getAppVar('started');
		$app->route = $useroute;

		if(!preg_match("/none|off|false|no/i", $_layout) && !empty($_layout))
		{
			// now apply to our layout
			ob_start();
			include(SYSTEMPATH."/layouts/$_layout.php");
			$app->content = ob_get_clean();
		}
		
		// set some helpful info here to be sent back
		$result = new stdClass();
		$result->output = $app->content;
		
		return $result;		
	}
	else
	{
		trigger_error("[routes$useroute.surf.php] is a valid configured route but the view file [public$useroute.surf.php] does not exist.", E_USER_ERROR);
	}
}



/**
 * loads variable from include file and returns result
 *
 * @param string $var 
 * @return mixed
 * @author Craig Hoover
 */
function loadConfig($var)
{
	if(file_exists(SYSTEMPATH."/config/$var.php"))
	{
		if(!appVarIsset($var))
		{
			return setAppVar($var, require(SYSTEMPATH."/config/$var.php"));
		}
		else
		{
			return getAppVar($var);
		}
	}else
	{
		trigger_error("[$var.php] config file not found", E_USER_ERROR);
	}
}


/**
 * load languages configuration
 *
 * @return void
 * @author Craig Hoover
 */
function loadLangs()
{
	if(file_exists(realpath(SYSTEMPATH.'/config/lang.php')))
	{
		$lang_config = loadConfig('lang');
		$routes = loadConfig('routes');				
		
		if(!isset($lang_config['default'])) trigger_error("no default language provided in [system/config/lang.php]");
		if(!isset($lang_config['all'])) trigger_error("no languages provided in [system/config/lang.php]");
		
		setAppVar('default_lang', $lang_config['default']);
		setAppVar('langs', $lang_config['all']);
		setAppVar('i18n', true);
		
		$langs = array();
		
		foreach($lang_config['all'] as $lang => $short)
		{
			if(file_exists(SYSTEMPATH."/lang/$lang.php"))
			{
				$langs[$lang] = require(SYSTEMPATH."/lang/$lang.php");
			}
			else
			{
				trigger_error("[$lang] cannot be found.  create a file under [system/lang/$lang.php] or remove from [system/config/lang.php]", E_USER_WARNING);
			}
		}
		
		setAppVar('dictionary', $langs);
		
		$lang_routes = array();
				
		foreach($routes as $key => $route)
		{	
			if(is_numeric($key))
			{
				$key = $route;				
			}

			$lang_routes[$key] = $route;
			$accept = getAppVar('i18n_accept');  
			
			foreach($lang_config['all'] as $lang => $short)
			{
				if(preg_match("/short|both/i", $accept))
				{
					$new_route = preg_replace("/\/$/","","/$short$key");				
					$lang_routes[$new_route] = $route;
				}
				
				if(preg_match("/long|both/i", $accept))
				{				
					$new_route = preg_replace("/\/$/","","/$lang$key");				
					$lang_routes[$new_route] = $route;
				}
			}
		}
		
		setAppVar('routes', $lang_routes);
	}	

}

/**
 * applies language to output objects for display
 *
 * @param object by reference 
 * @param string $key 
 * @param mixed $value
 * @return void
 * @author Craig Hoover
 */
function extractToObject(&$node, $key, $value)
{
	if(is_numeric($key)) $key = "index$key";
	
	if(is_array($value))
	{
		$node->$key = new stdClass();
		
		foreach($value as $key2 => $term2)
		{			
			extractToObject($node->$key,$key2, $term2);
		}
	}
	else
	{
		$node->$key = $value;
	}
}

/**
 * traverse library and load functions
 * 
 * @param none
 * @return void
 * @author Craig Hoover
 **/

function loadLibraryFuncs($defer = array())
{
	$path = SYSTEMPATH.'/library';
	$dir = opendir($path);
	while(false !== ($file = readdir($dir)))
	{
		if(preg_match('/\.php$/i',$file) && !in_array($file, $defer))
		{
			include_once(SYSTEMPATH.'/library/'.$file);
		}
	}
	setAppVar('functions_loaded', true);	
}

/**
 * execute a user function under the library/ folder
 *
 * @param string $name 
 * @return void
 * @author Craig Hoover
 */
function callLibraryFunc($name)
{
	if(!appVarIsset('functions_loaded'))
	{
		loadLibraryFuncs();
	}
	
	$args = array_splice(func_get_args(),0,1);
	
	if(function_exists($name))
	{
		return call_user_func_array($name, $args);
	}
	else
	{
		 trigger_error("[$name] cannot be executed as it doesn't appear to exist under any file within [system/library/*.php].  did you misspell the function?", E_USER_WARNING);
	}
}

function toArray($value)
{
	return is_array($value) ? $value : array($value);
}


function linkTo($page ='', $lang = '', $params = '')
{	
	$curlang = getAppVar('current_lang');
	$lang = empty($lang) ? $curlang : $lang;
	$page = empty($page) ? $app->self : $page;
	$params = empty($params)  ? array() : $params;
	
	$curlang = getAppVar('i18n_use') == 'short' ? substr($curlang, 0, 2) : $curlang;
	$newlang = getAppVar('i18n_use') == 'short' ? substr($lang, 0, 2) : $lang;
				
	if(preg_match("/^\/".$curlang."\/?/",$page))
	{
		$page = preg_replace("/^\/(".$curlang.")(.*)/", "/$newlang$2", $page);
	}
	else
	{
		$page = "/$newlang$page";
	}
	
	$query = '';
	
	if(count($params) > 0 )
	{
		while(list($key,$value) = each($params))
		{
			$query .= $query != '' ? '&' : '';
			$query .= sprintf('%s=%s', $key, urlencode($value));
		}
		
		if($query != '')
		{
			$page = sprintf('%s?%s', $page, $query);
		}
	}
	
	return $page;	
	
}

function setDependencies()
{
	foreach(func_get_args() as $func)
	{
		if(!function_exists($func))
		{
			trigger_error("[$func] is required by this library and could not be found",E_USER_ERROR);
		}
	}
	return;
}

function connectToDb($charset = 'utf8')
{
	$db = getAppVar('database');
	$conn = @mysql_connect($db['host'],$db['user'], $db['password']);

	if(!$conn) trigger_error('database connection failed.  please check your config under [system/config/database.php].', E_USER_ERROR);
	
	if(mysql_select_db($db['name'], $conn)) 
	{
		@mysql_set_charset($charset, $conn);
		setAppVar('conn', $conn);
		return true;
	}
	else
	{
		trigger_error('database does not exist.  please check your config under [system/config/database.php].', E_USER_ERROR);
	} 
	mysql_close($conn);
	return false;	
}

?>