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


class extends adapter_convertTo_abstract
{
	function file($file)
	{
		$file = escapeshellarg($file);
		$file = `antiword -t -w 0 -m UTF-8 {$file}`;

		return VALIDATE::get($file, 'text');
	}
}
