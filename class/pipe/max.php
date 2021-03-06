<?php /*********************************************************************
 *
 *   Copyright : (C) 2010 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


class pipe_max
{
	static function php($a, $b)
	{
		return patchwork::string($a) < patchwork::string($b) ? $b : $a;
	}

	static function js()
	{
		?>/*<script>*/

function($a, $b)
{
	return num(str($a), 1) < num(str($b), 1) ? $b : $a;
}

<?php	}
}
