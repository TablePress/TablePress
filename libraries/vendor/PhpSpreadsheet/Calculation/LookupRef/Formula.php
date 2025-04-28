<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use TablePress\PhpOffice\PhpSpreadsheet\Cell\Cell;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Formula
{
	/**
	 * FORMULATEXT.
	 *
	 * @param mixed $cellReference The cell to check
	 * @param ?Cell $cell The current cell (containing this formula)
	 */
	public static function text($cellReference = '', ?Cell $cell = null): string
	{
		if ($cell === null) {
			return ExcelError::REF();
		}

		$worksheet = null;
		$cellReference = StringHelper::convertToString($cellReference);
		if (1 === preg_match('/^' . Calculation::CALCULATION_REGEXP_CELLREF . '$/i', $cellReference, $matches)) {
			$cellReference = $matches[6] . $matches[7];
			$worksheetName = trim($matches[3], "'");
			$worksheet = (!empty($worksheetName))
				? $cell->getWorksheet()->getParentOrThrow()->getSheetByName($worksheetName)
				: $cell->getWorksheet();
		}

		if (
			$worksheet === null
			|| !$worksheet->cellExists($cellReference)
			|| !$worksheet->getCell($cellReference)->isFormula()
		) {
			return ExcelError::NA();
		}

		return $worksheet->getCell($cellReference)->getValueString();
	}
}
