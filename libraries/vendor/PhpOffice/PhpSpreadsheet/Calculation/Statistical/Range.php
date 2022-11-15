<?php

// Range function (Specific to TablePress).

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Functions;

class Range
{
	/**
	 * Range (Specific to TablePress).
	 *
	 * Calculates the range of numbers, which is the difference of the maximum minus the minimum of the numbers.
	 *
	 * @param mixed $args Data values.
	 * @return float Range of the values (max - min).
	 */
	public static function RANGE( ...$args ) {
		return Maximum::max( ...$args ) - Minimum::min( ...$args );
	}
}
