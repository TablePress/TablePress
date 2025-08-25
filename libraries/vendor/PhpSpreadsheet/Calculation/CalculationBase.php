<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation;

class CalculationBase
{
	/**
	 * Get a list of all implemented functions as an array of function objects.
	 *
	 * @return array<string, array{category: string, functionCall: string|string[], argumentCount: string, passCellReference?: bool, passByReference?: bool[], custom?: bool}>
	 */
	public static function getFunctions(): array
	{
		return FunctionArray::$phpSpreadsheetFunctions;
	}

	/**
	 * Get address of list of all implemented functions as an array of function objects.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected static function &getFunctionsAddress(): array
	{
		return FunctionArray::$phpSpreadsheetFunctions;
	}

	/**
	 * @param array{category: string, functionCall: string|string[], argumentCount: string, passCellReference?: bool, passByReference?: bool[], custom?: bool} $value
	 */
	public static function addFunction(string $key, array $value): bool
	{
		$key = strtoupper($key);
		if (
			array_key_exists($key, FunctionArray::$phpSpreadsheetFunctions)
			&& !self::isDummy($key)
		) {
			return false;
		}
		$value['custom'] = true;
		FunctionArray::$phpSpreadsheetFunctions[$key] = $value;

		return true;
	}

	private static function isDummy(string $key): bool
	{
		// key is already known to exist
		$functionCall = FunctionArray::$phpSpreadsheetFunctions[$key]['functionCall'] ?? null;
		if (!is_array($functionCall)) {
			return false;
		}
		if (($functionCall[1] ?? '') !== 'DUMMY') {
			return false;
		}

		return true;
	}

	public static function removeFunction(string $key): bool
	{
		$key = strtoupper($key);
		if (array_key_exists($key, FunctionArray::$phpSpreadsheetFunctions)) {
			if (FunctionArray::$phpSpreadsheetFunctions[$key]['custom'] ?? false) {
				unset(FunctionArray::$phpSpreadsheetFunctions[$key]);

				return true;
			}
		}

		return false;
	}

	/**
	 * Registers custom functions and aliases that TablePress uses.
	 *
	 * These functions don't exist in Excel and need to be aliases or replaced by a custom implementation, to maintain backward compatibility.
	 * These functions are deprecated in TablePress and should be replaced with their corresponding function!
	 *
	 * For functions with an underscore (_) in their name, the Calculation::CALCULATION_REGEXP_FUNCTION regexp has been adjusted.
	 */
	protected function register_tablepress_aliases_and_custom_functions() {
		// Trigonometric ARC functions only have an A prefix in Excel.
		FunctionArray::$phpSpreadsheetFunctions['ARCCOS']  = FunctionArray::$phpSpreadsheetFunctions['ACOS'];
		FunctionArray::$phpSpreadsheetFunctions['ARCCOSH'] = FunctionArray::$phpSpreadsheetFunctions['ACOSH'];
		FunctionArray::$phpSpreadsheetFunctions['ARCCOT']  = FunctionArray::$phpSpreadsheetFunctions['ACOT'];
		FunctionArray::$phpSpreadsheetFunctions['ARCCOTH'] = FunctionArray::$phpSpreadsheetFunctions['ACOTH'];
		FunctionArray::$phpSpreadsheetFunctions['ARCSIN']  = FunctionArray::$phpSpreadsheetFunctions['ASIN'];
		FunctionArray::$phpSpreadsheetFunctions['ARCSINH'] = FunctionArray::$phpSpreadsheetFunctions['ASINH'];
		FunctionArray::$phpSpreadsheetFunctions['ARCTAN']  = FunctionArray::$phpSpreadsheetFunctions['ATAN'];
		FunctionArray::$phpSpreadsheetFunctions['ARCTAN2'] = FunctionArray::$phpSpreadsheetFunctions['ATAN2'];
		FunctionArray::$phpSpreadsheetFunctions['ARCTANH'] = FunctionArray::$phpSpreadsheetFunctions['ATANH'];

		// Aliases for functions with different names in Excel.
		FunctionArray::$phpSpreadsheetFunctions['MEAN']       = FunctionArray::$phpSpreadsheetFunctions['AVERAGE'];
		FunctionArray::$phpSpreadsheetFunctions['CEIL']       = FunctionArray::$phpSpreadsheetFunctions['CEILING'];
		FunctionArray::$phpSpreadsheetFunctions['RAND_INT']   = FunctionArray::$phpSpreadsheetFunctions['RANDBETWEEN'];
		FunctionArray::$phpSpreadsheetFunctions['RAND_FLOAT'] = FunctionArray::$phpSpreadsheetFunctions['RAND'];

		// Custom functions for which there is no corresponding function in Excel.
		FunctionArray::$phpSpreadsheetFunctions['NUMBER_FORMAT'] = array(
			'category'      => Category::CATEGORY_TEXT_AND_DATA,
			'functionCall'  => [TextData\Format::class, 'NUMBER_FORMAT'],
			'argumentCount' => '1,2',
		);
		FunctionArray::$phpSpreadsheetFunctions['NUMBER_FORMAT_EU'] = array(
			'category'      => Category::CATEGORY_TEXT_AND_DATA,
			'functionCall'  => [TextData\Format::class, 'NUMBER_FORMAT_EU'],
			'argumentCount' => '1,2',
		);
		FunctionArray::$phpSpreadsheetFunctions['RANGE'] = array(
			'category'      => Category::CATEGORY_STATISTICAL,
			'functionCall' => [Statistical\Range::class, 'RANGE'],
			'argumentCount' => '1+',
		);
	}
}
