<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Exception;
use TablePress\PhpOffice\PhpSpreadsheet\Cell\Cell;
use TablePress\PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use TablePress\PhpOffice\PhpSpreadsheet\Style\Conditional;
use TablePress\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CellMatcher
{
	public const COMPARISON_OPERATORS = [
		Conditional::OPERATOR_EQUAL => '=',
		Conditional::OPERATOR_GREATERTHAN => '>',
		Conditional::OPERATOR_GREATERTHANOREQUAL => '>=',
		Conditional::OPERATOR_LESSTHAN => '<',
		Conditional::OPERATOR_LESSTHANOREQUAL => '<=',
		Conditional::OPERATOR_NOTEQUAL => '<>',
	];

	public const COMPARISON_RANGE_OPERATORS = [
		Conditional::OPERATOR_BETWEEN => 'IF(AND(A1>=%s,A1<=%s),TRUE,FALSE)',
		Conditional::OPERATOR_NOTBETWEEN => 'IF(AND(A1>=%s,A1<=%s),FALSE,TRUE)',
	];

	public const COMPARISON_DUPLICATES_OPERATORS = [
		Conditional::CONDITION_DUPLICATES => "COUNTIF('%s'!%s,%s)>1",
		Conditional::CONDITION_UNIQUE => "COUNTIF('%s'!%s,%s)=1",
	];

	protected Cell $cell;

	protected int $cellRow;

	protected Worksheet $worksheet;

	protected int $cellColumn;

	protected string $conditionalRange;

	protected string $referenceCell;

	protected int $referenceRow;

	protected int $referenceColumn;

	protected Calculation $engine;

	public function __construct(Cell $cell, string $conditionalRange)
	{
		$this->cell = $cell;
		$this->worksheet = $cell->getWorksheet();
		[$this->cellColumn, $this->cellRow] = Coordinate::indexesFromString($this->cell->getCoordinate());
		$this->setReferenceCellForExpressions($conditionalRange);

		$this->engine = Calculation::getInstance($this->worksheet->getParent());
	}

	protected function setReferenceCellForExpressions(string $conditionalRange): void
	{
		$conditionalRange = Coordinate::splitRange(str_replace('$', '', strtoupper($conditionalRange)));
		[$this->referenceCell] = $conditionalRange[0];

		[$this->referenceColumn, $this->referenceRow] = Coordinate::indexesFromString($this->referenceCell);

		// Convert our conditional range to an absolute conditional range, so it can be used  "pinned" in formulae
		$rangeSets = [];
		foreach ($conditionalRange as $rangeSet) {
			$absoluteRangeSet = array_map(
				[Coordinate::class, 'absoluteCoordinate'],
				$rangeSet
			);
			$rangeSets[] = implode(':', $absoluteRangeSet);
		}
		$this->conditionalRange = implode(',', $rangeSets);
	}

	public function evaluateConditional(Conditional $conditional): bool
	{
		// Some calculations may modify the stored cell; so reset it before every evaluation.
		$cellColumn = Coordinate::stringFromColumnIndex($this->cellColumn);
		$cellAddress = "{$cellColumn}{$this->cellRow}";
		$this->cell = $this->worksheet->getCell($cellAddress);

		switch ($conditional->getConditionType()) {
									case Conditional::CONDITION_CELLIS:
										return $this->processOperatorComparison($conditional);
									case Conditional::CONDITION_DUPLICATES:
									case Conditional::CONDITION_UNIQUE:
										return $this->processDuplicatesComparison($conditional);
									case Conditional::CONDITION_CONTAINSTEXT:
									case Conditional::CONDITION_NOTCONTAINSTEXT:
									case Conditional::CONDITION_BEGINSWITH:
									case Conditional::CONDITION_ENDSWITH:
									case Conditional::CONDITION_CONTAINSBLANKS:
									case Conditional::CONDITION_NOTCONTAINSBLANKS:
									case Conditional::CONDITION_CONTAINSERRORS:
									case Conditional::CONDITION_NOTCONTAINSERRORS:
									case Conditional::CONDITION_TIMEPERIOD:
									case Conditional::CONDITION_EXPRESSION:
										return $this->processExpression($conditional);
									case Conditional::CONDITION_COLORSCALE:
										return $this->processColorScale($conditional);
									default:
										return false;
								}
	}

	/**
				 * @return float|int|string
				 * @param mixed $value
				 */
				protected function wrapValue($value)
	{
		if (!is_numeric($value)) {
			if (is_bool($value)) {
				return $value ? 'TRUE' : 'FALSE';
			} elseif ($value === null) {
				return 'NULL';
			}

			return '"' . StringHelper::convertToString($value) . '"';
		}

		return $value;
	}

	/**
				 * @return float|int|string
				 */
				protected function wrapCellValue()
	{
		$this->cell = $this->worksheet->getCell([$this->cellColumn, $this->cellRow]);

		return $this->wrapValue($this->cell->getCalculatedValue());
	}

	/** @param string[] $matches
				 * @return float|int|string */
				protected function conditionCellAdjustment(array $matches)
	{
		$column = $matches[6];
		$row = $matches[7];
		if (!str_contains($column, '$')) {
			//            $column = Coordinate::stringFromColumnIndex($this->cellColumn);
			$column = Coordinate::columnIndexFromString($column);
			$column += $this->cellColumn - $this->referenceColumn;
			$column = Coordinate::stringFromColumnIndex($column);
		}

		if (!str_contains($row, '$')) {
			$row = (int) $row + $this->cellRow - $this->referenceRow;
		}

		if (!empty($matches[4])) {
			$worksheet = $this->worksheet->getParentOrThrow()->getSheetByName(trim($matches[4], "'"));
			if ($worksheet === null) {
				return $this->wrapValue(null);
			}

			return $this->wrapValue(
				$worksheet
					->getCell(str_replace('$', '', "{$column}{$row}"))
					->getCalculatedValue()
			);
		}

		return $this->wrapValue(
			$this->worksheet
				->getCell(str_replace('$', '', "{$column}{$row}"))
				->getCalculatedValue()
		);
	}

	protected function cellConditionCheck(string $condition): string
	{
		$splitCondition = explode(Calculation::FORMULA_STRING_QUOTE, $condition);
		$i = false;
		foreach ($splitCondition as &$value) {
			//    Only count/replace in alternating array entries (ie. not in quoted strings)
			$i = $i === false;
			if ($i) {
				$value = (string) preg_replace_callback(
					'/' . Calculation::CALCULATION_REGEXP_CELLREF_RELATIVE . '/i',
					[$this, 'conditionCellAdjustment'],
					$value
				);
			}
		}
		unset($value);

		//    Then rebuild the condition string to return it
		return implode(Calculation::FORMULA_STRING_QUOTE, $splitCondition);
	}

	/**
	 * @param mixed[] $conditions
	 *
	 * @return mixed[]
	 */
	protected function adjustConditionsForCellReferences(array $conditions): array
	{
		return array_map(
			[$this, 'cellConditionCheck'],
			$conditions
		);
	}

	protected function processOperatorComparison(Conditional $conditional): bool
	{
		if (array_key_exists($conditional->getOperatorType(), self::COMPARISON_RANGE_OPERATORS)) {
			return $this->processRangeOperator($conditional);
		}

		$operator = self::COMPARISON_OPERATORS[$conditional->getOperatorType()];
		$conditions = $this->adjustConditionsForCellReferences($conditional->getConditions());
		/** @var float|int|string */
		$temp1 = $this->wrapCellValue();
		/** @var scalar */
		$temp2 = array_pop($conditions);
		$expression = sprintf('%s%s%s', (string) $temp1, $operator, (string) $temp2);

		return $this->evaluateExpression($expression);
	}

	protected function processColorScale(Conditional $conditional): bool
	{
		if (is_numeric($this->wrapCellValue()) && (($nullsafeVariable1 = $conditional->getColorScale()) ? $nullsafeVariable1->colorScaleReadyForUse() : null)) {
			return true;
		}

		return false;
	}

	protected function processRangeOperator(Conditional $conditional): bool
	{
		$conditions = $this->adjustConditionsForCellReferences($conditional->getConditions());
		sort($conditions);
		$expression = sprintf(
			(string) preg_replace(
				'/\bA1\b/i',
				(string) $this->wrapCellValue(),
				self::COMPARISON_RANGE_OPERATORS[$conditional->getOperatorType()]
			),
			...$conditions //* @phpstan-ignore-line
		);

		return $this->evaluateExpression($expression);
	}

	protected function processDuplicatesComparison(Conditional $conditional): bool
	{
		$worksheetName = $this->cell->getWorksheet()->getTitle();

		$expression = sprintf(
			self::COMPARISON_DUPLICATES_OPERATORS[$conditional->getConditionType()],
			$worksheetName,
			$this->conditionalRange,
			$this->cellConditionCheck($this->cell->getCalculatedValueString())
		);

		return $this->evaluateExpression($expression);
	}

	protected function processExpression(Conditional $conditional): bool
	{
		$conditions = $this->adjustConditionsForCellReferences($conditional->getConditions());
		/** @var string */
		$expression = array_pop($conditions);
		/** @var float|int|string */
		$temp = $this->wrapCellValue();

		$expression = (string) preg_replace(
			'/\b' . $this->referenceCell . '\b/i',
			(string) $temp,
			$expression
		);

		return $this->evaluateExpression($expression);
	}

	protected function evaluateExpression(string $expression): bool
	{
		$expression = "={$expression}";

		try {
			$this->engine->flushInstance();
			$result = (bool) $this->engine->calculateFormula($expression);
		} catch (Exception $exception) {
			return false;
		}

		return $result;
	}
}
