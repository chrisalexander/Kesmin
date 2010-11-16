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
* Represents a cluster of Kestrel servers
*/
class Kestrel_Cluster extends Kesmin_Output
{
	/**
	* The name of the cluster
	*
	* @var string
	*/
	protected $name;

	/**
	* An array of servers in this cluster
	*
	* @var array
	*/
	protected $servers = array();

	/**
	* Constucts a cluster of nodes
	*
	* @param string $name
	*/
	public function __construct($name)
	{
		$this->name = $name;
	}

	/**
	* Adds a server into this cluster
	*
	* @param Kestrel_Server $server the server to add
	* @return bool
	*/
	public function addServer($server)
	{
		if (!($server instanceof Kestrel_Server)) {
			throw new Exception('Unable to add server that is not a Kestrel_Server');
		}

		if (!isset($this->servers[$server->getName()])) {
			$this->servers[$server->getName()] = $server;
			return true;
		} else {
			return false;
		}
	}

	/**
	* Removes a server from the pool by name
	*
	* @param string $serverName
	* @return bool
	*/
	public function removeServer($serverName)
	{
		if (!isset($this->servers[$serverName])) {
			return false;
		} else {
			unset($this->servers[$serverName]);
			return true;
		}	
	}

	/**
	* Lists all the servers in the cluster
	*
	* @return array
	*/
	public function listServers()
	{
		return array_keys($this->servers);
	}

	/**
	* Retusna a server by name
	*
	* @param string $name the name of the server
	* @return Kestrel_Server
	*/
	public function getServer($name = 'default')
	{
		$name = trim($name);
		if (!isset($this->servers[$name])) {
			throw new Exception('No server named \'' . $name . '\' exists in this cluster');
		}
		return $this->servers[$name];
	}

	/**
	* Returns a single item from the given queue for each of the servers
	*
	* @param string $queue the queue name
	* @param int $wait the number of milliseconds to lock and wait for an item
	* @return array
	*/
	public function get($queue, $wait = 0)
	{	
		$this->checkServers();

		$results = array();
		foreach ($this->servers as $name=>$server) {
			try {
				$results[$name] = $server->get($queue, $wait);
			} catch (Exception $e) {
				$results[$name] = $e;
			}
		}
		return $results;
	}

	/**
	* Peeks a single item from the given queue for each of the servers
	*
	* @param string $queue the queue name
	* @return array
	*/
	public function peek($queue)
	{	
		$this->checkServers();

		$results = array();
		foreach ($this->servers as $name=>$server) {
			try {
				$results[$name] = $server->peek($queue);
			} catch (Exception $e) {
				$results[$name] = $e;
			}
		}
		return $results;
	}

	/**
	* Flushes the queue with the given name on all of the servers in this cluster
	*
	* @param string $queue the queue name
	* @return array
	*/
	public function flush($queue = null)
	{	
		if (is_null($queue)) {
			return $this->flushall();
		}

		$this->checkServers();

		$results = array();
		foreach ($this->servers as $name=>$server) {
			try {
				$results[$name] = $server->flush($queue);
			} catch (Exception $e) {
				$results[$name] = $e;
			}
		}
		return $results;
	}

	/**
	* Delete a key from all servers
	*
	* @param string $queue the queue to delete
	* @return array
	*/
	public function delete($queue)
	{
		$this->checkServers();

		$results = array();
		foreach ($this->servers as $name=>$server) {
			try {
				$results[$name] = $server->delete($queue);
			} catch (Exception $e) {
				$results[$name] = $e;
			}
		}
		return $results;
	}

	/**
	* Flushes all the queues on all the servers
	*
	* @return array
	*/
	public function flushall()
	{
		$this->checkServers();

		$results = array();
		foreach ($this->servers as $name=>$server) {
			try {
				$results[$name] = $server->flush();
			} catch (Exception $e) {
				$results[$name] = $e;
			}
		}
		return $results;
	}

	/**
	* Deletes all the queues on all the servers
	*
	* @return array
	*/
	public function deleteall()
	{
		$this->checkServers();

		$results = array();
		foreach ($this->servers as $name=>$server) {
			try {
				$results[$name] = $server->deleteall();
			} catch (Exception $e) {
				$results[$name] = $e;
			}
		}
		return $results;
	}


	/**
	* Get stats from each of the servers
	*
	* @return array
	*/
	public function stats()
	{
		$this->checkServers();

		$results = new Kestrel_ClusterStats($this->name);
		foreach ($this->servers as $name=>$server) {
			try {
				$results->addServer($name, $server->stats());
			} catch (Exception $e) {}
		}
		return $results;
	}
	
	/**
	* Returns a random server from this cluster
	*
	* @return Kestrel_Server
	*/
	public function getRandomServer()
	{
		return $this->servers[array_rand($this->servers)];
	}

	/**
	* Method that outputs this class as HTML
	*
	* @return string
	*/
	public function toHTML()
	{
		$output = '<h2>Cluster: ' . $this->name . '</h2>';
		$output .= '<h3>Servers</h3><ul>';
		foreach ($this->servers as $server) {
			$output .= '<li><a href="?method=showserver&cluster=' . $this->name . '&server=' . $server->getName() . '">' . $server->getName() . '</a></li>';
		}
		$output .= '</ul>';
		$output .= '<h3>Queues</h3><a href="?method=showqueues&cluster=' . $this->name . '">List queues for this cluster</a><br />';
		$output .= '<a href="?method=showcluster&cluster=' . $this->name . '">More about \'' . $this->name . '\' cluster</a><br />';
		return $output;
	}

	/**
	* Method that outputs this class to text
	*
	* @return string
	*/
	public function toText()
	{
		$output = $this->name . "\n";
		foreach ($this->servers as $server) {
			$output .= "\t" . $server->getName() . "\n";
		}
		return $output;
	}

	/**
	* Helper to check that there are servers configured before performing an operation
	*
	*/
	protected function checkServers()
	{
		if (count($this->servers) == 0) {
			throw new Exception('There are no servers configured in the cluster ' . $this->name);
		}
	}
}
