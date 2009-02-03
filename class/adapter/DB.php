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
	protected static $db = array();

	static function connect($dsn)
	{
		if (isset(self::$db[$dsn])) return self::$db[$dsn];

		$db = MDB2::factory($dsn);

		if (PEAR::isError($db))
		{
			trigger_error($db->getMessage(), E_USER_ERROR);
			p::disable(true);
		}

		$mysql = 'mysql' === substr($db->phptype, 0, 5);

		$db->loadModule('Extended');
		$db->setErrorHandling(PEAR_ERROR_TRIGGER, E_USER_WARNING);
		$db->setFetchMode(MDB2_FETCHMODE_OBJECT);
		$db->setOption('seqname_format', 'zeq_%s');
		$db->setOption('portability', MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL ^ MDB2_PORTABILITY_FIX_CASE);

		$mysql && $db->setOption('default_table_type', 'InnoDB');

		$db->connect();

		if ($mysql)
		{
			$db->exec('SET NAMES utf8');
			$db->exec("SET collation_connection='utf8_unicode_ci'");
			$db->exec('SET group_concat_max_len=10485760'); // The effective maximum length is constrained by max_allowed_packet
		}

		$db->beginTransaction();

		self::$db[$dsn] = $db;

		return $db;
	}

	static function disconnect($db)
	{
		$db->in_transaction && $db->commit();
		$db->disconnect();
	}

	static function __destructStatic()
	{
		foreach (self::$db as $db) self::disconnect($db);

		self::$db = array();
	}
}
