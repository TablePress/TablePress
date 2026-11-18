<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\Statistical;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Functions;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information\ErrorValue;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

abstract class MaxMinBase
{
	/**
				 * @param bool|float|int|string $value
				 * @return float|int
				 */
				protected static function datatypeAdjustmentAllowStrings($value)
	{
		if (is_bool($value)) {
			return (int) $value;
		} elseif (is_string($value)) {
			return 0;
		}

		return $value;
	}

	/**
	 * As in Excel (and SUM), a logical value or a text representation of a number
	 * typed directly into the argument list counts, and any other text typed there is #VALUE!.
	 * References and arrays are left unchanged, so that their non-numeric values are ignored.
	 *
	 * @param mixed[] $args
	 *
	 * @return mixed[]
	 */
	protected static function datatypeAdjustmentLiteralArguments(array $args): array
	{
		foreach ($args as $index => $arg) {
			if (!is_array($arg) && !Functions::isCellValue($index)) {
				$args[$index] = self::datatypeAdjustmentLiteral($arg);
			}
		}

		return $args;
	}

	/**
				 * @param mixed $value
				 * @return mixed
				 */
				private static function datatypeAdjustmentLiteral($value)
	{
		if (is_bool($value)) {
			return (int) $value;
		}
		if (is_string($value) && !ErrorValue::isError($value, true)) {
			return is_numeric($value) ? 0 + $value : ExcelError::VALUE();
		}

		return $value;
	}
}
