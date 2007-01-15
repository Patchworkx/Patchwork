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


class extends agent_bin
{
	protected $maxage = -1;

	function control()
	{
		CIA::header('Content-Type: text/javascript; charset=UTF-8');
	}

	function compose($o)
	{
		$o->DATA = file_get_contents(resolvePath('public/__/fckeditor/fckeditor.js'));
		return $o;
	}
}