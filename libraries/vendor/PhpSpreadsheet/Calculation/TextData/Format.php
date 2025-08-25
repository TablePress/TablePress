<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\TextData;

use TablePress\Composer\Pcre\Preg;
use DateTimeInterface;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Exception as CalcExp;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Functions;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information\ErrorValue;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\MathTrig;
use TablePress\PhpOffice\PhpSpreadsheet\RichText\RichText;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\Date;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use TablePress\PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Format
{
	use ArrayEnabled;

	/**
	 * DOLLAR.
	 *
	 * This function converts a number to text using currency format, with the decimals rounded to the specified place.
	 * The format used is $#,##0.00_);($#,##0.00)..
	 *
	 * @param mixed $value The value to format
	 *                         Or can be an array of values
	 * @param mixed $decimals The number of digits to display to the right of the decimal point (as an integer).
	 *                            If decimals is negative, number is rounded to the left of the decimal point.
	 *                            If you omit decimals, it is assumed to be 2
	 *                         Or can be an array of values
	 *
	 * @return array<mixed>|string If an array of values is passed for either of the arguments, then the returned result
	 *            will also be an array with matching dimensions
	 */
	public static function DOLLAR($value = 0, $decimals = 2)
	{
		if (is_array($value) || is_array($decimals)) {
			return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $decimals);
		}

		try {
			$value = Helpers::extractFloat($value);
			$decimals = Helpers::extractInt($decimals, -100, 0, true);
		} catch (CalcExp $e) {
			return $e->getMessage();
		}

		$mask = '$#,##0';
		if ($decimals > 0) {
			$mask .= '.' . str_repeat('0', $decimals);
		} else {
			$round = 10 ** abs($decimals);
			if ($value < 0) {
				$round = 0 - $round;
			}
			/** @var float|int|string */
			$value = MathTrig\Round::multiple($value, $round);
		}
		$mask = "{$mask};-{$mask}";

		return NumberFormat::toFormattedString($value, $mask);
	}

	/**
	 * FIXED.
	 *
	 * @param mixed $value The value to format
	 *                         Or can be an array of values
	 * @param mixed $decimals Integer value for the number of decimal places that should be formatted
	 *                         Or can be an array of values
	 * @param mixed $noCommas Boolean value indicating whether the value should have thousands separators or not
	 *                         Or can be an array of values
	 *
	 * @return array<mixed>|string If an array of values is passed for either of the arguments, then the returned result
	 *            will also be an array with matching dimensions
	 */
	public static function FIXEDFORMAT($value, $decimals = 2, $noCommas = false)
	{
		if (is_array($value) || is_array($decimals) || is_array($noCommas)) {
			return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $decimals, $noCommas);
		}

		try {
			$value = Helpers::extractFloat($value);
			$decimals = Helpers::extractInt($decimals, -100, 0, true);
		} catch (CalcExp $e) {
			return $e->getMessage();
		}

		$valueResult = round($value, $decimals);
		if ($decimals < 0) {
			$decimals = 0;
		}
		if ($noCommas === false) {
			$valueResult = number_format(
				$valueResult,
				$decimals,
				StringHelper::getDecimalSeparator(),
				StringHelper::getThousandsSeparator()
			);
		}

		return (string) $valueResult;
	}

	/**
	 * TEXT.
	 *
	 * @param mixed $value The value to format
	 *                         Or can be an array of values
	 * @param mixed $format A string with the Format mask that should be used
	 *                         Or can be an array of values
	 *
	 * @return array<mixed>|string If an array of values is passed for either of the arguments, then the returned result
	 *            will also be an array with matching dimensions
	 */
	public static function TEXTFORMAT($value, $format)
	{
		if (is_array($value) || is_array($format)) {
			return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $format);
		}

		try {
			$value = Helpers::extractString($value, true);
			$format = Helpers::extractString($format, true);
		} catch (CalcExp $e) {
			return $e->getMessage();
		}

		$format = (string) NumberFormat::convertSystemFormats($format);

		if (!is_numeric($value) && Date::isDateTimeFormatCode($format) && !Preg::isMatch('/^\s*\d+(\s+\d+)+\s*$/', $value)) {
			$value1 = DateTimeExcel\DateValue::fromString($value);
			$value2 = DateTimeExcel\TimeValue::fromString($value);
			/** @var float|int|string */
			$value = (is_numeric($value1) && is_numeric($value2)) ? ($value1 + $value2) : (is_numeric($value1) ? $value1 : (is_numeric($value2) ? $value2 : $value));
		}

		return (string) NumberFormat::toFormattedString($value, $format);
	}

	/**
				 * @param mixed $value Value to check
				 * @return mixed
				 */
				private static function convertValue($value, bool $spacesMeanZero = false)
	{
		$value = $value ?? 0;
		if (is_bool($value)) {
			if (Functions::getCompatibilityMode() === Functions::COMPATIBILITY_OPENOFFICE) {
				$value = (int) $value;
			} else {
				throw new CalcExp(ExcelError::VALUE());
			}
		}
		if (is_string($value)) {
			$value = trim($value);
			if (ErrorValue::isError($value, true)) {
				throw new CalcExp($value);
			}
			if ($spacesMeanZero && $value === '') {
				$value = 0;
			}
		}

		return $value;
	}

	/**
	 * VALUE.
	 *
	 * @param mixed $value Value to check
	 *                         Or can be an array of values
	 *
	 * @return array<mixed>|DateTimeInterface|float|int|string A string if arguments are invalid
	 *         If an array of values is passed for the argument, then the returned result
	 *            will also be an array with matching dimensions
	 */
	public static function VALUE($value = '')
	{
		if (is_array($value)) {
			return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
		}

		try {
			$value = self::convertValue($value);
		} catch (CalcExp $e) {
			return $e->getMessage();
		}
		if (!is_numeric($value)) {
			$value = StringHelper::convertToString($value);
			$numberValue = str_replace(
				StringHelper::getThousandsSeparator(),
				'',
				trim($value, " \t\n\r\0\x0B" . StringHelper::getCurrencyCode())
			);
			if ($numberValue === '') {
				return ExcelError::VALUE();
			}
			if (is_numeric($numberValue)) {
				return (float) $numberValue;
			}

			$dateSetting = Functions::getReturnDateType();
			Functions::setReturnDateType(Functions::RETURNDATE_EXCEL);

			if (str_contains($value, ':')) {
				$timeValue = Functions::scalar(DateTimeExcel\TimeValue::fromString($value));
				if ($timeValue !== ExcelError::VALUE()) {
					Functions::setReturnDateType($dateSetting);

					return $timeValue; //* @phpstan-ignore-line
				}
			}
			$dateValue = Functions::scalar(DateTimeExcel\DateValue::fromString($value));
			if ($dateValue !== ExcelError::VALUE()) {
				Functions::setReturnDateType($dateSetting);

				return $dateValue; //* @phpstan-ignore-line
			}
			Functions::setReturnDateType($dateSetting);

			return ExcelError::VALUE();
		}

		return (float) $value;
	}

	/**
				 * VALUETOTEXT.
				 *
				 * @param mixed $value The value to format
				 *                         Or can be an array of values
				 *
				 * @return array<mixed>|string If an array of values is passed for either of the arguments, then the returned result
				 *            will also be an array with matching dimensions
				 * @param mixed $format
				 */
				public static function valueToText($value, $format = false)
	{
		if (is_array($value) || is_array($format)) {
			return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $format);
		}

		$format = (bool) $format;

		if (is_object($value) && $value instanceof RichText) {
			$value = $value->getPlainText();
		}
		if (is_string($value)) {
			$value = ($format === true) ? StringHelper::convertToString(Calculation::wrapResult($value)) : $value;
			$value = str_replace("\n", '', $value);
		} elseif (is_bool($value)) {
			$value = Calculation::getLocaleBoolean($value ? 'TRUE' : 'FALSE');
		}

		return StringHelper::convertToString($value);
	}

	/**
				 * @param mixed $decimalSeparator
				 */
				private static function getDecimalSeparator($decimalSeparator): string
	{
		return empty($decimalSeparator) ? StringHelper::getDecimalSeparator() : StringHelper::convertToString($decimalSeparator);
	}

	/**
				 * @param mixed $groupSeparator
				 */
				private static function getGroupSeparator($groupSeparator): string
	{
		return empty($groupSeparator) ? StringHelper::getThousandsSeparator() : StringHelper::convertToString($groupSeparator);
	}

	/**
	 * NUMBERVALUE.
	 *
	 * @param mixed $value The value to format
	 *                         Or can be an array of values
	 * @param mixed $decimalSeparator A string with the decimal separator to use, defaults to locale defined value
	 *                         Or can be an array of values
	 * @param mixed $groupSeparator A string with the group/thousands separator to use, defaults to locale defined value
	 *                         Or can be an array of values
	 *
	 * @return array<mixed>|float|string
	 */
	public static function NUMBERVALUE($value = '', $decimalSeparator = null, $groupSeparator = null)
	{
		if (is_array($value) || is_array($decimalSeparator) || is_array($groupSeparator)) {
			return self::evaluateArrayArguments([self::class, __FUNCTION__], $value, $decimalSeparator, $groupSeparator);
		}

		try {
			$value = self::convertValue($value, true);
			$decimalSeparator = self::getDecimalSeparator($decimalSeparator);
			$groupSeparator = self::getGroupSeparator($groupSeparator);
		} catch (CalcExp $e) {
			return $e->getMessage();
		}

		/** @var null|array<scalar>|scalar $value */
		if (!is_array($value) && !is_numeric($value)) {
			$value = StringHelper::convertToString($value);
			$decimalPositions = Preg::matchAllWithOffsets('/' . preg_quote($decimalSeparator, '/') . '/', $value, $matches);
			if ($decimalPositions > 1) {
				return ExcelError::VALUE();
			}
			$decimalOffset = array_pop($matches[0])[1] ?? null;
			if ($decimalOffset === null || strpos($value, $groupSeparator, $decimalOffset) !== false) {
				return ExcelError::VALUE();
			}

			$value = str_replace([$groupSeparator, $decimalSeparator], ['', '.'], $value);

			// Handle the special case of trailing % signs
			$percentageString = rtrim($value, '%');
			if (!is_numeric($percentageString)) {
				return ExcelError::VALUE();
			}

			$percentageAdjustment = strlen($value) - strlen($percentageString);
			if ($percentageAdjustment) {
				$value = (float) $percentageString;
				$value /= 10 ** ($percentageAdjustment * 2);
			}
		}

		return is_array($value) ? ExcelError::VALUE() : (float) $value;
	}

	/**
	 * NUMBER_FORMAT (Specific to TablePress).
	 *
	 * Formats a number with the . (period) as the decimal separator and the , (comma) as the thousands separator, rounded to a precision.
	 *
	 * The is a common number format in English-language regions.
	 *
	 * @param mixed $value    The value to format.
	 * @param mixed $decimals Optional. Integer value for the number of decimal places that should be formatted. Default 0.
	 * @return string Formatted number.
	 */
	public static function NUMBER_FORMAT( $value, $decimals = 0 ) {
		$current_decimal_separator = StringHelper::getDecimalSeparator();
		$current_thousands_separator = StringHelper::getThousandsSeparator();
		StringHelper::setDecimalSeparator( '.' );
		StringHelper::setThousandsSeparator( ',' );
		$result = self::FIXEDFORMAT( $value, $decimals, false );
		StringHelper::setDecimalSeparator( $current_decimal_separator );
		StringHelper::setThousandsSeparator( $current_thousands_separator );
		return $result;
	}

	/**
	 * NUMBER_FORMAT_EU (Specific to TablePress).
	 *
	 * Formats a number with the , (comma) as the decimal separator and the . (period) as the thousands separator, rounded to a precision.
	 *
	 * The is a common number format in non-English-language regions, mainly in Europe.
	 *
	 * @param mixed $value    The value to format.
	 * @param mixed $decimals Optional. Integer value for the number of decimal places that should be formatted. Default 0.
	 * @return string Formatted number.
	 */
	public static function NUMBER_FORMAT_EU( $value, $decimals = 0 ) {
		$current_decimal_separator = StringHelper::getDecimalSeparator();
		$current_thousands_separator = StringHelper::getThousandsSeparator();
		StringHelper::setDecimalSeparator( ',' );
		StringHelper::setThousandsSeparator( '.' );
		$result = self::FIXEDFORMAT( $value, $decimals, false );
		StringHelper::setDecimalSeparator( $current_decimal_separator );
		StringHelper::setThousandsSeparator( $current_thousands_separator );
		return $result;
	}
}
