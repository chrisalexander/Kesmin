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
* Abstract to force classes to define a common set of output types
*/
abstract class Kesmin_Output
{
	abstract public function toHTML();
	abstract public function toText();
}
