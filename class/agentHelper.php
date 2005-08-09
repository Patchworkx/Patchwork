<?php

class agentHelper
{
	public $data;

	public function __construct($data = false)
	{
		$this->data = $data ? $data : (object) array();
	}

	public function setVar($varname, $value)
	{
		$this->data->$varname =& $value;
	}

	public function setVars($vararray)
	{
		while (list($key) = each($vararray)) $this->data->$key =& $vararray[$key];
	}

	public function setBlock($blockname, $vararray)
	{
		$blocks = explode('.', $blockname);
		$blockcount = count($blocks)-1;
		$loop =& $this->data;

		$i = -1;
		while (++$i < $blockcount)
		{
			$loop =& $loop->{$blocks[$i]}->array;
			$loop =& $loop[ count($loop)-1 ];
		}

		if (isset($loop->{$blocks[$i]})) $loop->{$blocks[$i]}->array[] = (object) $vararray;
		else $loop->{$blocks[$i]} = new agentHelper_blockContainer__((object) $vararray);
	}
}

class agentHelper_blockContainer__ extends loop
{
	public $array;

	public function __construct($data) {$this->array = array($data);}
	protected function prepare() {return count($this->array);}
	protected function next()
	{
		return (list(, $value) = each($this->array)) ? $value : (reset($this->array) && false);
	}
}
