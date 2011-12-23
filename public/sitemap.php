<?php
/**
 * this is just a boilerplate php file to use if you want to
 * generate a sitemap on-the-fly
 *
 * @author Craig Hoover
 */
header('Content-type:text/xml');

// clear our layout
$app->layout = '';

// create a new domdocument for our XML sitemap
$dom = new DomDocument();
$root = $dom->appendChild($dom->createElement('urlset'));
$root->setAttribute('xmlns','http://www.sitemaps.org/schemas/sitemap/0.9');
$root->setAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
$root->setAttribute('xsi:schemaLocation','http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');

// append sitemap url's
$url = $dom->createElement('url');
$loc = $url->appendChild($dom->createElement('loc'));
$mod = $url->appendChild($dom->createElement('lastmod'));

$root->appendChild($url);

// send output
echo $dom->saveXML();

?>