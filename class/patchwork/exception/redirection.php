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


class patchwork_exception_redirection extends Exception
{
	protected $url;

	function __construct($url)
	{
		$url = (string) $url;
		$url = '' === $url ? '' : (preg_match("'^([^:/]+:/|\.+)?/'", $url) ? $url : (patchwork::__BASE__() . ('index' === $url ? '' : $url)));

		if ('.' === substr($url, 0, 1)) W('Current redirection behaviour with relative URLs may change in a future version of Patchwork. As long as this notice appears, using relative URLs is strongly discouraged.');

		$this->url = $url;
	}

	function redirect($javascript)
	{
		patchwork::disable();

		$url = $this->url;

		if ($javascript)
		{
			$url = 'location.replace(' . ('' !== $url
				? "'" . addslashes($url) . "'"
				: 'location')
			. ')';

			header('Content-Length: ' . strlen($url));
			echo $url;
		}
		else
		{
			header('HTTP/1.1 302 Found');
			header('Location: ' . ('' !== $url ? $url : $_SERVER['REQUEST_URI']));
		}
	}
}
