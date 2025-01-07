<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Exception;

class Absolute
{
	use ArrayEnabled;

	/**
	 * ABS.
	 *
	 * Returns the result of builtin function abs after validating args.
	 *
	 * @param mixed $number Should be numeric, or can be an array of numbers
	 *
	 * @return array|float|int|string rounded number
	 *         If an array of numbers is passed as the argument, then the returned result will also be an array
	 *            with the same dimensions
	 */
	public static function evaluate($number)
	{
		if (is_array($number)) {
			return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $number);
		}

		try {
			$number = Helpers::validateNumericNullBool($number);
		} catch (Exception $e) {
			return $e->getMessage();
		}

		return abs($number);
	}
}
