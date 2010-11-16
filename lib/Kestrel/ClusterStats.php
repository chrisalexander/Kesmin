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
* Represents Kestrel stats for a cluster
*/
class Kestrel_ClusterStats extends Kestrel_Stats
{
	/**
	* The name of the cluster the server these stats belong to is in
	*
	* @var string
	*/
	protected $cluster;

	/**
	* Constructs the object
	* 
	* @return Kestrel_ClusterStats
	*/
	public function __construct($cluster)
	{
		$this->cluster = $cluster;
	}

	/**
	* Adds a server's data to this cluster data
	*
	* @param string $name the name of the server
	* @param array $data the data from the server
	*/
	public function addServer($name, $data)
	{
		foreach($data->getData() as $key=>$items) {
			if (!isset($this->stats[$key]) || !is_array($this->stats[$key])) {
				$this->stats[$key] = array('servers'=>0);
			}
			foreach($items as $k=>$v) {
				if (is_array($v)) {
					if (isset($this->stats[$key][$k]) && !is_array($this->stats[$key][$k])) {
						$this->stats[$key][$k] = array();
					}
					foreach ($v as $w) {
						$this->stats[$key][$k][] = $w;
					}
				} else {
					if (!isset($this->stats[$key][$k])) {
						$this->stats[$key][$k] = 0;
					}
					$this->stats[$key][$k] += $v;
				}
			}
			$this->stats[$key]['servers']++;
		}
	}

	/**
	* Formats this class as HTML
	*
	* @return string
	*/
	public function toHTML()
	{
		$output = '<h2>Queues</h2><ul>';
		foreach ($this->stats as $key=>$stats) {
			$output .= '<li><a href="?method=showqueue&cluster=' . $this->cluster . '&key=' . str_replace('+','___',$key) . '">' . $key . '</a></li>';
		}
		$output .= '</ul>';
		$output .= '<h3>Actions</h3><ul>';
                $output .= '<li><a href="?method=flushall&cluster=' . $this->cluster . '">Flush All Queues on All Servers</a></li>';
                $output .= '<li><a href="?method=deleteall&cluster=' . $this->cluster . '">Delete All Queues on All Servers</a></li>';
                $output .= '</ul>';
		return $output;
	}

	/**
	* Formats this class as text
	*
	* @return string
	*/
	public function toText()
	{
		$outputs = array();
                foreach ($this->stats as $key=>$stats) {
                        $outputs[] = $key;
                }
                $output = implode("\n", $outputs);
                return $output;
	}
}
