<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Chart;

use TablePress\PhpOffice\PhpSpreadsheet\Style\Font;

class Layout
{
	/**
	 * layoutTarget.
	 * @var string|null
	 */
	private $layoutTarget;

	/**
	 * X Mode.
	 * @var string|null
	 */
	private $xMode;

	/**
	 * Y Mode.
	 * @var string|null
	 */
	private $yMode;

	/**
	 * X-Position.
	 * @var float|null
	 */
	private $xPos;

	/**
	 * Y-Position.
	 * @var float|null
	 */
	private $yPos;

	/**
	 * width.
	 * @var float|null
	 */
	private $width;

	/**
	 * height.
	 * @var float|null
	 */
	private $height;

	/**
	 * Position - t=top.
	 * @var string
	 */
	private $dLblPos = '';

	/**
	 * @var string
	 */
	private $numFmtCode = '';

	/**
	 * @var bool
	 */
	private $numFmtLinked = false;

	/**
	 * show legend key
	 * Specifies that legend keys should be shown in data labels.
	 * @var bool|null
	 */
	private $showLegendKey;

	/**
	 * show value
	 * Specifies that the value should be shown in a data label.
	 * @var bool|null
	 */
	private $showVal;

	/**
	 * show category name
	 * Specifies that the category name should be shown in the data label.
	 * @var bool|null
	 */
	private $showCatName;

	/**
	 * show data series name
	 * Specifies that the series name should be shown in the data label.
	 * @var bool|null
	 */
	private $showSerName;

	/**
	 * show percentage
	 * Specifies that the percentage should be shown in the data label.
	 * @var bool|null
	 */
	private $showPercent;

	/**
	 * show bubble size.
	 * @var bool|null
	 */
	private $showBubbleSize;

	/**
	 * show leader lines
	 * Specifies that leader lines should be shown for the data label.
	 * @var bool|null
	 */
	private $showLeaderLines;

	/**
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Chart\ChartColor|null
	 */
	private $labelFillColor;

	/**
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Chart\ChartColor|null
	 */
	private $labelBorderColor;

	/**
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Style\Font|null
	 */
	private $labelFont;

	/**
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Chart\Properties|null
	 */
	private $labelEffects;

	/**
	 * Create a new Layout.
	 */
	public function __construct(array $layout = [])
	{
		if (isset($layout['layoutTarget'])) {
			$this->layoutTarget = $layout['layoutTarget'];
		}
		if (isset($layout['xMode'])) {
			$this->xMode = $layout['xMode'];
		}
		if (isset($layout['yMode'])) {
			$this->yMode = $layout['yMode'];
		}
		if (isset($layout['x'])) {
			$this->xPos = (float) $layout['x'];
		}
		if (isset($layout['y'])) {
			$this->yPos = (float) $layout['y'];
		}
		if (isset($layout['w'])) {
			$this->width = (float) $layout['w'];
		}
		if (isset($layout['h'])) {
			$this->height = (float) $layout['h'];
		}
		if (isset($layout['dLblPos'])) {
			$this->dLblPos = (string) $layout['dLblPos'];
		}
		if (isset($layout['numFmtCode'])) {
			$this->numFmtCode = (string) $layout['numFmtCode'];
		}
		$this->initBoolean($layout, 'showLegendKey');
		$this->initBoolean($layout, 'showVal');
		$this->initBoolean($layout, 'showCatName');
		$this->initBoolean($layout, 'showSerName');
		$this->initBoolean($layout, 'showPercent');
		$this->initBoolean($layout, 'showBubbleSize');
		$this->initBoolean($layout, 'showLeaderLines');
		$this->initBoolean($layout, 'numFmtLinked');
		$this->initColor($layout, 'labelFillColor');
		$this->initColor($layout, 'labelBorderColor');
		$labelFont = $layout['labelFont'] ?? null;
		if ($labelFont instanceof Font) {
			$this->labelFont = $labelFont;
		}
		$labelFontColor = $layout['labelFontColor'] ?? null;
		if ($labelFontColor instanceof ChartColor) {
			$this->setLabelFontColor($labelFontColor);
		}
		$labelEffects = $layout['labelEffects'] ?? null;
		if ($labelEffects instanceof Properties) {
			$this->labelEffects = $labelEffects;
		}
	}

	private function initBoolean(array $layout, string $name): void
	{
		if (isset($layout[$name])) {
			$this->$name = (bool) $layout[$name];
		}
	}

	private function initColor(array $layout, string $name): void
	{
		if (isset($layout[$name]) && $layout[$name] instanceof ChartColor) {
			$this->$name = $layout[$name];
		}
	}

	/**
	 * Get Layout Target.
	 */
	public function getLayoutTarget(): ?string
	{
		return $this->layoutTarget;
	}

	/**
	 * Set Layout Target.
	 *
	 * @return $this
	 */
	public function setLayoutTarget(?string $target)
	{
		$this->layoutTarget = $target;

		return $this;
	}

	/**
	 * Get X-Mode.
	 */
	public function getXMode(): ?string
	{
		return $this->xMode;
	}

	/**
	 * Set X-Mode.
	 *
	 * @return $this
	 */
	public function setXMode(?string $mode)
	{
		$this->xMode = (string) $mode;

		return $this;
	}

	/**
	 * Get Y-Mode.
	 */
	public function getYMode(): ?string
	{
		return $this->yMode;
	}

	/**
	 * Set Y-Mode.
	 *
	 * @return $this
	 */
	public function setYMode(?string $mode)
	{
		$this->yMode = (string) $mode;

		return $this;
	}

	/**
	 * Get X-Position.
	 * @return null|float|int
	 */
	public function getXPosition()
	{
		return $this->xPos;
	}

