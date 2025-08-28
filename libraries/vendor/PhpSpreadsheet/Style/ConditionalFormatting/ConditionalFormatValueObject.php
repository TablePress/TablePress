<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting;

class ConditionalFormatValueObject
{
	private string $type;

	/**
				 * @var float|int|string|null
				 */
				private $value;

	private ?string $cellFormula;

	/**
	 * For icon sets, determines whether this threshold value uses the greater
	 * than or equal to operator. False indicates 'greater than' is used instead
	 * of 'greater than or equal to'.
	 */
	private ?bool $greaterThanOrEqual = null;

	/**
				 * @param null|float|int|string $value
				 */
				public function __construct(string $type, $value = null, ?string $cellFormula = null)
	{
		$this->type = $type;
		$this->value = $value;
		$this->cellFormula = $cellFormula;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type): self
	{
		$this->type = $type;

		return $this;
	}

	/**
				 * @return float|int|string|null
				 */
				public function getValue()
	{
		return $this->value;
	}

	/**
				 * @param null|float|int|string $value
				 */
				public function setValue($value): self
	{
		$this->value = $value;

		return $this;
	}

	public function getCellFormula(): ?string
	{
		return $this->cellFormula;
	}

	public function setCellFormula(?string $cellFormula): self
	{
		$this->cellFormula = $cellFormula;

		return $this;
	}

	public function getGreaterThanOrEqual(): ?bool
	{
		return $this->greaterThanOrEqual;
	}

	public function setGreaterThanOrEqual(?bool $greaterThanOrEqual): self
	{
		$this->greaterThanOrEqual = $greaterThanOrEqual;

		return $this;
	}
}
