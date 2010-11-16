<?php
/**
* Kesmin
*
* Shiny interface for statistics in the Kestrel distributed message queue
*
* Copyright 2010 Chris Alexander
* Licensed under the MIT License
*
* http://code.google.com/p/kesmin
*/

require_once('bootstrap.php');

// grab the method and parameters
if (isset($_REQUEST['method'])) {
	$method = $_REQUEST['method'];
	unset($_REQUEST['method']);
} else {
	$method = 'index';
}
$params = $_REQUEST;

$output = array();
$seperator = '';
$prefix = '<article>';
$suffix = '</article>';

try {
	$kesmin = new Kesmin();
	$d = $kesmin->callMethod($method, $params);

	foreach ($d as $item) {
		if (is_callable(array($item, 'toHTML'))) {
			$output[] = $prefix . $item->toHTML() . $suffix;
		}
	}
} catch (Kestrel_Exception $e) {
	$output[] = $prefix . $e->toHTML() . $suffix;
}

echo '<html><head><title>Kesmin</title><style>' . file_get_contents('index.css')  . '</style></head><body><section><h1>Kesmin</h1>' . implode($seperator, $output) . '<footer>Kesmin is an <a href="http://code.google.com/p/kesmin">open-source project</a></footer></section></body></html>';
