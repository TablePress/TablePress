<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Worksheet;

use TablePress\PhpOffice\PhpSpreadsheet\Calculation\Functions;
use TablePress\PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use TablePress\PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PageBreak
{
	/** @var int */
	private $breakType;

	/** @var string */
	private $coordinate;

	/** @var int */
	private $maxColOrRow;

	/** @param array|CellAddress|string $coordinate */
	public function __construct(int $breakType, $coordinate, int $maxColOrRow = -1)
	{
		$coordinate = Functions::trimSheetFromCellReference(Validations::validateCellAddress($coordinate));
		$this->breakType = $breakType;
		$this->coordinate = $coordinate;
		$this->maxColOrRow = $maxColOrRow;
	}

	public function getBreakType(): int
	{
		return $this->breakType;
	}

	public function getCoordinate(): string
	{
		return $this->coordinate;
	}

	public function getMaxColOrRow(): int
	{
		return $this->maxColOrRow;
	}

	public function getColumnInt(): int
	{
		return Coordinate::indexesFromString($this->coordinate)[0];
	}

	public function getRow(): int
	{
		return Coordinate::indexesFromString($this->coordinate)[1];
	}

	public function getColumnString(): string
	{
		return Coordinate::indexesFromString($this->coordinate)[2];
	}
}
