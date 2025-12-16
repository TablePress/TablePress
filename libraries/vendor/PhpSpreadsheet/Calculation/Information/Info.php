<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Functions;
use TablePress\PhpOffice\PhpSpreadsheet\Cell\Cell;

class Info
{
	/**
	 * @internal
	 */
	public static bool $infoSupported = true;

	/**
	 * INFO.
	 *
	 * Excel Function:
	 *        =INFO(type_text)
	 *
	 * @param mixed $typeText String specifying the type of information to be returned
	 * @param ?Cell $cell Cell from which spreadsheet information is retrieved
	 *
	 * @return int|string The requested information about the current operating environment
	 */
	public static function getInfo($typeText = '', ?Cell $cell = null)
	{
		if (!self::$infoSupported) {
			return Functions::DUMMY();
		}

		switch (is_string($typeText) ? strtolower($typeText) : $typeText) {
									case 'directory':
										return '/';
									case 'numfile':
										return (($nullsafeVariable1 = ($nullsafeVariable2 = ($nullsafeVariable3 = $cell) ? $nullsafeVariable3->getWorksheetOrNull() : null) ? $nullsafeVariable2->getParent() : null) ? $nullsafeVariable1->getSheetCount() : null) ?? 1;
									case 'origin':
										return '$A:$A$1';
									case 'osversion':
										return 'PHP ' . PHP_VERSION;
									case 'recalc':
										return 'Automatic';
									case 'release':
										return PHP_VERSION;
									case 'system':
										return 'PHP';
									case 'memavail':
									case 'memused':
									case 'totmem':
										return ExcelError::NA();
									default:
										return ExcelError::VALUE();
								}
	}
}
