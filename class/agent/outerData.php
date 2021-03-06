<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class agent_outerData extends agent
{
	static

	$outerData = array(),
	$outerTemplate = 'bin';

	protected $data;

	function control()
	{
		$this->data     = self::$outerData;
		$this->template = self::$outerTemplate;
	}

	function compose($o)
	{
		return $this->data;
	}
}
