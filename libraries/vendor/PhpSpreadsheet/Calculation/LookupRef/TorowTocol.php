<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Functions;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information\ErrorValue;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class TorowTocol
{
	/**
				 * Excel function TOCOL.
				 *
				 * @return mixed[]|string
				 * @param mixed $array
				 * @param mixed $ignore
				 * @param mixed $byColumn
				 */
				public static function tocol($array, $ignore = 0, $byColumn = false)
	{
		$result = self::torow($array, $ignore, $byColumn);
		if (is_array($result)) {
			return array_map((fn ($x) => [$x]), $result);
		}

		return $result;
	}

	/**
				 * Excel function TOROW.
				 *
				 * @return mixed[]|string
				 * @param mixed $array
				 * @param mixed $ignore
				 * @param mixed $byColumn
				 */
				public static function torow($array, $ignore = 0, $byColumn = false)
	{
		if (!is_numeric($ignore)) {
			return ExcelError::VALUE();
		}
		$ignore = (int) $ignore;
		if ($ignore < 0 || $ignore > 3) {
			return ExcelError::VALUE();
		}
		if (is_int($byColumn) || is_float($byColumn)) {
			$byColumn = (bool) $byColumn;
		}
		if (!is_bool($byColumn)) {
			return ExcelError::VALUE();
		}
		if (!is_array($array)) {
			$array = [$array];
		}
		if ($byColumn) {
			$temp = [];
			foreach ($array as $row) {
				if (!is_array($row)) {
					$row = [$row];
				}
				$temp[] = Functions::flattenArray($row);
			}
			$array = ChooseRowsEtc::transpose($temp);
		} else {
			$array = Functions::flattenArray($array);
		}

		return self::byRow($array, $ignore);
	}

	/**
	 * @param mixed[] $array
	 *
	 * @return mixed[]
	 */
	private static function byRow(array $array, int $ignore): array
	{
		$returnMatrix = [];
		foreach ($array as $row) {
			if (!is_array($row)) {
				$row = [$row];
			}
			foreach ($row as $cell) {
				if ($cell === null) {
					if ($ignore === 1 || $ignore === 3) {
						continue;
					}
					$cell = 0;
				} elseif (ErrorValue::isError($cell)) {
					if ($ignore === 2 || $ignore === 3) {
						continue;
					}
				}
				$returnMatrix[] = $cell;
			}
		}

		return $returnMatrix;
	}
}
