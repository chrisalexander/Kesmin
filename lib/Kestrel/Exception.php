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
* Exception for problems with Kestrel
*/
class Kestrel_Exception extends Exception
{
	public function toHTML()
	{
		return '<h2>Exception</h2><p>' . $this->getMessage() . '</p>';
	}

	public function toText()
	{
		return 'EXCEPTION: ' . $this->getMessage();
	}
}
