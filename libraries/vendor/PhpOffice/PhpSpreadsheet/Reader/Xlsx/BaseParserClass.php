<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Reader\Xlsx;

use Stringable;

class BaseParserClass
{
	/**
	 * @param mixed $value
	 */
	protected static function boolean($value): bool
	{
		if (is_object($value)) {
			$value = ((is_object($value) && method_exists($value, '__toString'))) ? ((string) $value) : 'true';
		}

		if (is_numeric($value)) {
			return (bool) $value;
		}

		return $value === 'true' || $value === 'TRUE';
	}
}
