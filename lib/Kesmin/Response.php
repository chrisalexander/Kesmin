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
* Response object for returning and outputting the result of an operation
*/
class Kesmin_Response extends Kesmin_Output
{
	/**
	* Stores the data about what the operation was
	*/
	protected $data;

	/**
	* Stores the type of what the operation was
	*/
	protected $type;

	/**
	* Stores the result of whatever the operation was
	*/
	protected $result;

	/**
	* Constructs and stores the data
	*
	* @var string $type
	* @var array $data
	* @var generic $result
	*/
	public function __construct($type, $data, $result)
	{
		$this->type = ucwords($type);
		$this->data = $data;
		$this->result = $result;
	}

	/**
	* Outputs as HTML
	*/
	public function toHTML()
	{
		$output = '<h2>Result of ' . $this->type . '</h2>';
		$output .= '<h3>Data parameters</h3><ul>';
		foreach ($this->data as $key=>$value) {
			$output .= '<li>' . $key . ': ' . $value . '</li>';
		}
		$output .= '</ul><h3>Result</h3>';
		if (is_array($this->result)) {
			$output .= '<ul>';
			foreach ($this->result as $key=>$value) {
				$output .= '<li>' . $key . ': ';
				if (is_array($value)) {
					$output .= '<ul>';
					foreach ($value as $k=>$v) {
						$output .= '<li>' . $k . ': ' . ((is_array($v)) ? print_r($value, true) : $v) . '</li>';
					}
					$output .= '</ul>';
				} else {
					$output .= $value;
				}
				$output .= '</li>';
			}
			$output .= '</ul>';
		} else {
			$output .= (string) $this->result;
		}
		return $output;
	}

	/**
	* Outputs as text
	*/
	public function toText()
	{
		$outputs = array();
		if (is_array($this->result)) {
			foreach ($this->result as $key=>$value) {
				if (is_array($value)) {
					$outputs[] = $key;
					foreach ($value as $k=>$v) {
						$outputs[] = "\t" . $k . ': ' . $v;
					}
				} else {
					$outputs[] = $key . ': ' . $value;
				}
			}
		} else {
			$outputs[] = (string) $this->result;
		}
		$output = implode("\n", $outputs);
		return $output;
	}
}
