<?php

class extends agent_pStudioWidget_location
{
	function compose($o)
	{
		$o->data = @file_get_contents($this->realpath);

		return $o;
	}
}