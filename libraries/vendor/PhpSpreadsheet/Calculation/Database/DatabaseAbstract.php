<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\Database;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Functions;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Internal\WildcardMatch;

abstract class DatabaseAbstract
{
	/**
				 * @param mixed[]|null|int|string $field
				 * @return float|int|string|null
				 */
				abstract public static function evaluate(array $database, $field, array $criteria);

	/**
	 * fieldExtract.
	 *
	 * Extracts the column ID to use for the data field.
	 *
	 * @param mixed[] $database The range of cells that makes up the list or database.
	 *                                        A database is a list of related data in which rows of related
	 *                                        information are records, and columns of data are fields. The
	 *                                        first row of the list contains labels for each column.
	 * @param mixed $field Indicates which column is used in the function. Enter the
	 *                                        column label enclosed between double quotation marks, such as
	 *                                        "Age" or "Yield," or a number (without quotation marks) that
	 *                                        represents the position of the column within the list: 1 for
	 *                                        the first column, 2 for the second column, and so on.
	 */
	protected static function fieldExtract(array $database, $field): ?int
	{
		/** @var ?string */
		$single = Functions::flattenSingleValue($field);
		$field = strtoupper($single ?? '');
		if ($field === '') {
			return null;
		}

		/** @var callable */
		$callable = 'strtoupper';
		/** @var non-empty-array $database */
		$fieldNames = array_map($callable, array_shift($database));
		if (is_numeric($field)) {
			$field = (int) $field - 1;
			if ($field < 0 || $field >= count($fieldNames)) {
				return null;
			}

			return $field;
		}
		$key = array_search($field, array_values($fieldNames), true);

		return ($key !== false) ? (int) $key : null;
	}

	/**
	 * filter.
	 *
	 * Parses the selection criteria, extracts the database rows that match those criteria, and
	 * returns that subset of rows.
	 *
	 * @param mixed[] $database The range of cells that makes up the list or database.
	 *                                        A database is a list of related data in which rows of related
	 *                                        information are records, and columns of data are fields. The
	 *                                        first row of the list contains labels for each column.
	 * @param mixed[] $criteria The range of cells that contains the conditions you specify.
	 *                                        You can use any range for the criteria argument, as long as it
	 *                                        includes at least one column label and at least one cell below
	 *                                        the column label in which you specify a condition for the
	 *                                        column.
	 *
	 * @return mixed[]
	 */
	protected static function filter(array $database, array $criteria): array
	{
		/** @var array */
		$fieldNames = array_shift($database);
		/** @var array */
		$criteriaNames = array_shift($criteria);

		//    Convert the criteria into a set of AND/OR conditions with [:placeholders]
		$query = self::buildQuery($criteriaNames, $criteria);

		//    Loop through each row of the database
		return self::executeQuery($database, $query, $criteriaNames, $fieldNames);
	}

	protected static function getFilteredColumn(array $database, ?int $field, array $criteria): array
	{
		//    reduce the database to a set of rows that match all the criteria
		$database = self::filter($database, $criteria);
		$defaultReturnColumnValue = ($field === null) ? 1 : null;

		//    extract an array of values for the requested column
		$columnData = [];
		/** @var array $row */
		foreach ($database as $rowKey => $row) {
			$keys = array_keys($row);
			$key = $keys[$field] ?? null;
			$columnKey = $key ?? 'A';
			$columnData[$rowKey][$columnKey] = $row[$key] ?? $defaultReturnColumnValue;
		}

		return $columnData;
	}

	private static function buildQuery(array $criteriaNames, array $criteria): string
	{
		$baseQuery = [];
		foreach ($criteria as $key => $criterion) {
			foreach ($criterion as $field => $value) {
				$criterionName = $criteriaNames[$field];
				if ($value !== null) {
					$condition = self::buildCondition($value, $criterionName);
					$baseQuery[$key][] = $condition;
				}
			}
		}

		$rowQuery = array_map(
			fn ($rowValue): string => (count($rowValue) > 1) ? 'AND(' . implode(',', $rowValue) . ')' : ($rowValue[0] ?? ''), // @phpstan-ignore-line
			$baseQuery
		);

		return (count($rowQuery) > 1) ? 'OR(' . implode(',', $rowQuery) . ')' : ($rowQuery[0] ?? '');
	}

	/**
				 * @param mixed $criterion
				 */
				private static function buildCondition($criterion, string $criterionName): string
	{
		$ifCondition = Functions::ifCondition($criterion);

		// Check for wildcard characters used in the condition
		$result = preg_match('/(?<operator>[^"]*)(?<operand>".*[*?].*")/ui', $ifCondition, $matches);
		if ($result !== 1) {
			return "[:{$criterionName}]{$ifCondition}";
		}

		$trueFalse = ($matches['operator'] !== '<>');
		$wildcard = WildcardMatch::wildcard($matches['operand']);
		$condition = "WILDCARDMATCH([:{$criterionName}],{$wildcard})";
		if ($trueFalse === false) {
			$condition = "NOT({$condition})";
		}

		return $condition;
	}

	private static function executeQuery(array $database, string $query, array $criteria, array $fields): array
	{
		foreach ($database as $dataRow => $dataValues) {
			//    Substitute actual values from the database row for our [:placeholders]
			$conditions = $query;
			foreach ($criteria as $criterion) {
				$conditions = self::processCondition($criterion, $fields, $dataValues, $conditions);
			}

			//    evaluate the criteria against the row data
			$result = Calculation::getInstance()->_calculateFormulaValue('=' . $conditions);

			//    If the row failed to meet the criteria, remove it from the database
			if ($result !== true) {
				unset($database[$dataRow]);
			}
		}

		return $database;
	}

	private static function processCondition(string $criterion, array $fields, array $dataValues, string $conditions): string
	{
		$key = array_search($criterion, $fields, true);

		$dataValue = 'NULL';
		if (is_bool($dataValues[$key])) {
			$dataValue = ($dataValues[$key]) ? 'TRUE' : 'FALSE';
		} elseif ($dataValues[$key] !== null) {
			$dataValue = $dataValues[$key];
			// escape quotes if we have a string containing quotes
			if (is_string($dataValue) && str_contains($dataValue, '"')) {
				$dataValue = str_replace('"', '""', $dataValue);
			}
			$dataValue = (is_string($dataValue)) ? Calculation::wrapResult(strtoupper($dataValue)) : $dataValue;
		}

		return str_replace('[:' . $criterion . ']', $dataValue, $conditions);
	}
}
