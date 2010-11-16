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
* Holds the various actions for performing functions with Kesmin
*/
class Kesmin
{
	/**
	* Holds the location of the configuration file
	*
	* @var string
	*/
	protected $config = null;

	/**
	* Constructs
	*	
	* @param string $config the location of the configuration file
	* @return Kesmin	
	*/
	public function __construct($config = null)
	{
		$this->config = null;
	}

	/**
	* Calls one of the action methods, passing the parameters
	*
	* @param string $method the name of the method to call
	* @param array $params the parameters to pass
	* @return the result from the method call
	*/
	public function callMethod($method, $params = array())
	{
		$methodName = strtolower($method) . 'Action';
		if (!is_callable(array($this, $methodName))) {
			throw new Exception('Unknown method ' . $method . ', missing method ' . $methodName);
		}
		return $this->$methodName($params);
	}
	
	/**
	* The default action
	*/
	public function indexAction($params)
	{
		return $this->listclustersAction($params);
	}

	/**
	* Lists all of the configured clusters
	*/
	public function listclustersAction($params)
	{
		$config = $this->loadConfig();	
		$clusters = $config->getClusters();
		return $clusters;
	}

	/**
	* Displays information on a particular cluster
	*/
	public function showclusterAction($params)
	{
		$this->verifyRequiredParams($params, array('cluster'));
		$config = $this->loadConfig();
		$clusters = $config->getClusters();
		if (!isset($clusters[$params['cluster']])) {
			throw new Exception('Cluster \'' . $params['cluster'] . '\' does not exist');
		}
		return array($clusters[$params['cluster']]);
	}

	/**
	* Shows queues for the given cluster or nodes
	*/
	public function showqueuesAction($params)
	{
		if ((!isset($params['cluster']) && (!isset($params['server'])))) {
			throw new Exception('Missing expected cluster or server');
		}
		$config = $this->loadConfig();
		$clusters = $config->getClusters();
		if (!isset($params['server'])) {
			// just the cluster
			if (!isset($clusters[$params['cluster']])) {
				throw new Exception('Cluster \'' . $params['cluster'] . '\' does not exist');
			}
			return array($clusters[$params['cluster']]->stats());
		} else {
			// cluster and server
			if (!isset($clusters[$params['cluster']])) {
				throw new Exception('Cluster \'' . $params['cluster'] . '\' does not exist');
			}
			return array($clusters[$params['cluster']]->getServer($params['server'])->stats());
		}
	}

	/**
	* Shows data about a particular server in a cluster
	*/
	public function showserverAction($params)
	{
		$this->verifyRequiredParams($params, array('cluster','server'));
		return array($this->loadConfig()->getCluster($params['cluster'])->getServer($params['server']));
	}

	/**
	* Shows data on a particular queue on a cluster / server
	*/
	public function showqueueAction($params)
	{
		$this->verifyRequiredParams($params, array('cluster','key'));
		if (isset($params['server'])) {
			return array($this->loadConfig()->getCluster($params['cluster'])->getServer($params['server'])->stats()->getKeyObject($params['key']));
		} else {
			return array($this->loadConfig()->getCluster($params['cluster'])->stats()->getKeyObject($params['key']));
		}
	}

	/**
	* Gets an item from a queue
	*/
	public function getqueueAction($params)
	{
		$this->verifyRequiredParams($params, array('cluster','key'));
		if (isset($params['server'])) {
			$response = $this->loadConfig()->getCluster($params['cluster'])->getServer($params['server'])->get(str_replace('___','+',$params['key']));
		} else {
			$response = $this->loadConfig()->getCluster($params['cluster'])->get(str_replace('___','+',$params['key']));
		}
		return array(new Kesmin_Response('queue get', $params, $response));
	}

	/**
	* Peeks an item in the queue
	*/
	public function peekqueueAction($params)
	{
		$this->verifyRequiredParams($params, array('cluster','key'));
		if (isset($params['server'])) {
			$response = $this->loadConfig()->getCluster($params['cluster'])->getServer($params['server'])->peek(str_replace('___','+',$params['key']));
		} else {
			$response = $this->loadConfig()->getCluster($params['cluster'])->peek(str_replace('___','+',$params['key']));
		}
		return array(new Kesmin_Response('queue peek', $params, $response));

	}

