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
				if (patchwork::resolvePublicPath($template . '.ptl')) return $template;
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
			$this->contentType = isset(patchwork_static::$contentType['.' . $a])
				? patchwork_static::$contentType['.' . $a]
				: 'application/octet-stream';
		}

		$this->contentType && patchwork::header('Content-Type: ' . $this->contentType);
	}

	function metaCompose()
	{
		patchwork::setMaxage($this->maxage);
		patchwork::setExpires($this->expires);
		patchwork::watch($this->watch);
		if ($this->canPost) patchwork::canPost();
	}


	static function get($agent, $args = array())
	{
		$o = (object) array();

		try
		{
			$agent = patchwork::resolveAgentClass($agent, $args);
			$agent = new $agent($args);
			$o = $agent->compose($o);
			$agent->metaCompose();
		}
		catch (patchwork_exception_forbidden   $agent) {W("Forbidden acces detected" );}
		catch (patchwork_exception_redirection $agent) {W("HTTP redirection detected");}
		catch (patchwork_exception_static      $agent) {}

		return $o;
	}
}
