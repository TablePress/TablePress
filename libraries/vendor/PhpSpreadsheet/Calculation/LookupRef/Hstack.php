<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Functions;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Hstack
{
	/**
				 * Excel function HSTACK.
				 *
				 * @return mixed[]|string
				 * @param mixed ...$inputData
				 */
				public static function hstack(...$inputData)
	{
		$maxRow = 0;
		foreach ($inputData as $matrix) {
			if (!is_array($matrix)) {
				$count = 1;
			} else {
				$count = count($matrix);
			}
			$maxRow = max($maxRow, $count);
		}
		/** @var mixed[] $inputData */
		foreach ($inputData as &$matrix) {
			if (!is_array($matrix)) {
				$matrix = [$matrix];
			}
			$rows = count($matrix);
			$reset = reset($matrix);
			$columns = is_array($reset) ? count($reset) : 1;
			while ($maxRow > $rows) {
				$matrix[] = array_pad([], $columns, ExcelError::NA());
				++$rows;
			}
		}

		$transpose = array_map(null, ...$inputData); //* @phpstan-ignore-line
		$returnMatrix = [];
		foreach ($transpose as $array) {
			$returnMatrix[] = Functions::flattenArray($array);
		}

		return $returnMatrix;
	}
}
