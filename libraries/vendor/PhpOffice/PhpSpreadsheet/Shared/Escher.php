<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Shared;

class Escher
{
	/**
	 * Drawing Group Container.
	 *
	 * @var ?Escher\DggContainer
	 */
	private $dggContainer;

	/**
	 * Drawing Container.
	 *
	 * @var ?Escher\DgContainer
	 */
	private $dgContainer;

	/**
	 * Get Drawing Group Container.
	 */
	public function getDggContainer(): ?Escher\DggContainer
	{
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
	 * Set Drawing Container.
	 */
	public function setDgContainer(Escher\DgContainer $dgContainer): Escher\DgContainer
	{
		return $this->dgContainer = $dgContainer;
	}
}
