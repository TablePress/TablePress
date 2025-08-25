<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\MathTrig;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Exception;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Functions;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Ceiling
{
	use ArrayEnabled;

	/**
	 * CEILING.
	 *
	 * Returns number rounded up, away from zero, to the nearest multiple of significance.
	 *        For example, if you want to avoid using pennies in your prices and your product is
	 *        priced at $4.42, use the formula =CEILING(4.42,0.05) to round prices up to the
	 *        nearest nickel.
	 *
	 * Excel Function:
	 *        CEILING(number[,significance])
	 *
	 * @param array<mixed>|float $number the number you want the ceiling
	 *                      Or can be an array of values
	 * @param array<mixed>|float $significance the multiple to which you want to round
	 *                      Or can be an array of values
	 *
	 * @return array<mixed>|float|string Rounded Number, or a string containing an error
	 *         If an array of numbers is passed as an argument, then the returned result will also be an array
	 *            with the same dimensions
	 */
	public static function ceiling($number, $significance = null)
	{
		if (is_array($number) || is_array($significance)) {
			return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $significance);
		}

		/*
		// Allow one argument only, for compatibility with Google Sheets, when importing an Excel file.
		if ($significance === null) {
			self::floorCheck1Arg();
		}
		*/

		try {
			$number = Helpers::validateNumericNullBool($number);
			$significance = Helpers::validateNumericNullSubstitution($significance, ($number < 0) ? -1 : 1);
		} catch (Exception $e) {
			return $e->getMessage();
		}

		return self::argumentsOk((float) $number, (float) $significance);
	}

	/**
	 * CEILING.MATH.
	 *
	 * Round a number down to the nearest integer or to the nearest multiple of significance.
	 *
	 * Excel Function:
	 *        CEILING.MATH(number[,significance[,mode]])
	 *
	 * @param mixed $number Number to round
	 *                      Or can be an array of values
	 * @param mixed $significance Significance
	 *                      Or can be an array of values
	 * @param array<mixed>|int $mode direction to round negative numbers
	 *                      Or can be an array of values
	 *
	 * @return array<mixed>|float|string Rounded Number, or a string containing an error
	 *         If an array of numbers is passed as an argument, then the returned result will also be an array
	 *            with the same dimensions
	 */
	public static function math($number, $significance = null, $mode = 0, bool $checkSigns = false)
	{
		if (is_array($number) || is_array($significance) || is_array($mode)) {
			return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $significance, $mode);
		}

		try {
			$number = Helpers::validateNumericNullBool($number);
			$significance = Helpers::validateNumericNullSubstitution($significance, ($number < 0) ? -1 : 1);
			$mode = Helpers::validateNumericNullSubstitution($mode, null);
		} catch (Exception $e) {
			return $e->getMessage();
		}

		if (empty($significance * $number)) {
			return 0.0;
		}
		if ($checkSigns) {
			if (($number > 0 && $significance < 0) || ($number < 0 && $significance > 0)) {
				return ExcelError::NAN();
			}
		}
		if (self::ceilingMathTest((float) $significance, (float) $number, (int) $mode)) {
			return floor($number / $significance) * $significance;
		}

		return ceil($number / $significance) * $significance;
	}

	/**
	 * CEILING.PRECISE.
	 *
	 * Rounds number up, away from zero, to the nearest multiple of significance.
	 *
	 * Excel Function:
	 *        CEILING.PRECISE(number[,significance])
	 *
	 * @param mixed $number the number you want to round
	 *                      Or can be an array of values
	 * @param array<mixed>|float $significance the multiple to which you want to round
	 *                      Or can be an array of values
	 *
	 * @return array<mixed>|float|string Rounded Number, or a string containing an error
	 *         If an array of numbers is passed as an argument, then the returned result will also be an array
	 *            with the same dimensions
	 */
	public static function precise($number, $significance = 1)
	{
		if (is_array($number) || is_array($significance)) {
			return self::evaluateArrayArguments([self::class, __FUNCTION__], $number, $significance);
		}

		try {
			$number = Helpers::validateNumericNullBool($number);
			$significance = Helpers::validateNumericNullSubstitution($significance, null);
		} catch (Exception $e) {
			return $e->getMessage();
		}

		if (!$significance) {
			return 0.0;
		}
		$result = $number / abs($significance);

		return ceil($result) * $significance * (($significance < 0) ? -1 : 1);
	}

	/**
	 * CEILING.ODS, pseudo-function - CEILING as implemented in ODS.
	 *
	 * ODS Function (theoretical):
	 *        CEILING.ODS(number[,significance[,mode]])
	 *
	 * @param mixed $number Number to round
	 * @param mixed $significance Significance
	 * @param array<mixed>|int $mode direction to round negative numbers
	 *
	 * @return array<mixed>|float|string Rounded Number, or a string containing an error
	 */
	public static function mathOds($number, $significance = null, $mode = 0)
	{
		return self::math($number, $significance, $mode, true);
	}

	/**
	 * Let CEILINGMATH complexity pass Scrutinizer.
	 */
	private static function ceilingMathTest(float $significance, float $number, int $mode): bool
	{
		return ($significance < 0) || ($number < 0 && !empty($mode));
	}

	/**
				 * Avoid Scrutinizer problems concerning complexity.
				 * @return float|string
				 */
				private static function argumentsOk(float $number, float $significance)
	{
		if (empty($number * $significance)) {
			return 0.0;
		}
		$signSig = Helpers::returnSign($significance);
		$signNum = Helpers::returnSign($number);
		if (
			($signSig === 1 && ($signNum === 1 || Functions::getCompatibilityMode() !== Functions::COMPATIBILITY_GNUMERIC))
			|| ($signSig === -1 && $signNum === -1)
		) {
			return ceil($number / $significance) * $significance;
		}

		return ExcelError::NAN();
	}

	private static function floorCheck1Arg(): void
	{
		$compatibility = Functions::getCompatibilityMode();
		if ($compatibility === Functions::COMPATIBILITY_EXCEL) {
			throw new Exception('Excel requires 2 arguments for CEILING');
		}
	}
}
