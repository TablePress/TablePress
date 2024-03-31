<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Worksheet;

class Pane
{
	/**
	 * @var string
	 */
	private $sqref;

	/**
	 * @var string
	 */
	private $activeCell;

	/**
	 * @var string
	 */
	private $position;

	public function __construct(string $position, string $sqref = '', string $activeCell = '')
	{
		$this->sqref = $sqref;
		$this->activeCell = $activeCell;
		$this->position = $position;
	}

	public function getPosition(): string
	{
		return $this->position;
	}

	public function getSqref(): string
	{
		return $this->sqref;
	}

	public function setSqref(string $sqref): self
	{
		$this->sqref = $sqref;

		return $this;
	}

	public function getActiveCell(): string
	{
		return $this->activeCell;
	}

	public function setActiveCell(string $activeCell): self
	{
		$this->activeCell = $activeCell;

		return $this;
	}
}
