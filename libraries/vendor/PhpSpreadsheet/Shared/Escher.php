<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Shared;

use TablePress\PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;

class Escher
{
	/**
	 * Drawing Group Container.
	 */
	private ?Escher\DggContainer $dggContainer = null;

	/**
	 * Drawing Container.
	 */
	private ?Escher\DgContainer $dgContainer = null;

	/**
	 * Get Drawing Group Container.
	 */
	public function getDggContainer(): ?Escher\DggContainer
	{
		return $this->dggContainer;
	}

	/**
	 * Get Drawing Group Container.
	 */
	public function getDggContainerOrThrow(): Escher\DggContainer
				{
					if (!isset($this->dggContainer)) {
						throw new SpreadsheetException('dggContainer is unexpectedly null');
					}
					return $this->dggContainer;
				}

	/**
	 * Set Drawing Group Container.
	 */
	public function setDggContainer(Escher\DggContainer $dggContainer): Escher\DggContainer
	{
		return $this->dggContainer = $dggContainer;
	}

	/**
	 * Get Drawing Container.
	 */
	public function getDgContainer(): ?Escher\DgContainer
	{
		return $this->dgContainer;
	}

	/**
	 * Get Drawing Container.
	 */
	public function getDgContainerOrThrow(): Escher\DgContainer
				{
					if (!isset($this->dgContainer)) {
						throw new SpreadsheetException('dgContainer is unexpectedly null');
					}
					return $this->dgContainer;
				}

	/**
	 * Set Drawing Container.
	 */
	public function setDgContainer(Escher\DgContainer $dgContainer): Escher\DgContainer
	{
		return $this->dgContainer = $dgContainer;
	}
}
