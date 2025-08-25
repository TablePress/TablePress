<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\TextData;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Exception as CalcExp;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class CaseConvert
{
	use ArrayEnabled;

	/**
	 * LOWERCASE.
	 *
	 * Converts a string value to upper case.
	 *
	 * @param mixed $mixedCaseValue The string value to convert to lower case
	 *                              Or can be an array of values
	 *
	 * @return array<mixed>|string If an array of values is passed as the argument, then the returned result will also be an array
	 *            with the same dimensions
	 */
	public static function lower($mixedCaseValue)
	{
		if (is_array($mixedCaseValue)) {
			return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $mixedCaseValue);
		}

		try {
			$mixedCaseValue = Helpers::extractString($mixedCaseValue, true);
		} catch (CalcExp $e) {
			return $e->getMessage();
		}

		return StringHelper::strToLower($mixedCaseValue);
	}

	/**
	 * UPPERCASE.
	 *
	 * Converts a string value to upper case.
	 *
	 * @param mixed $mixedCaseValue The string value to convert to upper case
	 *                              Or can be an array of values
	 *
	 * @return array<mixed>|string If an array of values is passed as the argument, then the returned result will also be an array
	 *            with the same dimensions
	 */
	public static function upper($mixedCaseValue)
	{
		if (is_array($mixedCaseValue)) {
			return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $mixedCaseValue);
		}

		try {
			$mixedCaseValue = Helpers::extractString($mixedCaseValue, true);
		} catch (CalcExp $e) {
			return $e->getMessage();
		}

		return StringHelper::strToUpper($mixedCaseValue);
	}

	/**
	 * PROPERCASE.
	 *
	 * Converts a string value to proper or title case.
	 *
	 * @param mixed $mixedCaseValue The string value to convert to title case
	 *                              Or can be an array of values
	 *
	 * @return array<mixed>|string If an array of values is passed as the argument, then the returned result will also be an array
	 *            with the same dimensions
	 */
	public static function proper($mixedCaseValue)
	{
		if (is_array($mixedCaseValue)) {
			return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $mixedCaseValue);
		}

		try {
			$mixedCaseValue = Helpers::extractString($mixedCaseValue, true);
		} catch (CalcExp $e) {
			return $e->getMessage();
		}

		return StringHelper::strToTitle($mixedCaseValue);
	}
}
