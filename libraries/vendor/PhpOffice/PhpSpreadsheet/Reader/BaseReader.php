<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Reader;

use TablePress\PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use TablePress\PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use TablePress\PhpOffice\PhpSpreadsheet\Reader\Security\XmlScanner;
use TablePress\PhpOffice\PhpSpreadsheet\Shared\File;
use TablePress\PhpOffice\PhpSpreadsheet\Spreadsheet;

abstract class BaseReader implements IReader
{
	/**
	 * Read data only?
	 * Identifies whether the Reader should only read data values for cells, and ignore any formatting information;
	 *        or whether it should read both data and formatting.
	 * @var bool
	 */
	protected $readDataOnly = false;

	/**
	 * Read empty cells?
	 * Identifies whether the Reader should read data values for cells all cells, or should ignore cells containing
	 *         null value or empty string.
	 * @var bool
	 */
	protected $readEmptyCells = true;

	/**
	 * Read charts that are defined in the workbook?
	 * Identifies whether the Reader should read the definitions for any charts that exist in the workbook;.
	 * @var bool
	 */
	protected $includeCharts = false;

	/**
	 * Restrict which sheets should be loaded?
	 * This property holds an array of worksheet names to be loaded. If null, then all worksheets will be loaded.
	 * This property is ignored for Csv, Html, and Slk.
	 *
	 * @var null|string[]
	 */
	protected $loadSheetsOnly;

	/**
	 * IReadFilter instance.
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Reader\IReadFilter
	 */
	protected $readFilter;

	/** @var resource */
	protected $fileHandle;

	/**
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Reader\Security\XmlScanner|null
	 */
	protected $securityScanner;

	public function __construct()
	{
		$this->readFilter = new DefaultReadFilter();
	}

	public function getReadDataOnly(): bool
	{
		return $this->readDataOnly;
	}

	/**
	 * @return $this
	 */
	public function setReadDataOnly(bool $readCellValuesOnly): \TablePress\PhpOffice\PhpSpreadsheet\Reader\IReader
	{
		$this->readDataOnly = $readCellValuesOnly;

		return $this;
	}

	public function getReadEmptyCells(): bool
	{
		return $this->readEmptyCells;
	}

	/**
	 * @return $this
	 */
	public function setReadEmptyCells(bool $readEmptyCells): \TablePress\PhpOffice\PhpSpreadsheet\Reader\IReader
	{
		$this->readEmptyCells = $readEmptyCells;

		return $this;
	}

	public function getIncludeCharts(): bool
	{
		return $this->includeCharts;
	}

	/**
	 * @return $this
	 */
	public function setIncludeCharts(bool $includeCharts): \TablePress\PhpOffice\PhpSpreadsheet\Reader\IReader
	{
		$this->includeCharts = $includeCharts;

		return $this;
	}

	public function getLoadSheetsOnly(): ?array
	{
		return $this->loadSheetsOnly;
	}

	/**
	 * @param string|mixed[]|null $sheetList
	 * @return $this
	 */
	public function setLoadSheetsOnly($sheetList): \TablePress\PhpOffice\PhpSpreadsheet\Reader\IReader
	{
		if ($sheetList === null) {
			return $this->setLoadAllSheets();
		}

		$this->loadSheetsOnly = is_array($sheetList) ? $sheetList : [$sheetList];

		return $this;
	}

	/**
	 * @return $this
	 */
	public function setLoadAllSheets(): \TablePress\PhpOffice\PhpSpreadsheet\Reader\IReader
	{
		$this->loadSheetsOnly = null;

		return $this;
	}

	public function getReadFilter(): IReadFilter
	{
		return $this->readFilter;
	}

	/**
	 * @return $this
	 */
	public function setReadFilter(IReadFilter $readFilter): \TablePress\PhpOffice\PhpSpreadsheet\Reader\IReader
	{
		$this->readFilter = $readFilter;

		return $this;
	}

	public function getSecurityScanner(): ?XmlScanner
	{
		return $this->securityScanner;
	}

	public function getSecurityScannerOrThrow(): XmlScanner
	{
		if ($this->securityScanner === null) {
			throw new ReaderException('Security scanner is unexpectedly null');
		}

		return $this->securityScanner;
	}

	protected function processFlags(int $flags): void
	{
		if (((bool) ($flags & self::LOAD_WITH_CHARTS)) === true) {
			$this->setIncludeCharts(true);
		}
		if (((bool) ($flags & self::READ_DATA_ONLY)) === true) {
			$this->setReadDataOnly(true);
		}
		if (((bool) ($flags & self::SKIP_EMPTY_CELLS) || (bool) ($flags & self::IGNORE_EMPTY_CELLS)) === true) {
			$this->setReadEmptyCells(false);
		}
	}

	protected function loadSpreadsheetFromFile(string $filename): Spreadsheet
	{
		throw new PhpSpreadsheetException('Reader classes must implement their own loadSpreadsheetFromFile() method');
	}

	/**
	 * Loads Spreadsheet from file.
	 *
	 * @param int $flags the optional second parameter flags may be used to identify specific elements
	 *                       that should be loaded, but which won't be loaded by default, using these values:
	 *                            IReader::LOAD_WITH_CHARTS - Include any charts that are defined in the loaded file
	 */
	public function load(string $filename, int $flags = 0): Spreadsheet
	{
		$this->processFlags($flags);

		try {
			return $this->loadSpreadsheetFromFile($filename);
		} catch (ReaderException $e) {
			throw $e;
		}
	}

	/**
	 * Open file for reading.
	 */
	protected function openFile(string $filename): void
	{
		$fileHandle = false;
		if ($filename) {
			File::assertFile($filename);

			// Open file
			$fileHandle = fopen($filename, 'rb');
		}
		if ($fileHandle === false) {
			throw new ReaderException('Could not open file ' . $filename . ' for reading.');
		}

		$this->fileHandle = $fileHandle;
	}

	/**
	 * Return worksheet info (Name, Last Column Letter, Last Column Index, Total Rows, Total Columns).
	 */
	public function listWorksheetInfo(string $filename): array
	{
		throw new PhpSpreadsheetException('Reader classes must implement their own listWorksheetInfo() method');
	}

	/**
	 * Returns names of the worksheets from a file,
	 * possibly without parsing the whole file to a Spreadsheet object.
	 * Readers will often have a more efficient method with which
	 * they can override this method.
	 */
	public function listWorksheetNames(string $filename): array
	{
		$returnArray = [];
		$info = $this->listWorksheetInfo($filename);
		foreach ($info as $infoArray) {
			if (isset($infoArray['worksheetName'])) {
				$returnArray[] = $infoArray['worksheetName'];
			}
		}

		return $returnArray;
	}
}
