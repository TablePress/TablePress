<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class SharedFormula
{
	/**
	 * @var string
	 */
	private $master;

	/**
	 * @var string
	 */
	private $formula;

	public function __construct(string $master, string $formula)
	{
		$this->master = $master;
		$this->formula = $formula;
	}

	public function master(): string
	{
		return $this->master;
	}

	public function formula(): string
	{
		return $this->formula;
	}
}
