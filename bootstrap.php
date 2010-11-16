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

/**
* Bootstraps the files required for kesmin operation
*/

$files = array(
	'lib/Kesmin/Output.php',
	'lib/Kesmin/Response.php',
	'lib/Kestrel/Exception.php',
	'lib/Kestrel/Server.php',
	'lib/Kestrel/Stats.php',
	'lib/Kestrel/StatsKey.php',
	'lib/Kestrel/Cluster.php',
	'lib/Kestrel/ClusterStats.php',
	'lib/Configuration.php',
	'lib/Kesmin.php',
);

foreach ($files as $file) {
	require_once($file);
}
