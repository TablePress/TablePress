<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation;

use TablePress\Composer\Pcre\Preg;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information\ErrorValue;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class BinaryComparison
{
	/**
	 * Epsilon Precision used for comparisons in calculations.
	 */
	private const DELTA = 0.1e-12;

	/**
	 * Compare two strings in the same way as strcmp() except that lowercase come before uppercase letters.
	 *
	 * @param mixed $str1 First string value for the comparison, expect ?string
	 * @param mixed $str2 Second string value for the comparison, expect ?string
	 */
	private static function strcmpLowercaseFirst($str1, $str2): int
	{
		$str1 = StringHelper::convertToString($str1);
		$str2 = StringHelper::convertToString($str2);
		$inversedStr1 = StringHelper::strCaseReverse($str1);
		$inversedStr2 = StringHelper::strCaseReverse($str2);

		return strcmp($inversedStr1, $inversedStr2);
	}

	/**
	 * PHP8.1 deprecates passing null to strcmp.
	 *
	 * @param mixed $str1 First string value for the comparison, expect ?string
	 * @param mixed $str2 Second string value for the comparison, expect ?string
	 */
	private static function strcmpAllowNull($str1, $str2): int
	{
		$str1 = StringHelper::convertToString($str1);
		$str2 = StringHelper::convertToString($str2);

		return strcmp($str1, $str2);
	}

	/**
				 * @return bool|string
				 * @param mixed $operand1
				 * @param mixed $operand2
				 */
				public static function compare($operand1, $operand2, string $operator)
	{
		//    Simple validate the two operands if they are string values
		if (is_string($operand1) && $operand1 > '' && $operand1[0] == Calculation::FORMULA_STRING_QUOTE) {
			$operand1 = Calculation::unwrapResult($operand1);
		}
		if (ErrorValue::isError($operand1, true)) {
			/** @var string $operand1 */
			return $operand1;
		}
		if (is_string($operand2) && $operand2 > '' && $operand2[0] == Calculation::FORMULA_STRING_QUOTE) {
			$operand2 = Calculation::unwrapResult($operand2);
		}
		if (ErrorValue::isError($operand2, true)) {
			/** @var string $operand2 */
			return $operand2;
		}

		if (is_string($operand1) && is_string($operand2) && is_numeric($operand1) && is_numeric($operand2)) {
			return self::numericStrings($operand1, $operand2, $operator);
		}
		$rank1 = self::typeRank($operand1);
		$rank2 = self::typeRank($operand2);
		if ($rank1 !== null && $rank2 !== null && $rank1 !== $rank2) {
			return self::compareTypeRanks($rank1, $rank2, $operator);
		}

		// Use case-insensitive comparison if not OpenOffice mode
		if (Functions::getCompatibilityMode() != Functions::COMPATIBILITY_OPENOFFICE) {
			if (is_string($operand1)) {
				$operand1 = StringHelper::strToUpper($operand1);
			}
			if (is_string($operand2)) {
				$operand2 = StringHelper::strToUpper($operand2);
			}
		}

		$useLowercaseFirstComparison = is_string($operand1)
			&& is_string($operand2)
			&& Functions::getCompatibilityMode() === Functions::COMPATIBILITY_OPENOFFICE;

		return self::evaluateComparison($operand1, $operand2, $operator, $useLowercaseFirstComparison);
	}

	/**
				 * Excel orders values of different types by type alone:
				 * every number is less than every text, and every text is less than FALSE and TRUE.
				 * So ="">0, ="a">1E+100 and =FALSE>"zzz" are all TRUE.
				 * LibreOffice treats booleans as numbers, so only numbers and text are ranked there.
				 * Null (an empty cell) and numeric strings keep their existing handling.
				 * @param mixed $operand
				 */
				private static function typeRank($operand): ?int
	{
		if (is_bool($operand)) {
			return Functions::getCompatibilityMode() === Functions::COMPATIBILITY_OPENOFFICE ? 0 : 2;
		}
		if (is_int($operand) || is_float($operand)) {
			return 0;
		}
		if (is_string($operand) && !is_numeric($operand)) {
			return 1;
		}

		return null;
	}

	private static function compareTypeRanks(int $rank1, int $rank2, string $operator): bool
	{
		switch ($operator) {
									case '=':
										return false;
									case '<>':
										return true;
									case '>':
									case '>=':
										return $rank1 > $rank2;
									case '<':
									case '<=':
										return $rank1 < $rank2;
									default:
										throw new Exception('Unsupported binary comparison operator');
								}
	}

	/**
				 * @param mixed $operand1
				 * @param mixed $operand2
				 */
				private static function evaluateComparison($operand1, $operand2, string $operator, bool $useLowercaseFirstComparison): bool
	{
		switch ($operator) {
									case '=':
										return self::equal($operand1, $operand2);
									case '>':
										return self::greaterThan($operand1, $operand2, $useLowercaseFirstComparison);
									case '<':
										return self::lessThan($operand1, $operand2, $useLowercaseFirstComparison);
									case '>=':
										return self::greaterThanOrEqual($operand1, $operand2, $useLowercaseFirstComparison);
									case '<=':
										return self::lessThanOrEqual($operand1, $operand2, $useLowercaseFirstComparison);
									case '<>':
										return self::notEqual($operand1, $operand2);
									default:
										throw new Exception('Unsupported binary comparison operator');
								}
	}

	/**
				 * @param mixed $operand1
				 * @param mixed $operand2
				 */
				private static function equal($operand1, $operand2): bool
	{
		if (is_numeric($operand1) && is_numeric($operand2)) {
			$result = (abs($operand1 - $operand2) < self::DELTA);
		} elseif (($operand1 === null && is_numeric($operand2)) || ($operand2 === null && is_numeric($operand1))) {
			$result = $operand1 == $operand2;
		} else {
			$result = self::strcmpAllowNull($operand1, $operand2) == 0;
		}

		return $result;
	}

	/**
				 * @param mixed $operand1
				 * @param mixed $operand2
				 */
				private static function greaterThanOrEqual($operand1, $operand2, bool $useLowercaseFirstComparison): bool
	{
		if (is_numeric($operand1) && is_numeric($operand2)) {
			$result = ((abs($operand1 - $operand2) < self::DELTA) || ($operand1 > $operand2));
		} elseif (($operand1 === null && is_numeric($operand2)) || ($operand2 === null && is_numeric($operand1))) {
			$result = $operand1 >= $operand2;
		} elseif ($useLowercaseFirstComparison) {
			$result = self::strcmpLowercaseFirst($operand1, $operand2) >= 0;
		} else {
			$result = self::strcmpAllowNull($operand1, $operand2) >= 0;
		}

		return $result;
	}

	/**
				 * @param mixed $operand1
				 * @param mixed $operand2
				 */
				private static function lessThanOrEqual($operand1, $operand2, bool $useLowercaseFirstComparison): bool
	{
		if (is_numeric($operand1) && is_numeric($operand2)) {
			$result = ((abs($operand1 - $operand2) < self::DELTA) || ($operand1 < $operand2));
		} elseif (($operand1 === null && is_numeric($operand2)) || ($operand2 === null && is_numeric($operand1))) {
			$result = $operand1 <= $operand2;
		} elseif ($useLowercaseFirstComparison) {
			$result = self::strcmpLowercaseFirst($operand1, $operand2) <= 0;
		} else {
			$result = self::strcmpAllowNull($operand1, $operand2) <= 0;
		}

		return $result;
	}

	/**
				 * @param mixed $operand1
				 * @param mixed $operand2
				 */
				private static function greaterThan($operand1, $operand2, bool $useLowercaseFirstComparison): bool
	{
		return self::lessThanOrEqual($operand1, $operand2, $useLowercaseFirstComparison) !== true;
	}

	/**
				 * @param mixed $operand1
				 * @param mixed $operand2
				 */
				private static function lessThan($operand1, $operand2, bool $useLowercaseFirstComparison): bool
	{
		return self::greaterThanOrEqual($operand1, $operand2, $useLowercaseFirstComparison) !== true;
	}

	/**
				 * @param mixed $operand1
				 * @param mixed $operand2
				 */
				private static function notEqual($operand1, $operand2): bool
	{
		return self::equal($operand1, $operand2) !== true;
	}

	private const EQUAL_COMPARES = ['=', '>=', '<='];
	private const LESS_COMPARES = ['<', '<=', '<>'];
	private const GREATER_COMPARES = ['>', '>=', '<>'];

	private static function numericStrings(string $a, string $b, string $operator): bool
	{
		if (Functions::getCompatibilityMode() === Functions::COMPATIBILITY_OPENOFFICE) {
			return self::numericStringsLibre($a, $b, $operator);
		}
		// special handling for numeric strings starting with -
		$a = Preg::replace('/^-/', '', $a);
		$b = Preg::replace('/^-/', '', $b);
		$comp = strcmp($a, $b);
		if ($comp === 0) {
			return in_array($operator, self::EQUAL_COMPARES, true);
		}
		if ($comp > 0) {
			return in_array($operator, self::GREATER_COMPARES, true);
		}

		return in_array($operator, self::LESS_COMPARES, true);
	}

	private static function numericStringsLibre(string $a2, string $b2, string $operator): bool
	{
		if (str_starts_with($a2, '+')) {
			if (str_starts_with($b2, '+')) {
				$comp = strcmp($a2, $b2);
			} elseif (str_starts_with($b2, '-')) {
				$comp = 1; // "+number1" is always > "-$number2"
			} else {
				$comp = -1; // "+number1" is always < "$number2" unsigned
			}
		} elseif (str_starts_with($b2, '+')) {
			// $a2 can't start with + here
			if (str_starts_with($a2, '-')) {
				$comp = -1; // "-$number1" is always < "+$number2"
			} else {
				$comp = 1; // unsigned "$number1" is always > "+$number2"
			}
		} else {
			$comp = strcmp($a2, $b2);
		}
		if ($comp === 0) {
			return in_array($operator, self::EQUAL_COMPARES, true);
		}
		if ($comp > 0) {
			return in_array($operator, self::GREATER_COMPARES, true);
		}

		return in_array($operator, self::LESS_COMPARES, true);
	}
}
