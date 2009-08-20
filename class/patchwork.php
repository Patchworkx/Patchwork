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


// {{{ Shortcut for applications developers
if ($_SERVER['PATCHWORK_LANG'])
{
	function T($string, $lang = false)
	{
		if (!$lang) $lang = p::__LANG__();
		return TRANSLATOR::get($string, $lang, true);
	}
}
else
{
	function T($string) {return $string;}
}
// }}}

// {{{ PHP session mechanism overloading
class sessionHandler implements ArrayAccess
{
	function offsetGet($k)     {$_SESSION = SESSION::getAll(); return $_SESSION[$k];}
	function offsetSet($k, $v) {$_SESSION = SESSION::getAll(); $_SESSION[$k] =& $v;}
	function offsetExists($k)  {$_SESSION = SESSION::getAll(); return isset($_SESSION[$k]);}
	function offsetUnset($k)   {$_SESSION = SESSION::getAll(); unset($_SESSION[$k]);}

	static $id;

	static function close()   {return true;}
	static function gc($life) {return true;}

	static function open($path, $name)
	{
		session_cache_limiter('');
		ini_set('session.use_only_cookies', true);
		ini_set('session.use_cookies', false);
		ini_set('session.use_trans_sid', false);
		return true;
	}

	static function read($id)
	{
		$_SESSION = SESSION::getAll();
		self::$id = $id;
		return '';
	}

	static function write($id, $data)
	{
		if (self::$id != $id) SESSION::regenerateId();
		return true;
	}

	static function destroy($id)
	{
		SESSION::regenerateId(true);
		return true;
	}
}

session_set_save_handler(
	array($k = 'sessionHandler', 'open'),
	array($k, 'close'),
	array($k, 'read'),
	array($k, 'write'),
	array($k, 'destroy'),
	array($k, 'gc')
);

$_SESSION = new sessionHandler;
// }}}

// {{{ Database sugar
function DB($dsn = 0)
{
	static $db = array();

	isset($db[$dsn]) || $db[$dsn] = adapter_DB::connect(0 === $dsn ? $CONFIG['DSN'] : $dsn);

	return $db[$dsn];
}
// }}}

function jsquote($a, $addDelim = true, $delim = "'")
{
	if (is_array($a))
	{
		W('jsquote error: can not quote an array');
		$a = '';
	}

	if ((string) $a === (string) ($a-0)) return $a-0;

	false !== strpos($a, "\r") && $a = strtr(str_replace("\r\n", "\n", $a), "\r", "\n");
	false !== strpos($a, '\\') && $a = str_replace('\\', '\\\\', $a);
	false !== strpos($a, "\n") && $a = str_replace("\n", '\n', $a);
	false !== strpos($a, '</') && $a = str_replace('</', '<\\/', $a);
	false !== strpos($a, "\xC2\x85"    ) && $a = str_replace("\xC2\x85"    , '\u0085', $a); // Next Line
	false !== strpos($a, "\xE2\x80\xA8") && $a = str_replace("\xE2\x80\xA8", '\u2028', $a); // Line Separator
	false !== strpos($a, "\xE2\x80\xA9") && $a = str_replace("\xE2\x80\xA9", '\u2029', $a); // Paragraph Separator
	false !== strpos($a, $delim) && $a = str_replace($delim, '\\' . $delim, $a);

	if ($addDelim) $a = $delim . $a . $delim;

	return $a;
}

function jsquoteRef(&$a) {$a = jsquote($a);}

function patchwork_error_handler($code, $message, $file, $line)
{
	if (error_reporting())
	{
		switch ($code)
		{
		case E_NOTICE:
		case E_STRICT:
			if (strpos($message, '__00::')) return;

			static $offset = 0;
			$offset || $offset = -13 - strlen(PATCHWORK_PATH_TOKEN);

			if ('-' === substr($file, $offset, 1)) return;

			break;

		case E_WARNING:
			if (stripos($message, 'safe mode')) return;
		}

		class_exists('patchwork_error', false) || __autoload('patchwork_error'); // http://bugs.php.net/42098 workaround
		patchwork_error::handle($code, $message, $file, $line);
	}
}

ini_set('log_errors', true);
ini_set('error_log', PATCHWORK_PROJECT_PATH . 'error.patchwork.log');
ini_set('display_errors', false);

set_error_handler('patchwork_error_handler');


class
{
	static

	$agentClass,
	$catchMeta = false;


	protected static

	$ETag = '',
	$LastModified = 0,

	$host,
	$lang,
	$base,
	$uri,

	$appId,
	$metaInfo,
	$metaPool = array(),
	$isGroupStage = true,
	$binaryMode = false,
	$requestMode = '',
	$requestArg  = '',

	$maxage = false,
	$private = false,
	$expires = 'auto',
	$watchTable = array(),
	$headers = array(),

	$is_enabled = false,
	$ob_starting_level,
	$ob_level,
	$varyEncoding = false,
	$contentEncoding = false,
	$lockedContentType = false,
	$is304 = false,

	$agentClasses = '',
	$privateDetectionMode = false,
	$antiCSRFtoken,
	$detectCSRF = false,
	$total_time = 0,

	$allowGzip = array(
		'text/','script','xml','html','bmp','wav',
		'msword','rtf','excel','powerpoint',
	),

	$ieSniffedTypes_edit = array(
		'text/plain','text/richtext','audio/x-aiff','audio/basic','audio/wav',
		'image/gif','image/jpeg','image/pjpeg','image/tiff','image/x-png','image/png',
		'image/x-xbitmap','image/bmp','image/x-jg','image/x-emf','image/x-wmf',
		'video/avi','video/mpeg','application/pdf','application/java',
		'application/base64','application/postscript',
	),

	$ieSniffedTypes_download = array(
		'application/octet-stream','application/macbinhex40',
		'application/x-compressed','application/x-msdownload',
		'application/x-gzip-compressed','application/x-zip-compressed',
	),

	$ieSniffedTags = array(
		'body','head','html','img','plaintext',
		'a href','pre','script','table','title'
	);


