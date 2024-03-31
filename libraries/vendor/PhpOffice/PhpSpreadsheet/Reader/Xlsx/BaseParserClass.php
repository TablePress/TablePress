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
			$value = ($value instanceof Stringable) ? ((string) $value) : 'true';
		}

		if (is_numeric($value)) {
			return (bool) $value;
		}

		return $value === 'true' || $value === 'TRUE';
	}
}
