if (window.lF)
{
	if (!window.pLuL)
	{
		function loadUpload($form)
		{
			window.UPID = $form.UPLOAD_IDENTIFIER.value;

			var $i = 0, $len = $form.length;

			for (; $i < $len; ++$i) if ($form[$i].type == 'file' && $form[$i].value) break;

			if ($i < $len) open(
				{home:'upload'|js},
				'',
				'status=no,scrollbars=no,resizable=no,dependent=yes,height=100,width=350,left=' + parseInt(screen.availWidth/2 - 200) + ',top=' + parseInt(screen.availHeight/2 - 100)
			);
		}

		pLuL=[
			'img/blank.gif',
			'img/upload/b.png',
			'img/upload/i.gif',
			'img/upload/l.png',
			'img/upload/r.png',
			'img/upload/t.gif'
		];

		setTimeout('for(i in pLuL)j=pLuL[i],pLuL[i]=new Image,pLuL[i].src=home(j)', 2000);
	}
}
else
{
	var $sending, $progress, $remaining, $detail, $unitWidth, $maxWidth, $unitHtml, $QJsrs, $bytes_total;

	$Done = {"Téléchargement terminé"|js} + ' !';
	$Minutes = {"minutes"|js};
	$Minute = {"minute"|js};
	$Secondes = {"secondes"|js};
	$Seconde = {"seconde"|js};
	$Remainings = {"restantes"|js};
	$Remaining = {"restante"|js};
	$At = {"à"|js};

	UPID = opener && opener.UPID;

	function $bytes($byte)
	{
		var $suffix = ' Ko', $div;

		if ($byte >= ($div=1073741824)) $suffix = ' Go';
		else if ($byte >= ($div=1048576)) $suffix = ' Mo';
		else $div = 1024;

		$byte /= $div;
		$div = $byte < 10 ? 100 : 1;
		$byte = parseInt($div*$byte)/$div;

		return $byte + $suffix;
	}

	function $showProgress(a)
	{
		var $html = '', $i = $unitWidth;

		if (a && a.bytes_total)
		{
			for (; $i < $maxWidth && $i/$maxWidth <= a.bytes_uploaded/a.bytes_total; $i += $unitWidth) $html += $unitHtml;
			$progress.innerHTML = $html || $unitHtml;

			a.est_min = Math.round(a.est_sec / 60);
			a.est_sec %= 60;

			a.speed_last /= 1024;
			a.speed_last = a.speed_last<10
				? Math.round(100*a.speed_last)/100
				: Math.round(a.speed_last);

			$remaining.innerHTML = (a.est_min ? a.est_min + ' ' + (a.est_min>1 ? $Minutes : $Minute) : '') + ' '
				+ (a.est_sec || !a.est_min ? a.est_sec + ' ' + (a.est_sec>1 ? $Secondes : $Seconde) : '') + ' '
				+ (a.est_min + a.est_sec > 1 ? $Remainings : $Remaining) +' '+ $At +' '+ a.speed_last + ' Ko/s';

			$detail.innerHTML = $bytes(a.bytes_uploaded) + ' / ' + $bytes(a.bytes_total) + ' (' + Math.round(100*a.bytes_uploaded/a.bytes_total) + '%)';

			if (!$bytes_total) $bytes_total = a.bytes_total;
			setTimeout("$QJsrs.push({id:UPID}, $showProgress)", 700);
		}
		else
		{
			$sending.innerHTML = $Done;
			for (; $i < $maxWidth; $i += $unitWidth) $html += $unitHtml;
			$progress.innerHTML = $html;

			$remaining.innerHTML = '';
			$detail.innerHTML = '100%';

			setTimeout('close()', 3000);
		}
	}

	addOnload(function()
	{
		$sending = document.getElementById('sending');
		$progress = document.getElementById('progress');
		$remaining = document.getElementById('remaining');
		$detail = document.getElementById('detail');

		$unitWidth = document.getElementById('unit').offsetWidth;
		$maxWidth  = $progress.offsetWidth;
		$unitHtml = $progress.innerHTML;

		$QJsrs = new QJsrs('QJsrs/upload');
		$bytes_total = 0;

		if (window.ScriptEngine) document.getElementById('b').background = '';

		setTimeout("$QJsrs.push({id:UPID}, $showProgress)", 1400);
	});
}
