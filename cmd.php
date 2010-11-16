#!/usr/bin/php
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

// bootstrapping
require_once('bootstrap.php');

// constants
const QUIT_SIGNAL = 'quit';
const HELP_SIGNAL = 'help';
const HELP_TEXT = "Command list:
	clusters - lists the available clusters
	servers - lists the servers available on the selected cluster
	cluster {n} - select cluster {n}
	server {n} - select server {n}
	selected - prints the selected cluster and server
	list - lists all the queues in the selected server or cluster
	flushall - flushes the currently selected server or cluster
	flush {n} - flushes the queue {n} on the selected server or cluster
	deleteall - deletes the queues on the currently selected server or cluster
	delete {n} - deletes the given queue on the currently selected server or cluster
	get {n} - gets an item from queue {n} on the selected server or cluster
	peek {n} - peeks an item from queue {n} on the selected server or cluster
	stats {n} - shows statistics for queue {n}
	add {n} - adds an item to the queue {n}
	fanoutflush {n} - flushes the fanout queues of queue {n} on the selected server or cluster
	fanoutdelete {n} - deletes the fanout queues of queue {n} on the selected server or cluster";

$methodlookup = array(
	'clusters'=>'listclusters',
	'servers'=>'showcluster',
	'list'=>'showqueues',
	'flushall' => 'flushall',
	'deleteall' => 'deleteall',
	'flush'=>'flushqueue',
	'delete'=>'deletequeue',
	'get'=>'getqueue',
	'peek'=>'peekqueue',
	'stats'=>'showqueue',
	'add'=>'addqueue',
	'fanoutflush'=>'flushfanoutqueues',
	'fanoutdelete'=>'deletefanoutqueues',
);

$paramslookup = array(
	'flush'=>array(0=>'key'),
	'delete'=>array(0=>'key'),
	'get'=>array(0=>'key'),
	'peek'=>array(0=>'key'),
	'stats'=>array(0=>'key'),
	'add'=>array(0=>'key',1=>'data'),
	'fanoutflush'=>array(0=>'key'),
	'fanoutdelete'=>array(0=>'key'),
);

// startup
echo " _        _______  _______  _______ _________ _       
| \    /\(  ____ \(  ____ \(       )\__   __/( (    /|
|  \  / /| (    \/| (    \/| () () |   ) (   |  \  ( |
|  (_/ / | (__    | (_____ | || || |   | |   |   \ | |
|   _ (  |  __)   (_____  )| |(_)| |   | |   | (\ \) |
|  ( \ \ | (            ) || |   | |   | |   | | \   |
|  /  \ \| (____/\/\____) || )   ( |___) (___| )  \  |
|_/    \/(_______/\_______)|/     \|\_______/|/    )_)\n";
echo "\nShiny interface for statistics in the Kestrel distributed message queue\n\nCopyright 2010 Chris Alexander\nLicensed under the MIT license\nhttp://code.google.com/p/kesmin\n\n";

$kesmin = new Kesmin();
$cluster = null;
$server = null;

// execution loop
while(true) {
	echo "> ";

	// prepare the command and arguments
	$commandstring = trim(fgets(STDIN));
	$cmds = explode(' ', $commandstring);
	$command = trim(array_shift($cmds));
	$args = array();
	foreach ($cmds as $k=>$arg) {
		$arg = trim($arg);
		if (strlen($arg) > 0) {
			$args[] = $arg;
		}
	}

	if ($command == QUIT_SIGNAL) {
		break;
	}

	$params = array();
	if (!is_null($cluster)) {
		$params['cluster'] = $cluster;
	}
	if (!is_null($server)) {
		$params['server'] = $server;
	}

	$result = false;
	switch($command) {
		case HELP_SIGNAL:
			echo HELP_TEXT;
			break;
		case "cluster":
			if (isset($args[0])) {
				$cluster = $args[0];
			} else {
				$cluster = null;
			}
			echo 'OK';
			break;
		case "server":
			if (isset($args[0])) {
				$server = $args[0];
			} else {
				$server = null;
			}
			echo 'OK';
			break;
		case "selected":
			echo "Cluster:\t[";
			if (!is_null($cluster)) {
				echo $cluster;
			}
			echo "]\nServer:\t\t[";
			if (!is_null($server)) {
				echo $server;
			}
			echo "]";
			break;
		default:
			if ((isset($paramslookup[$command])) && (is_array($paramslookup[$command]))) {
				foreach($paramslookup[$command] as $input=>$output) {
					if (isset($args[$input])) {
						$params[$output] = $args[$input];
					}
				}
			}
			if (isset($methodlookup[$command])) {
				$method = $methodlookup[$command];
			} else {
				$method = $command;
			}
			try {
				$result = $kesmin->callmethod($method, $params);
			} catch (Exception $e) {
				echo 'Exception: ' . $e->getMessage();
			}
			break;
	}

	if ($result) {
		if (is_array($result)) {
			$results = array();
			foreach ($result as $res) {
				$results[] =  $res->toText();
			}
			echo implode("\n", $results);
		} else {
			echo $res->toText();
		}
	}

	echo "\n";
}

echo "Bye!\n";
