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


class pipe_lower
{
	static function php($string)
	{
		return mb_strtolower(patchwork::string($string));
	}

	static function js()
	{
		?>/*<script>*/

function($string)
{
	return str($string).toLowerCase();
}

<?php	}
}
