<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Chart\Renderer;

use TablePress\PhpOffice\PhpSpreadsheet\Chart\Chart;

interface IRenderer
{
	/**
	 * IRenderer constructor.
	 */
	public function __construct(Chart $chart);

	/**
	 * Render the chart to given file (or stream).
	 *
	 * @param string $filename Name of the file render to
	 *
	 * @return bool true on success
	 */
	public function render($filename);
}
