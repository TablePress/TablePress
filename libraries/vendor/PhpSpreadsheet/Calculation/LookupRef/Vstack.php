<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Vstack
{
	/**
				 * Excel function VSTACK.
				 *
				 * @return mixed[]
				 * @param mixed ...$inputData
				 */
				public static function vstack(...$inputData)
	{
		$returnMatrix = [];

		$columns = 0;
		foreach ($inputData as $matrix) {
			if (!is_array($matrix)) {
				$count = 1;
			} else {
				$count = count(reset($matrix)); //* @phpstan-ignore-line
			}
			$columns = max($columns, $count);
		}

		foreach ($inputData as $matrix) {
			if (!is_array($matrix)) {
				$matrix = [$matrix];
			}
			foreach ($matrix as $row) {
				if (!is_array($row)) {
					$row = [$row];
				}
				$returnMatrix[] = array_values(array_pad($row, $columns, ExcelError::NA()));
			}
		}

		return $returnMatrix;
	}
}
