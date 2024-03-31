<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE;

use TablePress\PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE;

class Blip
{
	/**
	 * The parent BSE.
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE
	 */
	private $parent;

	/**
	 * Raw image data.
	 * @var string
	 */
	private $data;

	/**
	 * Get the raw image data.
	 */
	public function getData(): string
	{
		return $this->data;
	}

	/**
	 * Set the raw image data.
	 */
	public function setData(string $data): void
	{
		$this->data = $data;
	}

	/**
	 * Set parent BSE.
	 */
	public function setParent(BSE $parent): void
	{
		$this->parent = $parent;
	}

	/**
	 * Get parent BSE.
	 */
	public function getParent(): BSE
	{
		return $this->parent;
	}
}
