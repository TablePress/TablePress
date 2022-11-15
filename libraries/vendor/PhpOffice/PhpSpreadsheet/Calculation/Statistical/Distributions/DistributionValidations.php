<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Exception;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Statistical\StatisticalValidations;

class DistributionValidations extends StatisticalValidations
{
	/**
	 * @param mixed $probability
	 */
	public static function validateProbability($probability): float
	{
		$probability = self::validateFloat($probability);

		if ($probability < 0.0 || $probability > 1.0) {
			throw new Exception(ExcelError::NAN());
		}

		return $probability;
	}
}
