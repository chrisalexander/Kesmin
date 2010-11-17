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
* Represents Kestrel stats for a single server
*/
class Kestrel_Stats extends Kesmin_Output
{
	/**
	* Holds the compiled stats for this instance
	*
	* @var array
	*/
	protected $stats = array();

	/**
	* Holds the name of the server
	*
	* @var string
	*/
	protected $name;

	/**
	* The name of the cluster the server these stats belong to is in
	*
	* @var string
	*/
	protected $cluster;

	/**
	* Constructs the object and parses the data into the format
	* 
	* @param string $name the name of the server
	* @param string $data the data from kestrel
	* @return Kestrel_Stats
	*/
	public function __construct($name, $cluster, $data)
	{
		$this->name = $name;
		$this->cluster = $cluster;
		$this->stats = $this->parse($data);
	}

	/**
	* Parses the data input string from kestrel into a suitable form
	*
	* @param string $data
	*/
	protected function parse($data)
	{
		$stats = array();
		$d = explode("\r\n", $data);

		foreach ($d as $stat) {
			$matches = array();
			if(preg_match('/^STAT queue_(.*)(_total_items) (.*)$/', $stat, $matches) ||
				preg_match('/^STAT queue_(.*)(_expired_items) (.*)$/', $stat, $matches) ||
				preg_match('/^STAT queue_(.*)(_mem_items) (.*)$/', $stat, $matches) ||
				preg_match('/^STAT queue_(.*)(_items) (.*)$/', $stat, $matches) ||
				preg_match('/^STAT queue_(.*)(_logsize) (.*)$/', $stat, $matches) ||
				preg_match('/^STAT queue_(.*)(_mem_bytes) (.*)$/', $stat, $matches) ||
				preg_match('/^STAT queue_(.*)(_age) (.*)$/', $stat, $matches) ||
				preg_match('/^STAT queue_(.*)(_discarded) (.*)$/', $stat, $matches) ||
				preg_match('/^STAT queue_(.*)(_waiters) (.*)$/', $stat, $matches) ||
				preg_match('/^STAT queue_(.*)(_children) (.*)$/', $stat, $matches) ||
				preg_match('/^STAT queue_(.*)(_bytes) (.*)$/', $stat, $matches)
				)
			{
				$type = substr($matches[2], 1);
				$q_name = $matches[1];
				$val = $matches[3];

				// special case to expand children
				if (($type == 'children') && (strlen(trim($val)) > 0))
				{
					$val = explode(',', $val);
				}
				$stats[$q_name][$type] = $val;
			}
		}
		ksort($stats);
		return $stats;
	}

	/**
	* Returns all the keys stored in kestrel
	*
	* @return array
	*/
	public function getKeys()
	{
		return array_keys($this->stats);
	}

	/**
	* Returns a Kestrel_StatsKey object for a given key
	*
	* @param string $key the key to get for
	* @return Kestrel_StatsKey
	*/
	public function getKeyObject($key)
	{
		$key = str_replace('___', '+', $key);
		if (!isset($this->stats[$key])) {
			throw new Exception('Key \'' . $key . '\' does not exist on this server');
		}
		return new Kestrel_StatsKey($key, $this->cluster, $this->name, $this->stats[$key]);
	}

	/**
	* Returns the stats data in raw format
	*
	* @return array
	*/
	public function getData()
	{
		return $this->stats;
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
			$output .= '<li><a href="?method=showqueue&cluster=' . $this->cluster . '&server=' . $this->name . '&key=' . str_replace('+','___',$key) . '">' . $key . '</a></li>';
		}
		$output .= '</ul>';
		$output .= '<h3>Actions</h3><ul>';
                $output .= '<li><a href="?method=flushall&cluster=' . $this->cluster . '&server=' . $this->name . '">Flush All</a></li>';
                $output .= '<li><a href="?method=deleteall&cluster=' . $this->cluster . '&server=' . $this->name . '">Delete All</a></li>';
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