	static function __constructStatic()
	{
		if (isset($_GET['p:']))
		{
			list(self::$requestMode, self::$requestArg) = explode(':', $_GET['p:'], 2) + array(1 => '');

			if ('s' === self::$requestMode || 'flipside' === self::$requestMode)
			{
				$a = explode('?', $_SERVER['REQUEST_URI'], 2);
				$a[1] = preg_replace('/(^|&)p:(?:=[^&]*)?/', '', $a[1]);
				if ('' === $a[1]) unset($a[1]);
				else if ('&' === $a[1][0]) $a[1] = substr($a[1], 1);
				$_SERVER['REQUEST_URI'] = implode('?', $a);
			}
		}

#>		patchwork_debugger::execute();

		if (!$CONFIG['clientside']) unset($_COOKIE['JS']);
		else if ('flipside' === self::$requestMode)
		{
			preg_match('/[^.]+\.[^\.0-9]+$/', $_SERVER['HTTP_HOST'], $domain);
			$domain = isset($domain[0]) ? '.' . $domain[0] : false;
			self::setcookie('JS', isset($_COOKIE['JS']) && !$_COOKIE['JS'] ? '' : '0', 0, '/', $domain);
			header('Location: ' . $_SERVER['REQUEST_URI']);
			exit;
		}
		else if ('serverside' === self::$requestMode)
		{
			self::$requestMode = '';
			isset($_COOKIE['JS']) && $_COOKIE['JS'] = '0';
		}

		self::$appId = abs($GLOBALS['patchwork_appId'] % 10000);

		// Language controller

		switch (self::$requestMode)
		{
		case 'k':

			if ('' !== $_SERVER['PATCHWORK_LANG'])
			{
				$_SERVER['PATCHWORK_LANG'] = isset($CONFIG['i18n.lang_list'][self::$requestArg])
					? self::$requestArg
					: patchwork_language::getBest(array_keys($CONFIG['i18n.lang_list']), self::$requestArg);
			}

			self::setLang($_SERVER['PATCHWORK_LANG']);

			break;

		case '':
			if ('' === $_SERVER['PATCHWORK_LANG'] && '' !== key($CONFIG['i18n.lang_list']))
			{
				patchwork_language::HTTP_Negociate($CONFIG['i18n.lang_list']);
			}

		default: self::setLang($_SERVER['PATCHWORK_LANG']);
		}
	}

