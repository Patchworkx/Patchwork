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


class
{
	// This RegExp must work in most Javascript implementations too
	const EMAIL_RX = '(?:[-+=_a-zA-Z0-9%]+(\\.[-+=_a-zA-Z0-9%]+)*@([-+=_a-zA-Z0-9%]+(\\.[-+=_a-zA-Z0-9%]+)*))';

	static $IMAGETYPE = array(
		1 => 'gif', 'jpg', 'png',
		5 => 'psd', 'bmp', 'tif', 'tif', 'jpc', 'jp2', 'jpx', 'jb2', 'swc', 'iff'
	);

	static function get(&$value, $type, $args = array())
	{
		$type = "get_$type";
		return self::$type($value, $args);
	}

	static function getFile(&$value, $type, $args = array())
	{
		if (!is_array($value)) return '';

		if ($value['error']==4) return '';
		if ($value['error']) return false;

		if ('image/pjpeg' == $value['type']) $value['type'] = 'image/jpeg';

		$type = "getFile_$type";
		return self::$type($value, $args);
	}


	# no args
	protected static function get_b   (&$value, &$args) {return self::get_bool($value, $args);}
	protected static function get_bool(&$value, &$args)
	{
		return (string) (bool) $value;
	}

	# min, max
	protected static function get_i  (&$value, &$args) {return self::get_int($value, $args);}
	protected static function get_int(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$result = trim(str_replace(' ', '', strtr($value, ",.'", ' ')));
		if (!preg_match('/^[+-]?[0-9]+$/u', $result)) return false;
		if (isset($args[0]) && $result < $args[0]) return false;
		if (isset($args[1]) && $result > $args[1]) return false;

		return (int) $result;
	}

	# min, max
	protected static function get_f   (&$value, &$args) {return self::get_float($value, $args);}
	protected static function get_float(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$rx = '(?:(?:\d*\.\d+)|(?:\d+\.\d*))';
		$rx = "(?:[+-]\s*)?(?:(?:\d+|$rx)[eE][+-]?\d+|$rx|[1-9]\d*|0[xX][\da-fA-F]+|0[0-7]*)(?!\d)";

		$result = trim(str_replace(' ', '', strtr($value, "'", ' ')));
		$result = strtr($result, ',', '.');
		if (!preg_match("'^$rx$'u", $result)) return false;
		if (isset($args[0]) && $result < $args[0]) return false;
		if (isset($args[1]) && $result > $args[1]) return false;

		return (float) $result;
	}

	# array
	protected static function get_a       (&$value, &$args) {return self::get_in_array($value, $args);}
	protected static function get_in_array(&$value, &$args)
	{
		return in_array($value, $args[0]) ? $value : false;
	}

	# no args
	protected static function get_html(&$value, &$args)
	{
		$a = array();

		if ($result = self::get_text($value, $a))
		{
			static $parser;

			if (!isset($parser))
			{
				$parser = new HTML_Safe;
				$parser->deleteTags[] = 'form';
			}

			$result = $parser->parse($result);
			$result = str_replace(
				array('{~}'     , '{/}'     , p::__BASE__(), p::__HOST__()),
				array('{&#126;}', '{&#047;}', '{~}'        , '{/}'),
				$result
			);
		}

		return $result;
	}

	# regexp
	protected static function get_c   (&$value, &$args) {return self::get_char($value, $args);}
	protected static function get_char(&$value, &$args)
	{
		if (!is_scalar($value) || strspn($value, "\r\n")) return false;
		$result = self::get_text($value, $args);
		return $result ? substr(preg_replace('/\s+/u', ' ', " {$result} "), 1, -1) : $result;
	}

	# regexp
	protected static function get_t   (&$value, &$args) {return self::get_text($value, $args);}
	protected static function get_text(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$result = preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]+/', '', $value);
		false !== strpos($result, "\r") && $result = strtr(str_replace("\r\n", "\n", $result), "\r", "\n");
		preg_match('/[^\x00-\x{2ff}]/u', $result)
			&& preg_match(UTF8_NFC_RX, $result)
			&& $result = utf8_normalizer::toNFC($result);

		if (isset($args[0]))
		{
			$rx = implode(':', $args);
			$rx = preg_replace('/(?<!\\\\)((?:\\\\\\\\)*)@/', '$1\\@', $rx);
			if (!preg_match("@^(?:{$rx})$@Dsu", $result)) return false;
		}

