<?php

class extends agent
{
	function control()
	{
	}

	function compose($o)
	{
		patchworkPath('zcache/', $o->zcacheDepth);

		$o->apps = new loop_array(
			$GLOBALS['patchwork_path'],
			array($this, 'filterApp')
		);

		return $o;
	}

	function filterApp($o)
	{
		$depth = PATCHWORK_PATH_LEVEL - $o->KEY;

		$o = (object) array(
			'name' => pStudio::getAppname($depth),
			'depth' => $depth,
			'path' => $o->VALUE,
		);

		return $o;
	}
}