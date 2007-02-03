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


class extends CIA
{
	static function call($agent)
	{
		static $cache = array();

		if (isset($cache[$agent])) return $cache[$agent];
		else $cache[$agent] =& $trace;

		$args = array();
		$HOME = $home = self::__HOME__();
		$agent = self::home($agent, true);
		$keys = false;
		$s = '\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'';
		$s = "/w\.k\((-?[0-9]+),($s),($s),($s),\[((?:$s(?:,$s)*)?)\]\)/su";

		if (
			   0 === strpos($agent, $HOME)
			&& !ini_get('safe_mode')
			&& is_callable('shell_exec')
			&& (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? !extension_loaded('openssl') : false)
			&& $keys = $GLOBALS['CONFIG']['php']
		)
		{
			$keys = $keys . ' -q ' . implode(' ', array_map('escapeshellarg', array(
				processPath('getTrace.php'),
				resolvePath('config.php'),
				$_SERVER['CIA_HOME'],
				self::__LANG__(),
				substr($agent, strlen($HOME)),
				isset($_SERVER['HTTPS']) ? (bool) $_SERVER['HTTPS'] : false
			)));

			$keys = shell_exec($keys);

			if (!preg_match($s, $keys, $keys)) $keys = false;
		}

		if (!$keys)
		{
			$agent = implode(self::__LANG__(), explode('__', $agent, 2));

			if (ini_get('allow_url_fopen'))
			{
				$keys = file_get_contents($agent . '?k$', false, stream_context_create(array('http' => array('method' => 'GET'))));
			}
			else
			{
				require_once 'HTTP/Request.php';

				$keys = new HTTP_Request($agent . '?k$');
				$keys->sendRequest();
				$keys = $keys->getResponseBody();
			}

			if (!preg_match($s, $keys, $keys))
			{
				E('Error while getting meta info data for ' . htmlspecialchars($agent));
				self::disable(true);
			}
		}

		$CIApID = (int) $keys[1];
		$home = stripcslashes(substr($keys[2], 1, -1));
		$home = preg_replace("'__'", self::__LANG__(), $home, 1);
		$agent = stripcslashes(substr($keys[3], 1, -1));
		$a = stripcslashes(substr($keys[4], 1, -1));
		$keys = eval('return array(' . $keys[5] . ');');

		if ('' !== $a)
		{
			$args['__0__'] = $a;

			$i = 0;
			foreach (explode('/', $a) as $a) $args['__' . ++$i . '__'] = $a;
		}

		if ($home == $HOME) $CIApID = $home = false;
		else self::watch('foreignTrace');

		return $trace = array($CIApID, $home, $agent, $keys, $args);
	}
}
