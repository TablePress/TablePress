<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Chart;

use TablePress\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

interface ChartEx
{
	/**
	 * Get the ChartEx type.
	 */
	public function getChartType(): string;

	/**
	 * Refresh ChartEx data values.
	 */
	public function refresh(Worksheet $worksheet): void;
}