	static function start()
	{
/*<
		self::log(
			'<a href="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '" target="_blank">'
			. htmlspecialchars(preg_replace("'&v\\\$=[^&]*'", '', $_SERVER['REQUEST_URI']))
			. '</a>'
		);
		register_shutdown_function(array(__CLASS__, 'log'), '', true);
>*/


		// Cache synchronization

		if ('-' === strtr(self::$requestMode, '-tpax', '#----'))
		{
			self::header('Content-Type: text/javascript');

			self::$lockedContentType = true;

			if (isset($_GET['v$']) && self::$appId != $_GET['v$'] && 'x' !== self::$requestMode)
			{
				echo 'w(w.r(1,' . (int)!DEBUG . '))';
				return;
			}
		}


		// patchwork_appId cookie synchronisation

		if (!isset($_COOKIE['v$']) || $_COOKIE['v$'] != self::$appId)
		{
			$a = $CONFIG['i18n.lang_list'][$_SERVER['PATCHWORK_LANG']];
			$a = implode($a, explode('__', $_SERVER['PATCHWORK_BASE'], 2));
			$a = preg_replace("'\?.*$'", '', $a);
			$a = preg_replace("'^https?://[^/]*'i", '', $a);
			$a = dirname($a . ' ');
			if (1 === strlen($a)) $a = '';

			self::setcookie('v$', self::$appId, $_SERVER['REQUEST_TIME'] + $CONFIG['maxage'], $a .'/');
			$GLOBALS['patchwork_private'] = true;
		}


		// Anti Cross-Site-Request-Forgery / Javascript-Hijacking token

		if (
			isset($_COOKIE['T$'])
			&& (
				!IS_POSTING
				|| (isset($_POST['T$']) && substr($_COOKIE['T$'], 1) === substr($_POST['T$'], 1))
				|| (isset( $_GET['T$']) && substr($_COOKIE['T$'], 1) === substr( $_GET['T$'], 1))
			)
			&& 33 === strlen($_COOKIE['T$'])
			&& 33 === strspn($_COOKIE['T$'], 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
		) self::$antiCSRFtoken = $_COOKIE['T$'];
		else self::getAntiCSRFtoken(true);

		isset($_GET['T$']) && $GLOBALS['patchwork_private'] = true;
		define('PATCHWORK_TOKEN_MATCH', isset($_GET['T$']) && substr(self::$antiCSRFtoken, 1) === substr($_GET['T$'], 1));
		if (IS_POSTING) unset($_POST['T$'], $_POST['T$']);


		// Start output

		self::$is_enabled = true;
		self::$ob_starting_level = ob_get_level();
		ob_start(array(__CLASS__, 'ob_sendHeaders'));
		ob_start(array(__CLASS__, 'ob_filterOutput'), 32768);
		self::$ob_level = 2;

		try
		{
			$agent = $_SERVER['PATCHWORK_REQUEST'];

			if ('' === self::$requestMode || '-' === strtr(self::$requestMode, '-axks', '#----'))
			{
				$agent = self::resolveAgentClass($agent, $_GET);
			}

			switch (self::$requestMode)
			{
			case 't': patchwork_static::sendTemplate($agent);        break;
			case 'p': patchwork_static::sendPipe(self::$requestArg); break;
			case 'a': patchwork_clientside::render($agent, false);   break;
			case 'x': patchwork_clientside::render($agent, true);    break;
			case 'k': patchwork_agentTrace::send($agent);            break;
			case 's': isset($_COOKIE['JS']) && $_COOKIE['JS'] = '0';
			case '' : self::servePublicRequest($agent);
			}
		}
		catch (patchwork_exception_redirection $a)
		{
			$a->redirect('-' === strtr(self::$requestMode, '-tpax', '#----'));
		}

		while (self::$ob_level)
		{
			ob_end_flush();
			--self::$ob_level;
		}
	}

	// {{{ Public request controller
	static function servePublicRequest($agent)
	{
		// Synch exoagents on browser request
		if (isset($_COOKIE['cache_reset_id'])
			&& self::$appId == $_COOKIE['cache_reset_id']
			&& setcookie('cache_reset_id', '', 0, '/'))
		{
			self::updateAppId();
			self::touch('foreignTrace');
			self::touch('appId');

			self::setMaxage(0);
			self::setPrivate();

			header('Refresh: 0');

			echo '<html><head><script type="text/javascript">location.',
				IS_POSTING ? 'replace(location)' : 'reload()',
				'</script></head></html>';

			return;
		}

		self::$binaryMode = 'text/html' !== substr(constant("$agent::contentType"), 0, 9);

/*<
		if (PATCHWORK_SYNC_CACHE && !self::$binaryMode)
		{
			self::updateAppId();

			$a = $CONFIG['i18n.lang_list'][$_SERVER['PATCHWORK_LANG']];
			$a = implode($a, explode('__', $_SERVER['PATCHWORK_BASE'], 2));
			$a = preg_replace("'\?.*$'", '', $a);
			$a = preg_replace("'^https?://[^/]*'i", '', $a);
			$a = dirname($a . ' ');
			if (1 === strlen($a)) $a = '';

			self::setcookie('v$', self::$appId, $_SERVER['REQUEST_TIME'] + $CONFIG['maxage'], $a .'/');

			self::touch('');

			for ($i = 0; $i < 16; ++$i) for ($j = 0; $j < 16; ++$j)
			{
				$dir = PATCHWORK_ZCACHE . dechex($i) . '/' . dechex($j) . '/';

				if (file_exists($dir))
				{
					$h = opendir($dir);
					while (false !== $file = readdir($h)) '.' !== $file && '..' !== $file && unlink($dir . $file);
					closedir($h);
				}
			}

			if (!IS_POSTING)
			{
				self::setMaxage(0);
				self::setPrivate();

				header('Refresh: 0');

				echo '<html><head><script type="text/javascript">location.reload()</script></head></html>';
				return;
			}
		}
>*/

		// load agent
		if (IS_POSTING || self::$binaryMode || !isset($_COOKIE['JS']) || !$_COOKIE['JS'])
		{
			self::$binaryMode || self::setPrivate();
			patchwork_serverside::loadAgent($agent, false, false);
		}
		else patchwork_clientside::loadAgent($agent);
	}
	// }}}

	protected static function updateAppId()
	{
		// config.patchwork.php's last modification date is used for
		// version synchronisation with clients and caches.

		global $patchwork_appId;

		$oldAppId = sprintf('%020d', $patchwork_appId);

		$patchwork_appId += $_SERVER['REQUEST_TIME'] - filemtime(PATCHWORK_PROJECT_PATH . 'config.patchwork.php');
		self::$appId = abs($patchwork_appId % 10000);

		if (file_exists(PATCHWORK_PROJECT_PATH . '.patchwork.php') && $h = @fopen(PATCHWORK_PROJECT_PATH . '.patchwork.php', 'r+b'))
		{
			$offset = 0;

			while (false !== $line = fgets($h))
			{
				if (false !== $pos = strpos($line, $oldAppId))
				{
					fseek($h, $offset + $pos);
					fwrite($h, sprintf('%020d', $patchwork_appId));
					break;
				}
				else $offset += strlen($line);
			}

			fclose($h);

			@touch(PATCHWORK_PROJECT_PATH . '.patchwork.php');
		}

		@touch(PATCHWORK_PROJECT_PATH . 'config.patchwork.php', $_SERVER['REQUEST_TIME']);
	}

	static function disable($exit = false)
	{
		if (self::$is_enabled && ob_get_level() === self::$ob_starting_level + self::$ob_level)
		{
			while (self::$ob_level-- > 2) {ob::$clear = true; ob_end_clean();}

			ob::$clear = true; ob_end_clean();
			self::$is_enabled = false;
			ob::$clear = true; ob_end_clean();
			self::$ob_level = 0;

			if (self::$is304) exit;
			if (!$exit) return true;
		}

		if ($exit) exit;

		return false;
	}

	static function setLang($lang)
	{
		if (!isset($CONFIG['i18n.lang_list'][$lang]) || isset(self::$lang) && self::$lang === $lang) return;

		self::$lang = $lang;

		$base = $CONFIG['i18n.lang_list'][$lang];
		$base = implode($base, explode('__', $_SERVER['PATCHWORK_BASE'], 2));

		self::$base = $base;

		self::$host = strtr($base, '#?', '//');
		self::$host = substr($base, 0, strpos(self::$host, '/', 8)+1);

		if (!isset(self::$uri)) self::$uri = self::$host . substr($_SERVER['REQUEST_URI'], 1);
		else if (PATCHWORK_I18N)
		{
			$base = preg_quote($_SERVER['PATCHWORK_BASE'], "'");
			$base = explode('__', $base, 2);
			$base[1] = '/' === $base[1] ? '[^?/]+/?' : ".+?{$base[1]}";
			$base = "'^{$base[0]}{$base[1]}(.*)$'D";

			preg_match($base, self::$uri, $base)
				? self::$uri = self::$base . self::translateRequest($base[1], $lang)
				: W('Something is wrong between p::$uri and PATCHWORK_BASE');
		}
	}

	static function __HOST__() {return self::$host;}
	static function __LANG__() {return self::$lang;}
	static function __BASE__() {return self::$base;}
	static function __URI__() {return self::$uri;}

	static function base($url = '', $noId = false)
	{
		$url = (string) $url;

		if (!preg_match("'^https?://'", $url))
		{
			$noId = '' === $url || $noId;

			if (strncmp('/', $url, 1)) $url = self::$base . $url;
			else $url = self::$host . substr($url, 1);

			if (!$noId && '/' !== substr($url, -1)) $url .= (false === strpos($url, '?') ? '?' : '&') . self::$appId;
		}

		return $url;
	}

	static function translateRequest($req, $lang)
	{
		return $req;
	}

	static function getAntiCSRFtoken($new = false)
	{
		if ($new)
		{
			$new = isset($_COOKIE['T$']) && '1' === substr($_COOKIE['T$'], 0, 1) ? '1' : '2';

			if (!isset(self::$antiCSRFtoken))
			{
				if (IS_POSTING && (isset($_POST['T$']) || !empty($_COOKIE)))
				{
#>					if (DEBUG) W('Anti CSRF alert: in non-DEBUG mode, $_POST and $_FILES would have been erased.');
#>					else
#>					{
						$GLOBALS['_POST_BACKUP'] = $_POST;
						$_POST = array();

						$GLOBALS['_FILES_BACKUP'] = $_FILES;
						$_FILES = array();

						patchwork_antiCSRF::postAlert();
#>					}
				}

				unset($_COOKIE['T$']);
			}

			self::$antiCSRFtoken = $new . self::strongid();

			self::setcookie('T$', self::$antiCSRFtoken, 0, $CONFIG['session.cookie_path'], $CONFIG['session.cookie_domain']);
			$GLOBALS['patchwork_private'] = true;
		}

		return self::$antiCSRFtoken;
	}

	/*
	 * Replacement for PHP's header() function
	 */
	static function header($string, $replace = true, $http_response_code = null)
	{
		$string = preg_replace("'[\r\n].*'s", '', $string);
		$name = strtolower(substr($string, 0, strpos($string, ':')));

		if (self::$is_enabled)
		{
			if (   0 === stripos($string, 'http/')
				|| 0 === stripos($string, 'etag')
				|| 0 === stripos($string, 'expires')
				|| 0 === stripos($string, 'cache-control')
				|| 0 === stripos($string, 'content-length')
			) return;

			if (0 === stripos($string, 'last-modified'))
			{
				self::setLastModified(strtotime(trim(substr($string, 14))));
				return;
			}

			if (self::$catchMeta) self::$metaInfo[4][$name] = $string;
		}

		if (!self::$privateDetectionMode)
		{
			if ('content-type' === $name)
			{
				$string = substr($string, 14);

				if (isset(self::$headers[$name]) && self::$lockedContentType) return;

				if (self::$is_enabled && (false !== stripos($string, 'javascript') || false !== stripos($string, 'ecmascript')))
				{
					if (self::$private) PATCHWORK_TOKEN_MATCH || patchwork_antiCSRF::scriptAlert();

					self::$detectCSRF = true;
				}

				// Any non registered mime type is treated as application/octet-stream.
				// BUT! IE does special mangling with literal application/octet-stream...
				$string = str_ireplace('application/octet-stream', 'application/x-octet-stream', $string);

				if ((false !== stripos($string, 'text/') || false !== stripos($string, 'xml')) && false === strpos($string, ';')) $string .= '; charset=utf-8';

				$string = 'Content-Type: ' . $string;
			}

			self::$headers[$name] = $replace || !isset(self::$headers[$name]) ? $string : (self::$headers[$name] . ',' . substr($string, 1+strpos($string, ':')));
			header($string, $replace);
		}
	}

	static function setcookie($name, $value = '', $expires = null, $path = '', $domain = '', $secure = false, $httponly = false)
	{
		self::setrawcookie($name, urlencode($value), $expires, $path, $domain, $secure, $httponly);
	}

	static function setrawcookie($name, $value = '', $expires = 0, $path = '', $domain = '', $secure = false, $httponly = false)
	{
		isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 5') && $httponly = false;

		if ($value !== strtr($value, ",; \t\r\n\013\014", '--------')) setrawcookie($name, $value, $expires, $path, $domain, $secure);
		else
		{
			('' === (string) $value) && $expires = 1;

			if ($domain && '.' !== substr($domain, 0, 1)) W('setcookie() RFC incompatibility: $domain must start with a dot.');

			$GLOBALS['patchwork_private'] = true;
			header('P3P: CP="' . $CONFIG['P3P'] . '"');
			header(
				"Set-Cookie: {$name}={$value}" .
					($expires  ? '; expires=' . date('D, d-M-Y H:i:s T', $expires) : '') .
					($path     ? '; path='   . $path   : '') .
					($domain   ? '; domain=' . $domain : '') .
					($secure   ? '; secure'   : '') .
					($httponly ? '; HttpOnly' : ''),
				false
			);
		}
	}

	static function gzipAllowed($type)
	{
		$type = explode(';', $type);
		$type = strtolower($type[0]);
		$len  = strlen($type);

		foreach (self::$allowGzip as $p)
		{
			$p = strpos($type, $p);
			if (false !== $p && (0 === $p || $len - $p === strlen($p))) return true;
		}

		return false;
	}

	static function readfile($file, $mime = true, $filename = true)
	{
		return patchwork_static::readfile($file, $mime, $filename);
	}

	/*
	 * Redirect the web browser to an other GET request
	 */
	static function redirect($url = '')
	{
		throw new patchwork_exception_redirection($url);
	}

	static function forbidden()
	{
		throw new patchwork_exception_forbidden();
	}

	protected static function openMeta($agentClass, $is_trace = true)
	{
		self::$isGroupStage = true;

		self::$agentClass = $agentClass;
		if ($is_trace) self::$agentClasses .= '*' . self::$agentClass;

		$default = array(false, array(), false, array(), array(), false, self::$agentClass);

		self::$catchMeta = true;

		self::$metaPool[] =& $default;
		self::$metaInfo =& $default;
	}

	protected static function closeGroupStage()
	{
		self::$isGroupStage = false;

		return self::$metaInfo[1];
	}

	protected static function closeMeta()
	{
		self::$catchMeta = false;

		$poped = array_pop(self::$metaPool);

		$len = count(self::$metaPool);

		if ($len)
		{
			self::$metaInfo =& self::$metaPool[$len-1];
			self::$agentClass = self::$metaInfo[6];
		}
		else self::$agentClass = self::$metaInfo = null;

		return $poped;
	}

	static function setLastModified($LastModified)
	{
		if ($LastModified > self::$LastModified) self::$LastModified = $LastModified;
	}

	/*
	 * Controls cache max age
	 */
	static function setMaxage($maxage)
	{
		if ($maxage < 0) $maxage = $CONFIG['maxage'];
		else $maxage = min($CONFIG['maxage'], $maxage);

		if (!self::$privateDetectionMode)
		{
			if (false === self::$maxage) self::$maxage = $maxage;
			else self::$maxage = min(self::$maxage, $maxage);
		}

		if (self::$catchMeta)
		{
			if (false === self::$metaInfo[0]) self::$metaInfo[0] = $maxage;
			else self::$metaInfo[0] = min(self::$metaInfo[0], $maxage);
		}
	}

	/*
	 * Controls cache groups
	 */
	static function setGroup($group)
	{
		if ('public' === $group) return;

		$group = array_diff((array) $group, array('public'));

		if (!$group) return;

		if (self::$privateDetectionMode) throw new patchwork_exception_private;
		else if (self::$detectCSRF) PATCHWORK_TOKEN_MATCH || patchwork_antiCSRF::scriptAlert();

		self::$private = true;

		if (self::$catchMeta)
		{
			$a =& self::$metaInfo[1];

			if (1 === count($a) && 'private' === $a[0]) return;

			if (in_array('private', $group)) $a = array('private');
			else
			{
				$b = $a;

				$a = array_unique( array_merge($a, $group) );
				sort($a);

				if ($b != $a && !self::$isGroupStage)
				{
#>					W('Misconception: patchwork::setGroup() is called in ' . self::$agentClass . '->compose( ) rather than in ' . self::$agentClass . '->control(). Cache is now disabled for this agent.');

					$a = array('private');
				}
			}
		}
	}

	static function setPrivate() {return self::setGroup('private');}


	/*
	 * Controls cache expiration mechanism
	 */
	static function setExpires($expires)
	{
		if (!self::$privateDetectionMode) if ('auto' === self::$expires || 'ontouch' === self::$expires) self::$expires = $expires;

		if (self::$catchMeta) self::$metaInfo[2] = $expires;
	}

	static function watch($watch)
	{
		if (self::$catchMeta) self::$metaInfo[3] = array_merge(self::$metaInfo[3], (array) $watch);
	}

	static function canPost()
	{
		if (self::$catchMeta) self::$metaInfo[5] = true;
	}

	static function string($a)
	{
		return is_object($a) ? $a->__toString() : (string) $a;
	}

	static function uniqid($raw = false)
	{
		return md5(uniqid(mt_rand() . pack('d', lcg_value()), true), $raw);
	}

	static function strongid($length = 32)
	{
		static $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';

		$a = '';

		do
		{
			$i = 0;
			$n = unpack('C*', self::uniqid(true) . self::uniqid(true));

			do $a .= $chars[$n[++$i]%57] . $chars[$n[++$i]%57] . $chars[$n[++$i]%57] . $chars[$n[++$i]%57];
			while ($i < 32);

			$length -= 32;
		}
		while ($length > 0);

		$length && $a = substr($a, 0, $length);

		return $a;
	}

	// Basic UTF-8 to ASCII transliteration
	static function toASCII($s)
	{
		if (preg_match("'[\x80-\xFF]'", $s))
		{
			$s = Normalizer::normalize($s, Normalizer::FORM_KD);
			$s = preg_replace('/\p{Mn}+/u', '', $s);
			$s = iconv('UTF-8', 'ASCII' . ('glibc' !== ICONV_IMPL ? '//IGNORE' : '') . '//TRANSLIT', $s);
		}

		return $s;
	}


	protected static

	$saltLength = 4,
	$saltedHashTruncation = 32;

	static function saltedHash($pwd)
	{
		$salt = self::strongid(self::$saltLength);
		return substr($salt . md5($pwd . $salt), 0, self::$saltedHashTruncation);
	}

	static function matchSaltedHash($pwd, $saltedHash)
	{
		$salt = substr($saltedHash, 0, self::$saltLength);
		$pwd  = $salt . md5($pwd . $salt);

		return 0 === substr_compare($pwd, $saltedHash, 0, self::$saltedHashTruncation);
	}


	/*
	 * Clears files linked to $message
	 */
	static function touch($message)
	{
		if (is_array($message)) foreach ($message as &$message) self::touch($message);
		else
		{
			$message = preg_split("'[\\\\/]+'u", $message, -1, PREG_SPLIT_NO_EMPTY);
			$message = array_map('rawurlencode', $message);
			$message = implode('/', $message);
			$message = str_replace('.', '%2E', $message);

			$i = 0;

			$pool = array(self::getCachePath('watch/' . $message, 'txt'));

			$er = error_reporting(0);

			while ($message = array_pop($pool))
			{
				if (file_exists($message) && $h = fopen($message, 'rb'))
				{
					flock($h, LOCK_EX+LOCK_NB, $wb) || $wb = true;

					if (!$wb)
					{
						while ($line = fgets($h))
						{
							$a = $line[0];
							$line = substr($line, 1, -1);

							if ('I' === $a) $pool[] = $line;
							else if (file_exists($line)) unlink($line) && ++$i;
						}

						$wb = !IS_WINDOWS && unlink($message);
					}

					fclose($h);

					$wb || unlink($message);

					++$i;
				}
			}

			error_reporting($er);

#>			E("patchwork::touch('$message'): $i file(s) deleted.");
		}
	}

	/*
	 * Like mkdir(), but works with multiple level of inexistant directory
	 */
	static function makeDir($dir, $mode = 0700)
	{
		return @mkdir(dirname($dir . ' '), $mode, true);
	}

	static function fopenX($file, &$readHandle = false)
	{
		if ($h = !file_exists($file))
		{
			self::makeDir($file);
			$h = @fopen($file, 'xb');
		}

		if ($h) flock($h, LOCK_EX);
		else if ($readHandle)
		{
			$readHandle = fopen($file, 'rb');
			flock($readHandle, LOCK_SH);
		}

		return $h;
	}

	/*
	 * Creates the full directory path to $filename, then writes $data to the file
	 */
	static function writeFile($filename, &$data, $Dmtime = 0)
	{
		$tmpname = dirname($filename) . '/.~' . uniqid(mt_rand(), true);

		$h = @fopen($tmpname, 'wb');

		if (!$h)
		{
			self::makeDir($tmpname);
			$h = @fopen($tmpname, 'wb');
		}

		if ($h)
		{
			fwrite($h, $data);
			fclose($h);

			if (IS_WINDOWS)
			{
				file_exists($filename) && unlink($filename);
				rename($tmpname, $filename) || unlink($tmpname);
			}
			else rename($tmpname, $filename);

			if ($Dmtime) touch($filename, $_SERVER['REQUEST_TIME'] + $Dmtime);

			return true;
		}
		else return false;
	}


	protected static function getCachePath($filename, $extension, $key = '')
	{
		if ('' !== (string) $extension) $extension = '.' . $extension;

		$hash = md5($filename . $extension . '.' . $key);
		$hash = $hash[0] . '/' . $hash[1] . '/' . substr($hash, 2);

		$filename = rawurlencode(str_replace('/', '.', $filename));
		$filename = substr($filename, 0, 224 - strlen($extension));

		return PATCHWORK_ZCACHE . $hash . '.' . $filename . $extension;
	}

	static function getContextualCachePath($filename, $extension, $key = '')
	{
		return self::getCachePath($filename, $extension, self::$base .'-'. self::$lang .'-'. DEBUG .'-'. PATCHWORK_PROJECT_PATH .'-'. $key);
	}

	static function log($message, $is_end = false, $raw_html = true)
	{
		static $prev_time = patchwork;
		self::$total_time += $a = 1000*(microtime(true) - $prev_time);

		if ('__Δms' !== $message)
		{
			$mem = function_exists('memory_get_peak_usage') ? round(memory_get_peak_usage(true)/104857.6)/10 . 'M' : '';

			if (DEBUG && $is_end) $a = sprintf('<div>Total: %.01f ms%s</div></pre><pre>', self::$total_time, $mem ? ' - ' . $mem : '');
			else
			{
				$b = ob::$in_handler ? serialize($message) : print_r($message, true);

				if (!$raw_html) $b = htmlspecialchars($b);

				$a = '<span title="Date: ' . date("d-m-Y H:i:s", $_SERVER['REQUEST_TIME']) . ($mem ? ' - Memory: ' . $mem : '') . '">' . sprintf('%.01f ms', $a) . '</span> ' . $b . "\n";
			}

			static $error_log;

			if (!isset($error_log))
			{
				$error_log = ini_get('error_log');
				$error_log || $error_log = PATCHWORK_PROJECT_PATH . 'error.patchwork.log';
			}

			$b = fopen($error_log, 'ab');
			fwrite($b, $a);
			fclose($b);
		}

		$prev_time = microtime(true);

		return $a;
	}

	static function resolveAgentClass($agent, &$args)
	{
		static $resolvedCache = array();

		if (preg_match("''u", $agent))
		{
			$agent = preg_replace("'/[./]*(?:/|$)'", '/', '/' . $agent . '/');

			preg_match('"^((?:/[- !#$%&\'()+,.;=@[\]^_`{}~a-zA-Z0-9\x80-\xff]+)*)(?<!\.(?i)ptl)/"', $agent, $a);

			$param = (string) substr($agent, strlen($a[0]), -1);
			$agent = (string) substr($a[1], 1);
		}
		else $agent = $param = '';

		$lang = self::$lang;
		$l_ng = substr($lang, 0, 2);

		if ($lang)
		{
			$lang = '/' . $lang;
			$l_ng = '/' . $l_ng;
		}

		$existingAgent = '/index';

		if ('' !== $agent)
		{
			$agent = explode('/', $agent);
			$agentLength = count($agent);

			$i = 0;
			$a = '';
			$offset = 0;

			do
			{
				$a .= '/' . $agent[$i++];

				unset($level);

				if (isset($resolvedCache[$a])) $level = true;
				else if (patchworkPath("class/agent{$a}.php", $level)) {}
				else if (patchworkPath("public/__{$a}.ptl")
					|| ($l_ng !== $lang && patchworkPath("public{$l_ng}{$a}.ptl"))
					|| ($lang && patchworkPath("public{$lang}{$a}.ptl"))) $level = false;

				if (isset($level))
				{
					$existingAgent = $a;
					$agentLevel = $level;
					$offset = $i;
				}
			} while (
				   $i < $agentLength
				&& (isset($level)
				|| patchworkPath("class/agent{$a}/")
				|| patchworkPath("public/__{$a}/")
				|| ($l_ng != $lang && patchworkPath("public{$l_ng}{$a}/"))
				|| ($lang && patchworkPath("public{$lang}{$a}/")))
			);

			if ($offset < $agentLength)
			{
				if ($i === $agentLength && ($a = p::resolvePublicPath(substr($a, 1))) && !is_dir($a))
				{
					p::setMaxage(-1);
					p::writeWatchTable('public/static', PATCHWORK_ZCACHE);
					p::readfile($a, true, false);

					exit;
				}

				$param = implode('/', array_slice($agent, $offset)) . ('' !== $param ? '/' . $param : '');
			}
		}

		if ('' !== $param)
		{
			$args['__0__'] = $param;

			$i = 0;
			foreach (explode('/', $param) as $param) $args['__' . ++$i . '__'] = $param;
		}

		$resolvedCache[$existingAgent] = true;

		$agent = 'agent' . patchwork_file2class($existingAgent);

		isset($agentLevel)
			|| patchworkPath('class/agent/index.php', $agentLevel)
			|| $agentLevel = false;

		if (true !== $agentLevel && !class_exists($agent, false))
		{
			if (false === $agentLevel)
			{
				$agentLevel = '' !== pathinfo($existingAgent, PATHINFO_EXTENSION) ? 'const contentType="";' : '';
				eval("class {$agent} extends agentTemplate {{$agentLevel}}");
			}
			else $GLOBALS['patchwork_autoload_cache'][$agent] = $agentLevel + PATCHWORK_PATH_OFFSET;
		}

		return $agent;
	}

	protected static function agentArgs($agent)
	{
		$cache = self::getContextualCachePath('agentArgs/' . $agent, 'txt');
		$readHandle = true;
		if ($h = self::fopenX($cache, $readHandle))
		{
			// get declared arguments in $agent->get public property

			$args = get_class_vars($agent);
			$args =& $args['get'];

			is_array($args) || $args = (array) $args;
			$args && array_walk($args, array('self', 'stripArgs'));


			// autodetect private data for antiCSRF

			$private = '0';

			self::$privateDetectionMode = true;

			try
			{
				new $agent instanceof agent || W("Class {$agent} does not inherit from class agent");
			}
			catch (patchwork_exception_private $d)
			{
				$private = '1';
			}
			catch (patchwork_exception_redirection $d)
			{
			}


			// Cache results

			fwrite($h, $private . (DEBUG ? '' : serialize($args)));
			fclose($h);

			self::$privateDetectionMode = false;

			if ($private) $args[] = 'T$';
		}
		else
		{
			$cache = stream_get_contents($readHandle);
			fclose($readHandle);

#>			if (DEBUG)
#>			{
#>				$args = get_class_vars($agent);
#>				$args =& $args['get'];
#>
#>				is_array($args) || $args = (array) $args;
#>				$args && array_walk($args, array('self', 'stripArgs'));
#>			}
#>			else
#>			{
				$args = unserialize(substr($cache, 1));
#>			}

			if ($cache[0]) $args[] = 'T$';
		}

		return $args;
	}

	protected static function stripArgs(&$a, $k)
	{
		if (is_string($k)) $a = $k;

		false !== strpos($a, "\000") && $a = str_replace("\000", '', $a);
		false !== strpos($a, '\\')   && $a = strtr($a, array('\\\\' => '\\', '\\:' => "\000"));

		if (false !== strpos($a, ':'))
		{
			$a = explode(':', $a, 2);
			$a = $a[0];
		}

		false !== strpos($a, "\000") && $a = strtr($a, "\000", ':');
	}

	protected static function agentCache($agentClass, $keys, $type, $group = false)
	{
		if (false === $group) $group = self::$metaInfo[1];
		$keys = serialize(array($keys, $group));

		return self::getContextualCachePath($agentClass, $type, $keys);
	}

	static function writeWatchTable($message, $file = '', $exclusive = true)
	{
		$file = patchworkPath($file);

		if (!$file && !$exclusive) return;

		foreach (array_unique((array) $message) as $message)
		{
			if ($file && self::$catchMeta) self::$metaInfo[3][] = $message;

			$message = preg_split("'[\\\\/]+'u", $message, -1, PREG_SPLIT_NO_EMPTY);
			$message = array_map('rawurlencode', $message);
			$message = implode('/', $message);
			$message = str_replace('.', '%2E', $message);

			$path = self::getCachePath('watch/' . $message, 'txt');
			if ($exclusive) self::$watchTable[$path] = (bool) $file;

			if (!$file) continue;

			if ($file_isnew = !file_exists($path)) self::makeDir($path);

			$h = fopen($path, 'ab');
			fwrite($h, 'U' . $file . "\n");
			fclose($h);

			if ($file_isnew)
			{
				$message = explode('/', $message);
				while (null !== array_pop($message))
				{
					$a = $path;
					$path = self::getCachePath('watch/' . implode('/', $message), 'txt');

					if ($file_isnew = !file_exists($path)) self::makeDir($path);

					$h = fopen($path, 'ab');
					fwrite($h, 'I' . $a . "\n");
					fclose($h);

					if (!$file_isnew) break;
				}
			}
		}
	}


	protected static function appendToken($f)
	{
		return patchwork_antiCSRF::appendToken($f);
	}

	static function ob_filterOutput($buffer, $mode)
	{
		$one_chunk = $mode === (PHP_OUTPUT_HANDLER_START | PHP_OUTPUT_HANDLER_END);

		static $type = false;
		false !== $type || $type = isset(self::$headers['content-type']) ? strtolower(substr(self::$headers['content-type'], 14)) : 'html';

		if (PHP_OUTPUT_HANDLER_START & $mode) self::$lockedContentType = true;

		// Anti-XSRF token

		if (false !== strpos($type, 'html'))
		{
			static $lead;

			if (PHP_OUTPUT_HANDLER_START & $mode)
			{
				$lead = '';
#>				if ((!PATCHWORK_SYNC_CACHE || IS_POSTING) && !self::$binaryMode) $buffer = patchwork_debugger::getProlog() . $buffer;
			}

			$tail = '';

			if (PHP_OUTPUT_HANDLER_END & $mode)
			{
#>				if ((!PATCHWORK_SYNC_CACHE || IS_POSTING) && !self::$binaryMode) $buffer .= patchwork_debugger::getConclusion();
			}
			else
			{
				$a = strrpos($buffer, '<');
				if (false !== $a)
				{
					$tail = strrpos($buffer, '>');
					if (false !== $tail && $tail > $a) $a = $tail;

					$tail = substr($buffer, $a);
					$buffer = substr($buffer, 0, $a);
				}
			}

			$buffer = $lead . $buffer;
			$lead = $tail;


			$a = stripos($buffer, '<form');
			if (false !== $a)
			{
				$a = preg_replace_callback(
					'#<form\s(?:[^>]+?\s)?method\s*=\s*(["\']?)post\1.*?>#iu',
					array(__CLASS__, 'appendToken'),
					$buffer
				);

				if ($a !== $buffer)
				{
					self::$private = true;
					if (empty($_COOKIE['JS'])) self::$maxage = 0;
					$buffer = $a;
				}

				unset($a);
			}
		}
		else if (PHP_OUTPUT_HANDLER_START & $mode)
		{
			// Fix IE mime-sniff misfeature
			// (see http://www.splitbrain.org/blog/2007-02/12-internet_explorer_facilitates_cross_site_scripting
			// http://msdn.microsoft.com/fr-fr/library/ms775147.aspx
			// This will break some binary contents, but it is very unlikely that a legitimate
			// binary content may contain the suspicious bytes that trigger IE mime-sniffing.

			$a = substr($buffer, 0, 256);
			$lt = strpos($a, '<');
			if (false !== $lt
				&& !(isset(self::$headers['content-disposition']) && 0 === stripos(self::$headers['content-disposition'], 'attachment'))
				&& $b = in_array($type, self::$ieSniffedTypes_edit) ? 1 : (in_array($type, self::$ieSniffedTypes_download) ? 2 : 0))
			{
				foreach (self::$ieSniffedTags as $tag)
				{
					$tail = stripos($a, '<' . $tag, $lt);
					if (false !== $tail && $tail + strlen($tag) < strlen($a))
					{
						if (2 === $b) header('Content-Type: application/x-octet-stream');
						else
						{
							$buffer = substr($buffer, 0, $tail)
								. '<!--'
								. str_repeat(' ', max(1, 248 - strlen($tag) - $tail))
								. '-->'
								. substr($buffer, $tail);
						}

						break;
					}
				}
			}
		}


		// GZip compression

		if (self::gzipAllowed($type))
		{
			if ($one_chunk)
			{
				if (isset($buffer[100]))
				{
					self::$varyEncoding = true;
					self::$is_enabled || header('Vary: Accept-Encoding', false);

					$mode = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';

					if ($mode)
					{
						$algo = array(
							'deflate'  => 'gzdeflate',
							'gzip'     => 'gzencode',
							'compress' => 'gzcompress',
						);

						foreach ($algo as $encoding => $algo) if (false !== stripos($mode, $encoding))
						{
							self::$contentEncoding = $encoding;
							self::$is_enabled || header('Content-Encoding: ' . $encoding);
							$buffer = $algo($buffer);
							break;
						}
					}
				}
			}
			else
			{
				self::$varyEncoding = true;
				if (!self::$is_enabled && (PHP_OUTPUT_HANDLER_START & $mode)) header('Vary: Accept-Encoding', false);
				$buffer = ob_gzhandler($buffer, $mode);
			}
		}

		if ($one_chunk && !self::$is_enabled) header('Content-Length: ' . strlen($buffer));

		return $buffer;
	}

	static function ob_sendHeaders($buffer)
	{
		p::header(
			isset(self::$headers['content-type'])
				? self::$headers['content-type']
				: 'Content-Type: text/html'
		);

		$is304 = false;

		if (!IS_POSTING && ('' !== $buffer || self::$ETag))
		{
			if (!self::$maxage) self::$maxage = 0;
			if ($GLOBALS['patchwork_private']) self::$private = true;

			$LastModified = $_SERVER['REQUEST_TIME'];


			/* Write watch table */

			if ('ontouch' === self::$expires) self::$expires = 'auto';

			if ('auto' === self::$expires && self::$watchTable && !DEBUG)
			{
				$path = array_keys(self::$watchTable);
				sort($path);

				$validator = $_SERVER['PATCHWORK_BASE'] .'-'. $_SERVER['PATCHWORK_LANG'] .'-'. PATCHWORK_PROJECT_PATH .'-'. DEBUG;
				$validator = substr(md5(serialize($path) . $validator), 0, 8);

				$ETag = $validator;

				$validator = PATCHWORK_ZCACHE . $validator[0] .'/'. $validator[1] .'/'. substr($validator, 2) .'.v.txt';

				$readHandle = true;
				if ($h = self::fopenX($validator, $readHandle))
				{
					$a = substr(md5(microtime(1)), 0, 8);
					fwrite($h, $a .'-'. $LastModified);
					fclose($h);

					foreach ($path as $path)
					{
						$h = fopen($path, 'ab');
						fwrite($h, 'U' . $validator . "\n");
						fclose($h);
					}

					self::writeWatchTable('appId', $validator);
				}
				else
				{
					$a = fread($readHandle, 32);
					fclose($readHandle);

					$a = explode('-', $a);
					$LastModified = $a[1];
					$a = $a[0];
				}

				$ETag .= $a . (int)(bool) self::$private . sprintf('%08x', self::$maxage);
			}
			else
			{
				/* ETag / Last-Modified validation */

				$ETag = substr(
					md5(
						self::$ETag .'-'. $buffer .'-'. self::$expires .'-'. self::$maxage .'-'.
						(int)(bool)self::$private .'-'. implode('-', self::$headers)
					), 0, 8
				);

				if (self::$LastModified) $LastModified = self::$LastModified;
				else if (
					isset($_SERVER['HTTP_USER_AGENT'])
					&&  strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')
					&& !strpos($_SERVER['HTTP_USER_AGENT'], 'Opera')
					&& preg_match('/MSIE [0-6]\./', $_SERVER['HTTP_USER_AGENT'])
					&& self::gzipAllowed(strtolower(substr(self::$headers['content-type'], 14))))
				{
					// Patch an IE<=6 bug when using ETag + compression

					self::$private = true;

					$ETag = hexdec($ETag);
					if ($ETag > PHP_INT_MAX) $ETag -= PHP_INT_MAX + 1;
					$LastModified = $ETag;
					$ETag = dechex($ETag);
				}
			}

			$ETag = '"' . $ETag . '"';
			self::$ETag = $ETag;
			self::$LastModified = $LastModified;

			$is304 = (isset($_SERVER['HTTP_IF_NONE_MATCH'    ]) && $_SERVER['HTTP_IF_NONE_MATCH'] === $ETag)
			      || (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] === $LastModified);

			header('Expires: ' . gmdate(
				'D, d M Y H:i:s \G\M\T',
				time() + (self::$private || !self::$maxage ? 0 : self::$maxage)
			));
			header(
				'Cache-Control: max-age=' . self::$maxage
				. (self::$private ? ',private,must' : ',public,proxy') . '-revalidate'
			);

			if ($is304)
			{
				$buffer = '';
				header('HTTP/1.1 304 Not Modified');
			}
			else
			{
				header('ETag: ' . $ETag);
				header('Last-Modified: ' . gmdate('D, d M Y H:i:s \G\M\T', $LastModified));

				if ('' !== $buffer)
				{
					header('Accept-Ranges: bytes');
					self::$varyEncoding && header('Vary: Accept-Encoding', false);

					if ($range = isset($_SERVER['HTTP_RANGE'])
						? patchwork_httpRange::negociate(strlen($buffer), $ETag, $LastModified)
						: false)
					{
						self::$is_enabled = false;
						patchwork_httpRange::sendChunks($range, $buffer, self::$headers['content-type'], 0);
					}
				}
			}
		}

		if (!$is304)
		{
			stripos(self::$headers['content-type'], 'html') && header('P3P: CP="' . $CONFIG['P3P'] . '"');
			self::$is_enabled && header('Content-Length: ' . strlen($buffer));
			'' !== $buffer && self::$contentEncoding && header('Content-Encoding: ' . self::$contentEncoding);
		}

		self::$is304 = $is304;

		if ('HEAD' === $_SERVER['REQUEST_METHOD']) $buffer = '';

		return $buffer;
	}

