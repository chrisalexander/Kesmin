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
* Loads the configuration for kesmin from config.json
*/
class Configuration
{
	/**
	* Holds the configuration array
	*
	* @var array
	*/
	protected $configuration;

	/**
	* Loads the configuration, optionally from a different file
	* 
	* @param string $file the configuration file
	* @return Configuration
	*/
	public function __construct($file = 'config.json')
	{
		$this->configuration = json_decode(file_get_contents($file), true);
		if (!is_array($this->configuration)) {
			throw new Exception('Configuration file ' . $file . ' is not correctly formatted');
		}
	}

	/**
	* Returns the clusters of Kestrel servers as an array, as configured in the file
	*
	* @return array
	*/
	public function getClusters()
	{
		if (!isset($this->configuration['kestrel'])) {
			throw new Exception('The configuration file does not contain a \'kestrel\' element');
		}

		$clusters = array();

		foreach ($this->configuration['kestrel'] as $clusterName=>$clusterNodes) {
			$clusters[$clusterName] = new Kestrel_Cluster($clusterName);
			foreach ($clusterNodes as $nodeName=>$nodeConfig) {
				$clusters[$clusterName]->addServer(new Kestrel_Server($nodeName, $nodeConfig['host'], $nodeConfig['port'], $clusterName));
			}
		}

		return $clusters;
	}

	/**
	* Returns a cluster specified by name
	*
	* @param string $name the name of the cluster
	* @return Kestrel_Cluster
	*/
	public function getCluster($name = 'default')
	{
		$name = trim($name);
		$clusters = $this->getClusters();
		if (!isset($clusters[$name])) {
			throw new Exception('No cluster with name \'' . $name . '\'');
		}
		return $clusters[$name];
	}

	/**
	* Lists the names of the clusters
	*
	* @return array
	*/
	public function listClusters()
	{
		$clusters = $this->getClusters();
		return array_keys($clusters);
	}
}