	/**
	 * Set X-Position.
	 *
	 * @return $this
	 */
	public function setXPosition(float $position)
	{
		$this->xPos = $position;

		return $this;
	}

	/**
	 * Get Y-Position.
	 */
	public function getYPosition(): ?float
	{
		return $this->yPos;
	}

	/**
	 * Set Y-Position.
	 *
	 * @return $this
	 */
	public function setYPosition(float $position)
	{
		$this->yPos = $position;

		return $this;
	}

	/**
	 * Get Width.
	 */
	public function getWidth(): ?float
	{
		return $this->width;
	}

	/**
	 * Set Width.
	 *
	 * @return $this
	 */
	public function setWidth(?float $width)
	{
		$this->width = $width;

		return $this;
	}

	/**
	 * Get Height.
	 */
	public function getHeight(): ?float
	{
		return $this->height;
	}

	/**
	 * Set Height.
	 *
	 * @return $this
	 */
	public function setHeight(?float $height)
	{
		$this->height = $height;

		return $this;
	}

	public function getShowLegendKey(): ?bool
	{
		return $this->showLegendKey;
	}

	/**
	 * Set show legend key
	 * Specifies that legend keys should be shown in data labels.
	 */
	public function setShowLegendKey(?bool $showLegendKey): self
	{
		$this->showLegendKey = $showLegendKey;

		return $this;
	}

	public function getShowVal(): ?bool
	{
		return $this->showVal;
	}

	/**
	 * Set show val
	 * Specifies that the value should be shown in data labels.
	 */
	public function setShowVal(?bool $showDataLabelValues): self
	{
		$this->showVal = $showDataLabelValues;

		return $this;
	}

	public function getShowCatName(): ?bool
	{
		return $this->showCatName;
	}

	/**
	 * Set show cat name
	 * Specifies that the category name should be shown in data labels.
	 */
	public function setShowCatName(?bool $showCategoryName): self
	{
		$this->showCatName = $showCategoryName;

		return $this;
	}

	public function getShowSerName(): ?bool
	{
		return $this->showSerName;
	}

	/**
	 * Set show data series name.
	 * Specifies that the series name should be shown in data labels.
	 */
	public function setShowSerName(?bool $showSeriesName): self
	{
		$this->showSerName = $showSeriesName;

		return $this;
	}

	public function getShowPercent(): ?bool
	{
		return $this->showPercent;
	}

	/**
	 * Set show percentage.
	 * Specifies that the percentage should be shown in data labels.
	 */
	public function setShowPercent(?bool $showPercentage): self
	{
		$this->showPercent = $showPercentage;

		return $this;
	}

	public function getShowBubbleSize(): ?bool
	{
		return $this->showBubbleSize;
	}

	/**
	 * Set show bubble size.
	 * Specifies that the bubble size should be shown in data labels.
	 */
	public function setShowBubbleSize(?bool $showBubbleSize): self
	{
		$this->showBubbleSize = $showBubbleSize;

		return $this;
	}

	public function getShowLeaderLines(): ?bool
	{
		return $this->showLeaderLines;
	}

	/**
	 * Set show leader lines.
	 * Specifies that leader lines should be shown in data labels.
	 */
	public function setShowLeaderLines(?bool $showLeaderLines): self
	{
		$this->showLeaderLines = $showLeaderLines;

		return $this;
	}

	public function getLabelFillColor(): ?ChartColor
	{
		return $this->labelFillColor;
	}

	public function setLabelFillColor(?ChartColor $chartColor): self
	{
		$this->labelFillColor = $chartColor;

		return $this;
	}

	public function getLabelBorderColor(): ?ChartColor
	{
		return $this->labelBorderColor;
	}

	public function setLabelBorderColor(?ChartColor $chartColor): self
	{
		$this->labelBorderColor = $chartColor;

		return $this;
	}

	public function getLabelFont(): ?Font
	{
		return $this->labelFont;
	}

	public function getLabelEffects(): ?Properties
	{
		return $this->labelEffects;
	}

	public function getLabelFontColor(): ?ChartColor
	{
		if ($this->labelFont === null) {
			return null;
		}

		return $this->labelFont->getChartColor();
	}

	public function setLabelFontColor(?ChartColor $chartColor): self
	{
		if ($this->labelFont === null) {
			$this->labelFont = new Font();
			$this->labelFont->setSize(null, true);
		}
		$this->labelFont->setChartColorFromObject($chartColor);

		return $this;
	}

	public function getDLblPos(): string
	{
		return $this->dLblPos;
	}

	public function setDLblPos(string $dLblPos): self
	{
		$this->dLblPos = $dLblPos;

		return $this;
	}

	public function getNumFmtCode(): string
	{
		return $this->numFmtCode;
	}

	public function setNumFmtCode(string $numFmtCode): self
	{
		$this->numFmtCode = $numFmtCode;

		return $this;
	}

	public function getNumFmtLinked(): bool
	{
		return $this->numFmtLinked;
	}

	public function setNumFmtLinked(bool $numFmtLinked): self
	{
		$this->numFmtLinked = $numFmtLinked;

		return $this;
	}

	/**
	 * Implement PHP __clone to create a deep clone, not just a shallow copy.
	 */
	public function __clone()
	{
		$this->labelFillColor = ($this->labelFillColor === null) ? null : clone $this->labelFillColor;
		$this->labelBorderColor = ($this->labelBorderColor === null) ? null : clone $this->labelBorderColor;
		$this->labelFont = ($this->labelFont === null) ? null : clone $this->labelFont;
		$this->labelEffects = ($this->labelEffects === null) ? null : clone $this->labelEffects;
	}
}