	static function resolvePublicPath($filename, &$path_idx = 0)
	{
		if ($path_idx && $path_idx > PATCHWORK_PATH_LEVEL) return false;

		static $last_lang,
			$last__in_filename = '', $last__in_path_idx,
			$last_out_filename,      $last_out_path_idx;

		$lang = self::__LANG__();
		$l_ng = substr($lang, 0, 2);

		if ($lang)
		{
			$lang = '/' . $lang;
			$l_ng = '/' . $l_ng;
		}


		if ($filename == $last__in_filename
			&& $lang  == $last_lang
			&& $last__in_path_idx <= $path_idx
			&& $path_idx <= $last_out_path_idx)
		{
			$path_idx = $last_out_path_idx;
			return $last_out_filename;
		}

		$last_lang = $lang;
		$last__in_filename = $filename;
		$last__in_path_idx = $path_idx;

		$filename = '/' . $filename;

		$level = PATCHWORK_PATH_LEVEL - $path_idx;

		if ($lang)
		{
			$lang = patchworkPath("public{$lang}{$filename}", $lang_level, $level);
			$last_lang_level = $lang_level;

			if ($l_ng !== $last_lang)
			{
				$l_ng = patchworkPath("public{$l_ng}{$filename}", $last_lang_level, $level);
				if ($last_lang_level > $lang_level)
				{
					$lang = $l_ng;
					$lang_level = $last_lang_level;
				}
			}
		}

		$l_ng = patchworkPath("public/__{$filename}", $last_lang_level, $level);
		if (!$lang || $last_lang_level > $lang_level)
		{
			$lang = $l_ng;
			$lang_level = $last_lang_level;
		}

		$path_idx = PATCHWORK_PATH_LEVEL - $lang_level;

		$last_out_filename = $lang;
		$last_out_path_idx = $path_idx;

		return $lang;
	}

