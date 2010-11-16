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
* Represents a single Kestrel server
*/
class Kestrel_Server extends Kesmin_Output
{
	/**
	* The name of the server
	*/
	protected $name;

	/**
	* Holds the host and port for this connection
	* 
	* @var string
	* @var int
	*/
	protected $host;
	protected $port;
	
	/**
	* Holds the name of the cluster this server is in
	*
	* @var string
	*/
	protected $cluster;

	/**
	* Holds the resource that is connected to the Kestrel server
	*
	* @var resource
	*/
	protected $connection;

	/**
	* Constructs the object
	*
	* @return Kestrel_Server
	*/
	public function __construct($name, $host, $port, $cluster)
	{
		$this->name = $name;
		$this->host = $host;
		$this->port = $port;
		$this->cluster = $cluster;
		$this->configureInterface();
	}

	/**
	* Destroys the object and its connections
	*
	*/
	public function __destruct()
	{
		fclose($this->connection);
	}
	
	/**
	* Returns a single item from the given queue
	*
	* @param string $queue the queue name
	* @param int $wait the number of milliseconds to lock and wait for an item
	* @return string
	*/
	public function get($queue, $wait = 0)
	{
		try {
			$data = $this->executeCommand('GET ' . $queue . '/t=' . $wait);
			$data = explode("\n", $data);
			return $data[1];
		} catch (Exception $e) {
			throw new Kestrel_Exception($e->getMessage());
		}
	}

	/**
	* Peeks an item on the queue
	*
	* @param string $queue the queue
	* @return string
	*/
	public function peek($queue)
	{
		try {
			$data = $this->executeCommand('GET ' . $queue . '/peek');
			$data = explode("\n", $data);
			return $data[1];
		} catch (Exception $e) {
			throw new Kestrel_Exception($e->getMessage());
		}
		
	}

	/**
	* Returns the name of this node
	*
	* @return string
	*/
	public function getName()
	{
		return $this->name;
	}

	/**
	* Returns the name of the cluster this server is in
	*
	* @return string
	*/
	public function getCluster()
	{
		return $this->cluster;
	}

	/**
	* Pushes an item into the given queue
	*
	* @param string $queue the queue to insert the item into
	* @param string $data the data to input
	* @param int $expiration the expiration time in seconds
	* @return bool
	*/
	public function push($queue, $data, $expiration = 0)
	{
		try {
			$res = $this->executeCommand('SET ' . $queue . ' 0 ' . $expiration . ' ' . (strlen($data)) . "\n" . $data . "\n");
			return (trim($res) == 'STORED') ? 'OK' : 'Error';
		} catch (Exception $e) {
			throw new Kestrel_Exception($e->getMessage());
		}
	}

	/**
	* Delete a key
	*
	* @param string $queue the queue to delete
	* @return bool
	*/
	public function delete($queue)
	{
		try {
			$res = $this->executeCommand('DELETE ' . $queue);
			return (trim($res) == 'END') ? 'OK' : 'Error';
		} catch (Exception $e) {
			throw new Kestrel_Exception($e->getMessage());
		}
	}

	/**
	* flushes a queue, or if not provided, the whole server
	*
	* @param string $queue the queue name to flush if needed
	*/
	public function flush($queue = null)
	{
		if (is_null($queue)) {
			$res = $this->executeCommand('FLUSH');
			return (trim($res) == 'END') ? 'OK' : 'Error';
		} else {
			// need to send a custom command
			$res = $this->executeCommand('FLUSH ' . $queue);
			return (trim($res) == 'END') ? 'OK' : 'Error';
		}
	}

	/**
	* empties all the fanout queues for the given queue
	*/
	public function flushfanouts($queue)
	{
		$stats = $this->stats();
		$keys = $stats->getKeys();
		$target = $queue . '+';
		$target_length = strlen($target);
		$results = array('success'=>0, 'failures'=>0);
		foreach ($keys as $key) {
			if (substr($key, 0, $target_length) == $target) {
				$res = $this->flush($key);
				if ($res == 'OK') {
					$results['success']++;
				} else {
					$results['failures']++;
				}
			}
		}
		return $results;
	}
	