		return $result;
	}

	# no args
	protected static function get_email(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$result = trim($value);

		if ( !preg_match('/^' . self::EMAIL_RX . '$/u', $result, $domain) ) return false;
		if ( function_exists('checkdnsrr') && !checkdnsrr($domain[2]) ) return false;

		return $result;
	}

	# (bool) international
	protected static function get_phone(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$r = preg_replace('/[^+0-9]+/u', '', $value);
		$r = preg_replace('/^00/u', '+', $r);

		if (!preg_match('/^\+?[0-9]{4,}$/u', $r)) return false;
		if (isset($args[0]) && $args[0] && strpos($r, '+')!==0) return false;

		return $r;
	}

	# no args
	protected static function get_date(&$value, &$args)
	{
		if (!is_scalar($value)) return false;

		$r = trim($value);

		if ('0000-00-00' == $r) return $value = '';

		$r = preg_replace('/^(\d{4})-(\d{2})-(\d{2})$/u', '$3-$2-$1', $r);

		$Y = date('Y');
		$r = preg_replace('/^[^0-9]+/u', '', $r);
		$r = preg_replace('/[^0-9]+$/u', '', $r);
		$r = preg_split('/[^0-9]+/u', $r);

		if (2 == count($r)) $r[2] = $Y;
		else if (1 == count($r))
		{
			$r = $r[0];
			switch (strlen($r))
			{
				case 4:
				case 6:
				case 8:
					$r = array(
						substr($r, 0, 2),
						substr($r, 2, 2),
						substr($r, 4)
					);

					if (!$r[2]) $r[2] = $Y;

					break;

				default: return false;
			}
		}

		if (3 != count($r)) return false;
		if ($r[2]<100)
		{
			$r[2] += 1900;
			if ($Y - $r[2] > 80) $r[2] += 100;
		}

		if (31 < $r[0] || 12 < $r[1]) return false;

		return sprintf('%02d-%02d-%04d', $r[0], $r[1], $r[2]);
	}

	# size (octet), regexp
	protected static function get_file(&$value, &$args)
	{
		$result = $value;

		if (isset($args[1]) && $args[1])
		{
			$result = array($args[1]);
			$result = self::get_char($value, $result);
			if (false === $result) return false;
		}

		if (isset($args[0]) && $args[0])
		{
			$s = @filesize($result);
			if (false === $s || ($args[0] && $s > $args[0])) return false;
		}

		return $result;
	}

	# size (octet), regexp, type, max_width, max_height, min_width, min_height
	protected static function get_image(&$value, &$args)
	{
		$type =       isset($args[2]) ? $args[2] : 0;
		$max_width =  isset($args[3]) ? $args[3] : 0;
		$max_height = isset($args[4]) ? $args[4] : 0;
		$min_width =  isset($args[5]) ? $args[5] : 0;
		$min_height = isset($args[6]) ? $args[6] : 0;

		$result = self::get_file($value, $args);

		if ($result === false) return false;

		$size = @getimagesize($result);
		if (is_array($size))
		{
			if ($max_width && $size[0]>$max_width) return false;
			if ($min_width && $size[0]<$min_width) return false;
			if ($max_height && $size[1]>$max_height) return false;
			if ($min_height && $size[1]<$min_height) return false;

			if ($type && !in_array(self::$IMAGETYPE[$size[2]], (array) $type)) return false;

			$args[7] =& $size;
			return $result;
		}
		else return false;
	}


	# size (octet), regexp
	protected static function getFile_file(&$value, &$args)
	{
		if (isset($args[0]) && $args[0])
		{
			$a = array($args[0]);
			if ( false === self::get_file($value['tmp_name'], $a) ) return false;
		}

		if (isset($args[1]) && $args[1])
		{
			$value['name'] = basename(strtr($value['name'], "\\\0", '/_'));

			$a = array(0, $args[1]);
			if ( false === self::get_file($value['name'], $a) ) return false;
		}

		return $value;
	}

	# size (octet), regexp, type, max_width, max_height, min_width, min_height
	protected static function getFile_image(&$value, &$args)
	{
		$a = array(0, isset($args[1]) ? $args[1] : '');
		$args[1] = false;

		if ( false === self::get_image($value['tmp_name'], $args) ) return false;
		if ( false === self::get_file($value['name'], $a) ) return false;

		$type =& $args[7][2];
		$type = self::$IMAGETYPE[$type];

		$value['info'] = $args[7];

		return $value;
	}
}