	static function syncTemplate($template, $ctemplate)
	{
		if (file_exists($ctemplate))
		{
			$template = self::resolvePublicPath($template . '.ptl');
			if ($template && filemtime($ctemplate) <= filemtime($template)) return unlink($ctemplate);
		}
	}
}

class agent
{
	const contentType = 'text/html';

	public $get = array();

	protected

	$template = '',
	$maxage  = 0,
	$expires = 'auto',
	$canPost = false,
	$watch = array(),

	// By default, equals to contentType const if it's not empty
	$contentType;


	function control() {}
	function compose($o) {return $o;}
	function getTemplate()
	{
		if ($this->template) return $this->template;

		$class = get_class($this);

		do
		{
			if ((false === $tail = strrpos($class, '__'))
				|| ((false !== $tail = substr($class, $tail+2)) && strspn($tail, '0123456789') !== strlen($tail)))
			{
				$template = patchwork_class2file(substr($class, 6));
				if (p::resolvePublicPath($template . '.ptl')) return $template;
			}
		}
		while (__CLASS__ !== $class = get_parent_class($class));

		return 'bin';
	}

	final public function __construct($args = array())
	{
		$class = get_class($this);

		$this->contentType = constant($class . '::contentType');

		$a = (array) $this->get;

		$this->get = (object) array();
		$_GET = array();

		foreach ($a as $key => &$a)
		{
			if (is_string($key))
			{
				$default = $a;
				$a = $key;
			}
			else $default = '';

			false !== strpos($a, "\000") && $a = str_replace("\000", '', $a);

			if (false !== strpos($a, '\\'))
			{
				$a = strtr($a, array('\\\\' => '\\', '\\:' => "\000"));
				$a = explode(':', $a);
				$b = count($a);
				do false !== strpos($a[--$b], "\000") && $a[$b] = strtr($a[$b], "\000", ':');
				while ($b);
			}
			else $a = explode(':', $a);

			$key = array_shift($a);

			$b = isset($args[$key]) ? (string) $args[$key] : $default;
			false !== strpos($b, "\000") && $b = str_replace("\000", '', $b);

			if ($a)
			{
				$b = FILTER::get($b, array_shift($a), $a);
				if (false === $b) $b = $default;
			}

			$_GET[$key] = $this->get->$key = $b;
		}

		$this->control();

		if (!$this->contentType
			&& '' !== $a = strtolower(pathinfo(patchwork_class2file($class), PATHINFO_EXTENSION)))
		{
			$this->contentType = isset(patchwork_static::$contentType[$a])
				? patchwork_static::$contentType[$a]
				: 'application/octet-stream';
		}

		$this->contentType && p::header('Content-Type: ' . $this->contentType);
	}