	/**
	* deletes all the fanout queues for the given queue
	*/
	public function deletefanouts($queue)
	{
		$stats = $this->stats();
		$keys = $stats->getKeys();
		$target = $queue . '+';
		$target_length = strlen($target);
		$results = array('success'=>0, 'failures'=>0);
		foreach ($keys as $key) {
			if (substr($key, 0, $target_length) == $target) {
				$res = $this->delete($key);
				if ($res) {
					$results['success']++;
				} else {
					$results['failures']++;
				}
			}
		}
		return $results;
	}

	/**
	* Gets the stats for this server
	*
	* @return Kestrel_Stats
	*/
	public function stats()
	{
		$result = $this->executeCommand('STATS');
		return new Kestrel_Stats($this->name, $this->cluster, $result);
	}

	/**
	* Deletes all the queues on the server
	*/
	public function deleteall()
	{
		$stats = $this->stats();
		$keys = $stats->getKeys();
		$result = array('success'=>0,'failed'=>0);
		foreach ($keys as $key) {
			try {
				$res = $this->delete($key);
				if ($res) {
					$result['success']++;
				} else {
					$result['failed']++;
				}
			} catch (Exception $e) {
				$result['failed']++;
			}
		}
		return $result;
	}

	/**
	* Gets the stats for the global server
	*
	* @return array
	*/
	public function serverStats()
	{
		try {
			$data = $this->executeCommand('STATS');
			if (strlen($data) > 0) {
				$elements = explode("\n", $data);
				krsort($elements);
				$stats = array();
				foreach ($elements as $element) {
					$element = trim($element);
					$row = explode(' ', $element);
					if (count($row) < 3) {
						continue;
					}
					if (substr($row[1], 0, 5) == 'queue') {
						continue;
					}
					$stats[$row[1]] = $row[2];
				}
				return $stats;
			} else {
				return array();
			}
		} catch (Exception $e) {
			throw new Kestrel_Exception($e->getMessage());
		}
	}

	/**
	* Outputs this class as HTML
	*
	* @return string
	*/
	public function toHTML()
	{
		$output = '<h2>Server: ' . $this->name . '</h2>';
		$output .= '<h3>Stats</h3>';
		$stats = $this->serverStats();
		if (is_array($stats) && (count($stats) > 0)) {
			$output .= '<ul>';
			foreach ($stats as $k=>$v) {
				$output .= '<li>' . $k . ': ' . $v . '</li>';
			}
			$output .= '</ul>';
		} else {
			$output .= '<p>No stats - are you sure Kestrel is running?</p>';
		}
		$output .= '<h3>Queues</h3><a href="?method=showqueues&cluster=' . $this->cluster . '&server=' . $this->name . '">View queues on this server</a><br />';
		$output .= '<h3>Actions</h3><ul>';
                $output .= '<li><a href="?method=flushall&cluster=' . $this->cluster . '&server=' . $this->name . '">Flush All Queues</a></li>';
                $output .= '<li><a href="?method=deleteall&cluster=' . $this->cluster . '&server=' . $this->name . '">Delete All Queues</a></li>';
                $output .= '</ul>';
		return $output;
	}

	/**
	* Returns the class in text format
	*
	* @return string
	*/
	public function toText()
	{
		$output = 'This server is called ' . $this->name;
		return $output;
	}

	/**
	* makes a request to the kestrel server
	*
	* @param string $command the command to send
	* @return string the result
	*/
	protected function executeCommand($command)
	{
		if (!$this->connection) {
			throw new Kestrel_Exception('Error ' . $errornumber . ' opening socket: ' . $errordesc);
		}

		$command = $command . "\n";
		fwrite($this->connection, $command);

		$result = '';
		while (true)
		{
			$res = fgets($this->connection);
			$result .= $res;
			if ((preg_match('/^END/', $res)) || (strlen($res) == 0)) {
				break;
			}
		}

		return $result;
	}

	/**
	* Configure the interface
	*
	*/
	protected function configureInterface()
	{
		$this->connection = @fsockopen($this->host, $this->port, $errornumber, $errordesc, 1);

		if (!$this->connection) {
			throw new Kestrel_Exception('Error ' . $errornumber . ' opening socket: ' . $errordesc);
		}
		stream_set_timeout($this->connection, 1);
	}
}
