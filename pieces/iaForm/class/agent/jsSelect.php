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
	const contentType = 'text/javascript';

	protected
		$maxage = -1,
		$template = 'form/jsSelect.js',
		
		$param = array();

	function compose($o)
	{
		unset($this->param['valid']);
		unset($this->param['firstItem']);
		unset($this->param['multiple']);

		$this->form = new iaForm($o, '', true, '');
		$this->form->add('select', 'select', $this->param);

		return $o;
	}
}
