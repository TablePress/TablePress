<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Shared\Escher;

use TablePress\PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer\SpgrContainer;

class DgContainer
{
	/**
	 * Drawing index, 1-based.
	 */
	private ?int $dgId = null;

	/**
	 * Last shape index in this drawing.
	 */
	private ?int $lastSpId = null;

	private ?SpgrContainer $spgrContainer = null;

	public function getDgId(): ?int
	{
		return $this->dgId;
	}

	public function setDgId(int $value): void
	{
		$this->dgId = $value;
	}

	public function getLastSpId(): ?int
	{
		return $this->lastSpId;
	}

	public function setLastSpId(int $value): void
	{
		$this->lastSpId = $value;
	}

	public function getSpgrContainer(): ?SpgrContainer
	{
		return $this->spgrContainer;
	}

	public function getSpgrContainerOrThrow(): SpgrContainer
	{
		if ($this->spgrContainer !== null) {
			return $this->spgrContainer;
		}

		throw new SpreadsheetException('spgrContainer is unexpectedly null');
	}

	public function setSpgrContainer(SpgrContainer $spgrContainer): SpgrContainer
	{
		return $this->spgrContainer = $spgrContainer;
	}
}
