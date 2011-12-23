<?php
/**
 * Define your application routes here
 * 
 * You can set a single path or a key => value pair
 * They key can be different than the value:
 * 
 *   '/about-us' => '/pages/about-us'
 * 
 * All routes must begin with a leading /. If no value
 * is specified for /, index is assumed.
 * 
 * You can add a wildcard for a segment by specifying *.
 * You can also apply all wildcard matches to the route using
 * printf syntax.
 *
 * '/about/*' => '/about/%1$s'
 * 
 * All routes must translate to a valid view file named with the following convention:
 * 
 * file.surf.php
 *
 * All custom wildcard route segments can be retrieved within your page using:
 * 
 * echo $uri->segments[0] // array of each segment
 * 
 * So, a route for "/news/*" where the URI accessed in the browser is "/news/current", would
 * return "current" when reading: $uri->segments[1]
 *
 * @author Craig Hoover
 */

return array(
	'/'				=> '/home',
	'/sitemap'		=> '/sitemap/',
	'/about'			=> '/about/',
	'/mobile'		=> '/mobile/',
	'/admin'			=> '/admin/',
	'/dog/*/*'		=> '/mobile/%1$s/%2$s'
);

?>