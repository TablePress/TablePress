<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\Engineering;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Exception;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Functions;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\StringHelper;

abstract class ConvertBase
{
	use ArrayEnabled;

	/**
				 * @param mixed $value
				 */
				protected static function validateValue($value): string
	{
		if (is_bool($value)) {
			if (Functions::getCompatibilityMode() !== Functions::COMPATIBILITY_OPENOFFICE) {
				throw new Exception(ExcelError::VALUE());
			}
			$value = (int) $value;
		}

		if (is_numeric($value)) {
			if (Functions::getCompatibilityMode() == Functions::COMPATIBILITY_GNUMERIC) {
				$value = floor((float) $value);
			}
		}

		return strtoupper(StringHelper::convertToString($value));
	}

	/**
				 * @param mixed $places
				 */
				protected static function validatePlaces($places = null): ?int
	{
		if ($places === null) {
			return $places;
		}

		if (is_numeric($places)) {
			if ($places < 0 || $places > 10) {
				throw new Exception(ExcelError::NAN());
			}

			return (int) $places;
		}

		throw new Exception(ExcelError::VALUE());
	}

	/**
	 * Formats a number base string value with leading zeroes.
	 *
	 * @param string $value The "number" to pad
	 * @param ?int $places The length that we want to pad this value
	 *
	 * @return string The padded "number"
	 */
	protected static function nbrConversionFormat(string $value, ?int $places): string
	{
		if ($places !== null) {
			if (strlen($value) <= $places) {
				return substr(str_pad($value, $places, '0', STR_PAD_LEFT), -10);
			}

			return ExcelError::NAN();
		}

		return substr($value, -10);
	}
}
