<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer\SpgrContainer;

use TablePress\PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer\SpgrContainer;

class SpContainer
{
	/**
	 * Parent Shape Group Container.
	 */
	private SpgrContainer $parent;

	/**
	 * Is this a group shape?
	 */
	private bool $spgr = false;

	/**
	 * Shape type.
	 */
	private int $spType;

	/**
	 * Shape flag.
	 */
	private int $spFlag;

	/**
	 * Shape index (usually group shape has index 0, and the rest: 1,2,3...).
	 */
	private int $spId;

	/**
	 * Array of options.
	 */
	private array $OPT = [];

	/**
	 * Cell coordinates of upper-left corner of shape, e.g. 'A1'.
	 */
	private string $startCoordinates = '';

	/**
				 * Horizontal offset of upper-left corner of shape measured in 1/1024 of column width.
				 * @var float|int
				 */
				private $startOffsetX;

	/**
				 * Vertical offset of upper-left corner of shape measured in 1/256 of row height.
				 * @var float|int
				 */
				private $startOffsetY;

	/**
	 * Cell coordinates of bottom-right corner of shape, e.g. 'B2'.
	 */
	private string $endCoordinates;

	/**
				 * Horizontal offset of bottom-right corner of shape measured in 1/1024 of column width.
				 * @var float|int
				 */
				private $endOffsetX;

	/**
				 * Vertical offset of bottom-right corner of shape measured in 1/256 of row height.
				 * @var float|int
				 */
				private $endOffsetY;

	/**
	 * Set parent Shape Group Container.
	 */
	public function setParent(SpgrContainer $parent): void
	{
		$this->parent = $parent;
	}

	/**
	 * Get the parent Shape Group Container.
	 */
	public function getParent(): SpgrContainer
	{
		return $this->parent;
	}

	/**
	 * Set whether this is a group shape.
	 */
	public function setSpgr(bool $value): void
	{
		$this->spgr = $value;
	}

	/**
	 * Get whether this is a group shape.
	 */
	public function getSpgr(): bool
	{
		return $this->spgr;
	}

	/**
	 * Set the shape type.
	 */
	public function setSpType(int $value): void
	{
		$this->spType = $value;
	}

	/**
	 * Get the shape type.
	 */
	public function getSpType(): int
	{
		return $this->spType;
	}

	/**
	 * Set the shape flag.
	 */
	public function setSpFlag(int $value): void
	{
		$this->spFlag = $value;
	}

	/**
	 * Get the shape flag.
	 */
	public function getSpFlag(): int
	{
		return $this->spFlag;
	}

	/**
	 * Set the shape index.
	 */
	public function setSpId(int $value): void
	{
		$this->spId = $value;
	}

	/**
	 * Get the shape index.
	 */
	public function getSpId(): int
	{
		return $this->spId;
	}

	/**
				 * Set an option for the Shape Group Container.
				 *
				 * @param int $property The number specifies the option
				 * @param mixed $value
				 */
				public function setOPT(int $property, $value): void
	{
		$this->OPT[$property] = $value;
	}

	/**
				 * Get an option for the Shape Group Container.
				 *
				 * @param int $property The number specifies the option
				 * @return mixed
				 */
				public function getOPT(int $property)
	{
		if (isset($this->OPT[$property])) {
			return $this->OPT[$property];
		}

		return null;
	}

	/**
	 * Get the collection of options.
	 */
	public function getOPTCollection(): array
	{
		return $this->OPT;
	}

	/**
	 * Set cell coordinates of upper-left corner of shape.
	 *
	 * @param string $value eg: 'A1'
	 */
	public function setStartCoordinates(string $value): void
	{
		$this->startCoordinates = $value;
	}

	/**
	 * Get cell coordinates of upper-left corner of shape.
	 */
	public function getStartCoordinates(): string
	{
		return $this->startCoordinates;
	}

	/**
				 * Set offset in x-direction of upper-left corner of shape measured in 1/1024 of column width.
				 * @param int|float $startOffsetX
				 */
				public function setStartOffsetX($startOffsetX): void
	{
		$this->startOffsetX = $startOffsetX;
	}

	/**
				 * Get offset in x-direction of upper-left corner of shape measured in 1/1024 of column width.
				 * @return float|int
				 */
				public function getStartOffsetX()
	{
		return $this->startOffsetX;
	}

	/**
				 * Set offset in y-direction of upper-left corner of shape measured in 1/256 of row height.
				 * @param int|float $startOffsetY
				 */
				public function setStartOffsetY($startOffsetY): void
	{
		$this->startOffsetY = $startOffsetY;
	}

	/**
				 * Get offset in y-direction of upper-left corner of shape measured in 1/256 of row height.
				 * @return float|int
				 */
				public function getStartOffsetY()
	{
		return $this->startOffsetY;
	}

	/**
	 * Set cell coordinates of bottom-right corner of shape.
	 *
	 * @param string $value eg: 'A1'
	 */
	public function setEndCoordinates(string $value): void
	{
		$this->endCoordinates = $value;
	}

	/**
	 * Get cell coordinates of bottom-right corner of shape.
	 */
	public function getEndCoordinates(): string
	{
		return $this->endCoordinates;
	}

	/**
				 * Set offset in x-direction of bottom-right corner of shape measured in 1/1024 of column width.
				 * @param int|float $endOffsetX
				 */
				public function setEndOffsetX($endOffsetX): void
	{
		$this->endOffsetX = $endOffsetX;
	}

	/**
				 * Get offset in x-direction of bottom-right corner of shape measured in 1/1024 of column width.
				 * @return float|int
				 */
				public function getEndOffsetX()
	{
		return $this->endOffsetX;
	}

	/**
				 * Set offset in y-direction of bottom-right corner of shape measured in 1/256 of row height.
				 * @param int|float $endOffsetY
				 */
				public function setEndOffsetY($endOffsetY): void
	{
		$this->endOffsetY = $endOffsetY;
	}

	/**
				 * Get offset in y-direction of bottom-right corner of shape measured in 1/256 of row height.
				 * @return float|int
				 */
				public function getEndOffsetY()
	{
		return $this->endOffsetY;
	}

	/**
	 * Get the nesting level of this spContainer. This is the number of spgrContainers between this spContainer and
	 * the dgContainer. A value of 1 = immediately within first spgrContainer
	 * Higher nesting level occurs if and only if spContainer is part of a shape group.
	 *
	 * @return int Nesting level
	 */
	public function getNestingLevel(): int
	{
		$nestingLevel = 0;

		$parent = $this->getParent();
		while ($parent instanceof SpgrContainer) {
			++$nestingLevel;
			$parent = $parent->getParent();
		}

		return $nestingLevel;
	}
}
