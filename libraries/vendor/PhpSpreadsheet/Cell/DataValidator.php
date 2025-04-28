<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Cell;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Functions;
use TablePress\PhpOffice\PhpSpreadsheet\Exception;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\StringHelper;

/**
 * Validate a cell value according to its validation rules.
 */
class DataValidator
{
	/**
	 * Does this cell contain valid value?
	 *
	 * @param Cell $cell Cell to check the value
	 */
	public function isValid(Cell $cell): bool
	{
		if (!$cell->hasDataValidation() || $cell->getDataValidation()->getType() === DataValidation::TYPE_NONE) {
			return true;
		}

		$cellValue = $cell->getValue();
		$dataValidation = $cell->getDataValidation();

		if (!$dataValidation->getAllowBlank() && ($cellValue === null || $cellValue === '')) {
			return false;
		}

		$returnValue = false;
		$type = $dataValidation->getType();
		if ($type === DataValidation::TYPE_LIST) {
			$returnValue = $this->isValueInList($cell);
		} elseif ($type === DataValidation::TYPE_WHOLE) {
			if (!is_numeric($cellValue) || fmod((float) $cellValue, 1) != 0) {
				$returnValue = false;
			} else {
				$returnValue = $this->numericOperator($dataValidation, (int) $cellValue, $cell);
			}
		} elseif ($type === DataValidation::TYPE_DECIMAL || $type === DataValidation::TYPE_DATE || $type === DataValidation::TYPE_TIME) {
			if (!is_numeric($cellValue)) {
				$returnValue = false;
			} else {
				$returnValue = $this->numericOperator($dataValidation, (float) $cellValue, $cell);
			}
		} elseif ($type === DataValidation::TYPE_TEXTLENGTH) {
			$returnValue = $this->numericOperator($dataValidation, mb_strlen($cell->getValueString()), $cell);
		}

		return $returnValue;
	}

	private const TWO_FORMULAS = [DataValidation::OPERATOR_BETWEEN, DataValidation::OPERATOR_NOTBETWEEN];

	/**
				 * @param mixed $formula
				 * @return mixed
				 */
				private static function evaluateNumericFormula($formula, Cell $cell)
	{
		if (!is_numeric($formula)) {
			$calculation = Calculation::getInstance($cell->getWorksheet()->getParent());

			try {
				$formula2 = StringHelper::convertToString($formula);
				$result = $calculation
					->calculateFormula("=$formula2", $cell->getCoordinate(), $cell);
				while (is_array($result)) {
					$result = array_pop($result);
				}
				$formula = $result;
			} catch (Exception $exception) {
				// do nothing
			}
		}

		return $formula;
	}

	/**
				 * @param int|float $cellValue
				 */
				private function numericOperator(DataValidation $dataValidation, $cellValue, Cell $cell): bool
	{
		$operator = $dataValidation->getOperator();
		$formula1 = self::evaluateNumericFormula(
			$dataValidation->getFormula1(),
			$cell
		);

		$formula2 = 0;
		if (in_array($operator, self::TWO_FORMULAS, true)) {
			$formula2 = self::evaluateNumericFormula(
				$dataValidation->getFormula2(),
				$cell
			);
		}

		switch ($operator) {
									case DataValidation::OPERATOR_BETWEEN:
										return $cellValue >= $formula1 && $cellValue <= $formula2;
									case DataValidation::OPERATOR_NOTBETWEEN:
										return $cellValue < $formula1 || $cellValue > $formula2;
									case DataValidation::OPERATOR_EQUAL:
										return $cellValue == $formula1;
									case DataValidation::OPERATOR_NOTEQUAL:
										return $cellValue != $formula1;
									case DataValidation::OPERATOR_LESSTHAN:
										return $cellValue < $formula1;
									case DataValidation::OPERATOR_LESSTHANOREQUAL:
										return $cellValue <= $formula1;
									case DataValidation::OPERATOR_GREATERTHAN:
										return $cellValue > $formula1;
									case DataValidation::OPERATOR_GREATERTHANOREQUAL:
										return $cellValue >= $formula1;
									default:
										return false;
								}
	}

	/**
	 * Does this cell contain valid value, based on list?
	 *
	 * @param Cell $cell Cell to check the value
	 */
	private function isValueInList(Cell $cell): bool
	{
		$cellValueString = $cell->getValueString();
		$dataValidation = $cell->getDataValidation();

		$formula1 = $dataValidation->getFormula1();
		if (!empty($formula1)) {
			// inline values list
			if ($formula1[0] === '"') {
				return in_array(strtolower($cellValueString), explode(',', strtolower(trim($formula1, '"'))), true);
			}
			$calculation = Calculation::getInstance($cell->getWorksheet()->getParent());

			try {
				$result = $calculation->calculateFormula("=$formula1", $cell->getCoordinate(), $cell);
				$result = is_array($result) ? Functions::flattenArray($result) : [$result];
				foreach ($result as $oneResult) {
					if (is_scalar($oneResult) && strcasecmp((string) $oneResult, $cellValueString) === 0) {
						return true;
					}
				}
			} catch (Exception $exception) {
				// do nothing
			}

			return false;
		}

		return true;
	}
}