	/**
	* Adds an item to the queue
	*/
	public function addqueueAction($params)
	{
		$this->verifyRequiredParams($params, array('cluster','key','data'));
		$cluster = $this->loadConfig()->getCluster($params['cluster']);
		if (!isset($params['server']) || $params['server'] == '') {
			$server = $cluster->getRandomServer();
		} else {
			$server = $cluster->getServer($params['server']);
		}
		$response = $server->push($params['key'], $params['data']);
		return array(new Kesmin_Response('add to queue', $params, $response));
	}

	/**
	* Deletes a queue
	*/
	public function deletequeueAction($params)
	{
		$this->verifyRequiredParams($params, array('cluster','key'));
		if (isset($params['server'])) {
			$response = $this->loadConfig()->getCluster($params['cluster'])->getServer($params['server'])->delete(str_replace('___','+',$params['key']));
		} else {
			$response = $this->loadConfig()->getCluster($params['cluster'])->delete(str_replace('___','+',$params['key']));
		}
		return array(new Kesmin_Response('delete queue', $params, $response));
	}

	/**
	* Empties a queue
	*/
	public function flushqueueAction($params)
	{
		$this->verifyRequiredParams($params, array('cluster','key'));
		if (isset($params['server'])) {
			$response = $this->loadConfig()->getCluster($params['cluster'])->getServer($params['server'])->flush(str_replace('___','+',$params['key']));
		} else {
			$response = $this->loadConfig()->getCluster($params['cluster'])->flush(str_replace('___','+',$params['key']));
		}
		return array(new Kesmin_Response('flush queue', $params, $response));

	}

	/**
	* Empties all the queues on this server
	*/
	public function flushallAction($params)
	{
		$this->verifyRequiredParams($params, array('cluster'));
		if (isset($params['server'])) {
			$response = $this->loadConfig()->getCluster($params['cluster'])->getServer($params['server'])->flush();
		} else {
			$response = $this->loadConfig()->getCluster($params['cluster'])->flush();
		}
		return array(new Kesmin_Response('flush all queues', $params, $response));
	}

	/**
	* Deletes all the queues on this server
	*/
	public function deleteallAction($params)
	{
		$this->verifyRequiredParams($params, array('cluster'));
		if (isset($params['server'])) {
			$response = $this->loadConfig()->getCluster($params['cluster'])->getServer($params['server'])->deleteall();
		} else {
			$response = $this->loadConfig()->getCluster($params['cluster'])->deleteall();
		}
		return array(new Kesmin_Response('delete all queues', $params, $response));
	}

	/**
	* Flushes all the fanout queues of a given queue
	*/
	public function flushfanoutqueuesAction($params)
	{
		$this->verifyRequiredParams($params, array('cluster','server','key'));	
		$response = $this->loadConfig()->getCluster($params['cluster'])->getServer($params['server'])->flushfanouts($params['key']);
		return array(new Kesmin_Response('flush all queues', $params, $response));
	}

	/**
	* Deletes all the fanout queues of a given queue
	*/
	public function deletefanoutqueuesAction($params)
	{
		$this->verifyRequiredParams($params, array('cluster','server','key'));	
		$response = $this->loadConfig()->getCluster($params['cluster'])->getServer($params['server'])->deletefanouts($params['key']);
		return array(new Kesmin_Response('flush all queues', $params, $response));
	}

	/**
	* Verifies that a set of parameters are in the params array
	*
	* @param array $params the parameters array
	* @param array $required an array of names that must exist as keys in the $params array
	*/
	protected function verifyRequiredParams($params, $required)
	{
		foreach ($required as $key) {
			if (!isset($params[$key])) {
				throw new Exception('You must specify \'' . $key . '\'');
			}
		}
	}

	/*
	* Helper to load and return the configuration
	*
	* @return Configuration
	*/
	protected function loadConfig()
	{
		if (is_null($this->config)) {
			// load the default
			return new Configuration();
		} else {
			return new Configuration($this->config);
		}
	}
}
