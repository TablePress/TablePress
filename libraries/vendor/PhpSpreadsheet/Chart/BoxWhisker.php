<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Chart;

use TablePress\PhpOffice\PhpSpreadsheet\Exception;
use TablePress\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BoxWhisker implements ChartEx
{
	public const TYPE = 'boxWhisker';

	public const QUARTILE_METHOD_EXCLUSIVE = 'exclusive';

	public const QUARTILE_METHOD_INCLUSIVE = 'inclusive';

	/**
	 * @var BoxWhiskerSeries[]
	 */
	private array $series;

	private bool $showMeanLine = false;

	private bool $showMeanMarker = true;

	private bool $showInnerPoints = false;

	private bool $showOutliers = true;

	private string $quartileMethod = self::QUARTILE_METHOD_EXCLUSIVE;

	/**
	 * @param BoxWhiskerSeries[] $series
	 */
	public function __construct(array $series)
	{
		foreach ($series as $item) {
			if (!$item instanceof BoxWhiskerSeries) {
				throw new Exception('BoxWhisker series must contain BoxWhiskerSeries objects.');
			}
		}

		$this->series = $series;
	}

	public function getChartType(): string
	{
		return self::TYPE;
	}

	/**
	 * @return BoxWhiskerSeries[]
	 */
	public function getSeries(): array
	{
		return $this->series;
	}

	/**
				 * @return static
				 */
				public function setShowMeanLine(bool $showMeanLine)
	{
		$this->showMeanLine = $showMeanLine;

		return $this;
	}

	public function getShowMeanLine(): bool
	{
		return $this->showMeanLine;
	}

	/**
				 * @return static
				 */
				public function setShowMeanMarker(bool $showMeanMarker)
	{
		$this->showMeanMarker = $showMeanMarker;

		return $this;
	}

	public function getShowMeanMarker(): bool
	{
		return $this->showMeanMarker;
	}

	/**
				 * @return static
				 */
				public function setShowInnerPoints(bool $showInnerPoints)
	{
		$this->showInnerPoints = $showInnerPoints;

		return $this;
	}

	public function getShowInnerPoints(): bool
	{
		return $this->showInnerPoints;
	}

	/**
				 * @return static
				 */
				public function setShowOutliers(bool $showOutliers)
	{
		$this->showOutliers = $showOutliers;

		return $this;
	}

	public function getShowOutliers(): bool
	{
		return $this->showOutliers;
	}

	/**
				 * @return static
				 */
				public function setQuartileMethod(string $quartileMethod)
	{
		if (
			!in_array($quartileMethod, [
				self::QUARTILE_METHOD_EXCLUSIVE,
				self::QUARTILE_METHOD_INCLUSIVE,
			], true)
		) {
			throw new Exception('Invalid quartile method.');
		}

		$this->quartileMethod = $quartileMethod;

		return $this;
	}

	public function getQuartileMethod(): string
	{
		return $this->quartileMethod;
	}

	public function refresh(Worksheet $worksheet): void
	{
		foreach ($this->series as $series) {
			$series->refresh($worksheet);
		}
	}
}
