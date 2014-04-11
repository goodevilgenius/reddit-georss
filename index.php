<?php

$location_regexp = "/\[([^]]+)\]/";
$title_regexp    = "/\(([^)]+)\)/";

if (!empty($_SERVER["PATH_INFO"])) $sub = trim($_SERVER["PATH_INFO"],"/");
elseif (!empty($_GET['sub'])) $sub = $_GET['sub'];

if (empty($sub)) {
		header("HTTP/1.1 400 Bad Request");
		exit;
}

$rss = "http://www.reddit.com/r/$sub.rss";
$rss_cont = file_get_contents($rss);
if (empty($rss_cont)) {
		header("HTTP/1.1 502 No response from Reddit");
		exit;
}

$sx = new simplexmlelement($rss_cont);
if (empty($sx)) {
		header("HTTP/1.1 500 Internal Server Error");
		exit;
}

// Adding namespaces with SimpleXML doesn't work, so we use DOM for that
$doc = dom_import_simplexml($sx);
$doc->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:georss", "http://www.georss.org/georss");
if (empty($sx->getDocNamespaces()['dc'])) $doc->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:dc", "http://purl.org/dc/elements/1.1/");

$ns = $sx->getDocNamespaces();

foreach($sx->channel->item as $i) {

		$t = (string)($i->title);
		if (preg_match($location_regexp, $t, $m)) {
				// Add code to geolocate from address
				$lat = 0;
				$long = 0;

				$i->addChild("point", "$lat $long", $ns['georss']);
		} else {
				$d = dom_import_simplexml($i);
				$d->parentNode->removeChild($d);
		}

}

header("Content-type: application/rss+xml");
echo $sx->saveXML();
