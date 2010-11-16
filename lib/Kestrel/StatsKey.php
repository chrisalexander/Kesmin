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
* Represents Kestrel stats for a single key
*/
class Kestrel_StatsKey extends Kesmin_Output
{
	/**
	* Holds the data from kestrel for this key
	*
	* @var array
	*/
	protected $data = array();

	/**
	* The key we are referencing
	*
	* @var string
	*/
	protected $key;

	/**
	* The name of the cluster the server these stats belong to is in
	*
	* @var string
	*/
	protected $cluster;

	/**
	* The server this is from
	*
	* @var string
	*/
	protected $server;

	/**
	* Link to use to the server
	*
	* @var string
	*/
	protected $serverlink;

	/**
	* Constructs the object
	* 
	* @param string $key the name of the key
	* @param string $cluster the cluster name
	* @param string $server the name of the server, if it exists
	* @param string $data the data from kestrel
	* @return Kestrel_Stats
	*/
	public function __construct($key, $cluster, $server, $data)
	{
		$this->key = $key;
		$this->cluster = $cluster;
		$this->server = $server;
		if (strlen($this->server) > 0) {
			$this->serverlink = '&server=' . $this->server;
		} else {
			$this->serverlink = '';
		}
		$this->data = $data;
	}

	/**
	* Formats this class as HTML
	*
	* @return string
	*/
	public function toHTML()
	{
		$output = '<h2>Queue status of "' . $this->key . '"</h2>';
		$output .= '<ul>';
		foreach ($this->data as $key=>$data) {
			if (!is_array($data)) {
				$output .= '<li>' . $key . ': ' . $data . '</li>';
			} else {
				$output .= '<li>' . $key . ': <ul>';
				foreach ($data as $k=>$d) {
					$output .= '<li>' . $k . ': ' . $d . '</li>';
				}
				$output .= '</ul></li>';
			}
		}
		$output .= '</ul>';
		$output .= '<h3>Actions</h3><ul>';
		$output .= '<li><a href="?method=getqueue&cluster=' . $this->cluster . $this->serverlink . '&key=' . str_replace('+','___',$this->key) . '">Get Item</a></li>';
		$output .= '<li><a href="?method=peekqueue&cluster=' . $this->cluster . $this->serverlink . '&key=' . str_replace('+','___',$this->key) . '">Peek Item</a></li>';
		$output .= '<li>';
		$output .= '<form action="" method="POST"><input type="hidden" name="method" value="addqueue" /><input type="hidden" name="cluster" value="' . $this->cluster . '" />';
		$output .= '<input type="hidden" name="server" value="' . $this->server . '" /><input type="hidden" name="key" value="' . $this->key . '" />';
		$output .= '<input type="text" name="data" /><input type="submit" value="Add" />';
		$output .= '</form></li>';
		$output .= '<li><a href="?method=flushqueue&cluster=' . $this->cluster . $this->serverlink . '&key=' . str_replace('+','___',$this->key) . '">Flush</a></li>';
		$output .= '<li><a href="?method=deletequeue&cluster=' . $this->cluster . $this->serverlink . '&key=' . str_replace('+','___',$this->key) . '">Delete</a></li>';
		$output .= '<li><a href="?method=flushfanoutqueues&cluster=' . $this->cluster . $this->serverlink . '&key=' . str_replace('+','___',$this->key) . '">Flush Fanout Queues</a></li>';
		$output .= '<li><a href="?method=deletefanoutqueues&cluster=' . $this->cluster . $this->serverlink . '&key=' . str_replace('+','___',$this->key) . '">Delete Fanout Queues</a></li>';
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
		foreach($this->data as $key=>$data) {
			if (is_array($data)) {
				$outputs[] = $key;
				foreach ($data as $k=>$d) {
					$outputs[] = "\t" . $k . ': ' . $d;
				}
			} else {
				$outputs[] = $key . ': ' . $data;
			}
		}
		$output = implode("\n", $outputs);
		return $output;
	}
}