	function metaCompose()
	{
		p::setMaxage($this->maxage);
		p::setExpires($this->expires);
		p::watch($this->watch);
		if ($this->canPost) p::canPost();
	}


	static function get($agent, $args = array())
	{
		$o = (object) array();

		try
		{
			$agent = p::resolveAgentClass($agent, $args);
			$agent = new $agent($args);
			$o = $agent->compose($o);
			$agent->metaCompose();
		}
		catch (patchwork_exception_forbidden   $agent) {W("Forbidden acces detected" );}
		catch (patchwork_exception_redirection $agent) {W("HTTP redirection detected");}

		return $o;
	}
}

class agentTemplate extends agent
{
	protected

	$maxage = -1,
	$watch = array('public/templates');


	function control() {}
}

class loop
{
	private

	$loopLength = false,
	$filter = array();


	function __construct($filter = '')
	{
		$filter && $this->addFilter($filter);
	}

	protected function prepare() {}
	protected function next() {}

	final public function &loop($escape = false)
	{
		$catchMeta = p::$catchMeta;
		p::$catchMeta = true;

		if ($this->loopLength === false) $this->loopLength = (int) $this->prepare();

		if (!$this->loopLength) $data = false;
		else
		{
			$data = $this->next();
			if ($data || is_array($data))
			{
				$data = (object) $data;
				$i = 0;
				$len = count($this->filter);
				while ($i<$len) $data = (object) call_user_func($this->filter[$i++], $data, $this);
			}
			else $this->loopLength = false;
		}

		p::$catchMeta = $catchMeta;

		if ($escape && !($this instanceof L_) && $data)
		{
			foreach ($data as &$i) is_string($i) && $i = htmlspecialchars($i);
		}

		return $data;
	}

	final public function addFilter($filter) {if ($filter) $this->filter[] = $filter;}

	final public function __toString()
	{
		$catchMeta = p::$catchMeta;
		p::$catchMeta = true;

		if ($this->loopLength === false) $this->loopLength = (int) $this->prepare();

		p::$catchMeta = $catchMeta;

		return (string) $this->loopLength;
	}

	final public function getLength()
	{
		return (int) $this->__toString();
	}
}

class patchwork_exception_private extends Exception {}
