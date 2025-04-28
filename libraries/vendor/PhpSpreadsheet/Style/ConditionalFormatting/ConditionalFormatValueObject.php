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
}
