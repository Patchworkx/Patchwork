<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends loop
{
	public $autoResolve = true;

	protected $agent;
	protected $keys;

	private $data;
	private $firstCall = true;

	function __construct($agent, $keys = false)
	{
		$this->agent = $agent;
		if (false !== $keys) $this->keys = $keys;
	}

	final protected function prepare() {return 1;}
	final protected function next()
	{
		if ($this->firstCall)
		{
			$this->firstCall = false;
			if (!isset($this->data))
			{
				$data = $this->get();
				$data->{'a$'} = $this->agent;

				if ($this->autoResolve)
				{
					if (!isset($this->keys) || preg_match("'^(/|https?://)'", $this->agent))
					{
						list($CIApID, $base, $data->{'a$'}, $keys, $a) = CIA_resolveTrace::call($this->agent);

						foreach ($a as $k => &$v) $data->$k =& $v;

						array_walk($keys, 'jsquoteRef');

						$data->{'k$'} = implode(',', $keys);

						if (false !== $base)
						{
							$data->{'v$'} = $CIApID;
							$data->{'r$'} = $base;
						}
					}
					else $data->{'k$'} = $this->keys;
				}

				$this->data = $data;
			}

			return clone $this->data;
		}
		else
		{
			$this->firstCall = true;
			return false;
		}
	}

	protected function get()
	{
		return (object) array();
	}
}
