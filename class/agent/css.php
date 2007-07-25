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


class extends agent
{
	const contentType = 'text/css';

	public $get = '__0__';

	protected

	$maxage = -1,
	$watch = array('public/css');


	function control()
	{
		$tpl = $this->get->__0__;

		if ($tpl !== '')
		{
			$tpl = str_replace(
				array('\\', '../'),
				array('/' , '/'),
				"css/$tpl.css"
			);
		}

		$this->template = $tpl;
	}
}
