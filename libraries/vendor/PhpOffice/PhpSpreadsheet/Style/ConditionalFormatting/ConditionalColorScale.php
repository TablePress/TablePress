<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting;

use TablePress\PhpOffice\PhpSpreadsheet\Style\Color;

class ConditionalColorScale
{
	/**
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalFormatValueObject|null
	 */
	private $minimumConditionalFormatValueObject;

	/**
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalFormatValueObject|null
	 */
	private $midpointConditionalFormatValueObject;

	/**
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalFormatValueObject|null
	 */
	private $maximumConditionalFormatValueObject;

	/**
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Style\Color|null
	 */
	private $minimumColor;

	/**
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Style\Color|null
	 */
	private $midpointColor;

	/**
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Style\Color|null
	 */
	private $maximumColor;

	public function getMinimumConditionalFormatValueObject(): ?ConditionalFormatValueObject
	{
		return $this->minimumConditionalFormatValueObject;
	}

	public function setMinimumConditionalFormatValueObject(ConditionalFormatValueObject $minimumConditionalFormatValueObject): self
	{
		$this->minimumConditionalFormatValueObject = $minimumConditionalFormatValueObject;

		return $this;
	}

	public function getMidpointConditionalFormatValueObject(): ?ConditionalFormatValueObject
	{
		return $this->midpointConditionalFormatValueObject;
	}

	public function setMidpointConditionalFormatValueObject(ConditionalFormatValueObject $midpointConditionalFormatValueObject): self
	{
		$this->midpointConditionalFormatValueObject = $midpointConditionalFormatValueObject;

		return $this;
	}

	public function getMaximumConditionalFormatValueObject(): ?ConditionalFormatValueObject
	{
		return $this->maximumConditionalFormatValueObject;
	}

	public function setMaximumConditionalFormatValueObject(ConditionalFormatValueObject $maximumConditionalFormatValueObject): self
	{
		$this->maximumConditionalFormatValueObject = $maximumConditionalFormatValueObject;

		return $this;
	}

	public function getMinimumColor(): ?Color
	{
		return $this->minimumColor;
	}

	public function setMinimumColor(Color $minimumColor): self
	{
		$this->minimumColor = $minimumColor;

		return $this;
	}

	public function getMidpointColor(): ?Color
	{
		return $this->midpointColor;
	}

	public function setMidpointColor(Color $midpointColor): self
	{
		$this->midpointColor = $midpointColor;

		return $this;
	}

	public function getMaximumColor(): ?Color
	{
		return $this->maximumColor;
	}

	public function setMaximumColor(Color $maximumColor): self
	{
		$this->maximumColor = $maximumColor;

		return $this;
	}
}
