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


class extends loop
{
	protected $loop;
	protected $freqKey;
	protected $sizeKey;
	protected $range;

	protected $histo;
	protected $min;
	protected $max;

	protected $histoCumule = array();
	protected $dynamic;

	function __construct($loop, $freqKey, $sizeKey, $range)
	{
		$this->loop = $loop;
		$this->freqKey = $freqKey;
		$this->sizeKey = $sizeKey;
		$this->range = $range;
	}

	function setHisto($histo, $min, $max)
	{
		$this->histo = $histo;
		$this->min = $min;
		$this->max = $max;
	}

	protected function prepare()
	{
		$loop = $this->loop;

		if (!$this->histoCumule)
		{
			$histo =& $this->histo;
			$min =& $this->min;
			$max =& $this->max;

			if (!isset($histo))
			{
				$histo = array();
				$min = -1;
				$max = 0;

				while ($a = $loop->loop())
				{
					$a = $a->{$this->freqKey};

					if (isset($histo[$a])) ++$histo[$a];
					else $histo[$a] = 1;

					$min = $min < 0 ? $a : min($min, $a);
					$max = max($max, $a);
				}
			}

			$histoCumule = array( $min - 1 => 0 );
			$dynamic = $max - $min;

			if ($dynamic > 0)
			{
				$histo[$min] = 0;

				do $histoCumule[$min] = $histoCumule[$min - 1] + (isset($histo[$min]) ? $histo[$min] : 0);
				while (++$min <= $max);

				$this->dynamic = 1 / ($histoCumule[$max - 1] + $histoCumule[$max] + 1);
			}
			else $this->dynamic = 0;

			$this->histoCumule =& $histoCumule;

			unset($this->histo);
		}

		return $loop->getLength();
	}

	protected function next()
	{
		$a = $this->loop->loop();

		if ($a)
		{
			$b = $a->{$this->freqKey};
			$a->{$this->sizeKey} = $this->dynamic
				? intval($this->range * $this->dynamic * ($this->histoCumule[$b-1] + $this->histoCumule[$b]))
				: $this->range;
		}

		return $a;
	}
}