<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\TextData;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Exception as CalcExp;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Search
{
	use ArrayEnabled;

	/**
	 * FIND (case sensitive search).
	 *
	 * @param mixed $needle The string to look for
	 *                         Or can be an array of values
	 * @param mixed $haystack The string in which to look
	 *                         Or can be an array of values
	 * @param mixed $offset Integer offset within $haystack to start searching from
	 *                         Or can be an array of values
	 *
	 * @return array<mixed>|int|string The offset where the first occurrence of needle was found in the haystack
	 *         If an array of values is passed for the $value or $chars arguments, then the returned result
	 *            will also be an array with matching dimensions
	 */
	public static function sensitive($needle, $haystack, $offset = 1)
	{
		if (is_array($needle) || is_array($haystack) || is_array($offset)) {
			return self::evaluateArrayArguments([self::class, __FUNCTION__], $needle, $haystack, $offset);
		}

		try {
			$needle = Helpers::extractString($needle, true);
			$haystack = Helpers::extractString($haystack, true);
			$offset = Helpers::extractInt($offset, 1, 0, true);
		} catch (CalcExp $e) {
			return $e->getMessage();
		}

		if (StringHelper::countCharacters($haystack) >= $offset) {
			if (StringHelper::countCharacters($needle) === 0) {
				return $offset;
			}

			$pos = mb_strpos($haystack, $needle, --$offset, 'UTF-8');
			if ($pos !== false) {
				return ++$pos;
			}
		}

		return ExcelError::VALUE();
	}

	/**
	 * SEARCH (case insensitive search).
	 *
	 * @param mixed $needle The string to look for
	 *                         Or can be an array of values
	 * @param mixed $haystack The string in which to look
	 *                         Or can be an array of values
	 * @param mixed $offset Integer offset within $haystack to start searching from
	 *                         Or can be an array of values
	 *
	 * @return array<mixed>|int|string The offset where the first occurrence of needle was found in the haystack
	 *         If an array of values is passed for the $value or $chars arguments, then the returned result
	 *            will also be an array with matching dimensions
	 */
	public static function insensitive($needle, $haystack, $offset = 1)
	{
		if (is_array($needle) || is_array($haystack) || is_array($offset)) {
			return self::evaluateArrayArguments([self::class, __FUNCTION__], $needle, $haystack, $offset);
		}

		try {
			$needle = Helpers::extractString($needle, true);
			$haystack = Helpers::extractString($haystack, true);
			$offset = Helpers::extractInt($offset, 1, 0, true);
		} catch (CalcExp $e) {
			return $e->getMessage();
		}

		if (StringHelper::countCharacters($haystack) >= $offset) {
			if (StringHelper::countCharacters($needle) === 0) {
				return $offset;
			}

			$pos = mb_stripos($haystack, $needle, --$offset, 'UTF-8');
			if ($pos !== false) {
				return ++$pos;
			}
		}

		return ExcelError::VALUE();
	}
}
