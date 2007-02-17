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

$S = isset($_GET['stop']);
$S && ob_start('ob_gzhandler', 8192);

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: max-age=0,private,must-revalidate');

?><html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Debug Window</title>
<style type="text/css">
body
{
	margin: 0px;
	padding: 0px;
}
pre
{
	font-family: Arial;
	font-size: 10px;
	border-top: 1px solid;
	margin: 0px;
	padding: 5px;
}
pre:hover
{
	background-color: #D9E4EC;
}
</style>
<script type="text/javascript">/*<![CDATA[*/
function Z()
{
	scrollTo(0, window.innerHeight||(document.documentElement||document.body).scrollHeight);
}
//]]></script>
</head>
<body><?php

$sleep = 500;	// (ms)
$period = 5;	// (s)

ignore_user_abort(false);
@set_time_limit(0);

$error_log = ini_get('error_log');
$error_log = $error_log ? $error_log : './error.log';
echo str_repeat(' ', 512), // special MSIE
	'<pre>';
$S||flush();

$sleep = max(100, (int) $sleep);
$i = $period = max(1, (int) 1000*$period / $sleep);
$sleep *= 1000;
while (1)
{
	clearstatcache();
	if (is_file($error_log))
	{
		echo '<b></b>'; // Test the connexion for "ignore_user_abort(false)"
		$S||flush();

		$h = @fopen($error_log, 'r');
		while (!feof($h))
		{
			echo preg_replace(
				"'in .*?[\\\\/]\.([^\\\\/]+)\.{$cia_paths_token}\.[01][0-9]+-?\.zcache\.php'e",
				"'in '.strtr('$1','_','/')",
				fgets($h)
			);
		}
		fclose($h);

		echo '<script type="text/javascript">/*<![CDATA[*/Z()//]]></script>';
		$S||flush();

		unlink($error_log);
	}
	else if (!--$i)
	{
		$i = $period;
		echo '<b></b>'; // Test the connexion for "ignore_user_abort(false)"
		$S||flush();
	}

	if ($S)
	{
		echo '<script type="text/javascript">/*<![CDATA[*/scrollTo(0,0);if(window.opener&&opener.E&&opener.E.buffer.length)document.write(opener.E.buffer.join("")),opener.E.buffer=[]//]]></script>';
		break;
	}

	usleep($sleep);
}

exit;