<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class
{
	static function php($string)
	{
		return mb_strtoupper(p::string($string));
	}

	static function js()
	{
		?>/*<script>*/

P$upper = function($string)
{
	return str($string).toUpperCase();
}

<?php	}
}
