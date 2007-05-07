/***************************************************************************
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


/*
* Set a board variable for data persistancy across pages.
*
* WARNING: you must not use board variables to store any sensitive information.
* See http://www.boutell.com/newfaq/creating/scriptpass.html for explanation.
*/
function setboard($name, $value)
{
	if (t($name, 'object')) for ($value in $name) setboard($value, $name[$value]);
	else
	{
		$window = setboard.topwin;

		$name = '%K' + eUC(location.hostname + 0 + $name) + '%V';

		var $winName = $window.name,
			$varIdx = $winName.indexOf($name),
			$varEndIdx;

		if ($varIdx>=0)
		{
			$varEndIdx = $winName.indexOf('%K', $varIdx + $name.length);
			$winName = $winName.substring(0, $varIdx) + ( $varEndIdx>=0 ? $winName.substring($varEndIdx) : '' );
		}

		$window.name = $winName + $name + eUC($value);
	}
}


window.BOARD || (function()
{
	var $board = window, $i, $h = location.hostname + 0;

	// This eval avoids a parse error with browsers not supporting exceptions.
	t($board.Error) && eval('try{while((($i=$board.parent)!=$board)&&t($i.name))$board=$i}catch($i){}');

	setboard.topwin = $board;
	$board = $board.name;

	window.BOARD = {};
	$i = $board.indexOf('%K');

	if (0 <= $i)
	{
		$board = parseurl(
			$board.substr($i).replace(
				/%K/g, '&').replace(
				/%V/g, '=')
			, '&'
		);

		for ($i in $board) $i.indexOf($h) || (BOARD[ $i.substr($h.length) ] = $board[$i]);
	}
})();
