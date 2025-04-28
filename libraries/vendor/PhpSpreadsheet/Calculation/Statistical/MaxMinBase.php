<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\Statistical;

abstract class MaxMinBase
{
	/**
				 * @param int|float|string|bool $value
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
}
