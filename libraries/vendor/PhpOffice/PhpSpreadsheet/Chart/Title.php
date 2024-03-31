<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Chart;

use TablePress\PhpOffice\PhpSpreadsheet\RichText\RichText;
use TablePress\PhpOffice\PhpSpreadsheet\Spreadsheet;
use TablePress\PhpOffice\PhpSpreadsheet\Style\Font;

class Title
{
	public const TITLE_CELL_REFERENCE
		= '/^(.*)!' // beginning of string, everything up to ! is match[1]
		. '[$]([A-Z]{1,3})' // absolute column string match[2]
		. '[$](\d{1,7})$/i'; // absolute row string match[3]

	/**
	 * Title Caption.
	 *
	 * @var array<RichText|string>|RichText|string
	 */
	private $caption;

	/**
	 * Allow overlay of other elements?
	 * @var bool
	 */
	private $overlay = true;

	/**
	 * Title Layout.
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Chart\Layout|null
	 */
	private $layout;

	/**
	 * @var string
	 */
	private $cellReference = '';

	/**
	 * @var \TablePress\PhpOffice\PhpSpreadsheet\Style\Font|null
	 */
	private $font;

	/**
	 * Create a new Title.
	 * @param mixed[]|\TablePress\PhpOffice\PhpSpreadsheet\RichText\RichText|string $caption
	 */
	public function __construct($caption = '', ?Layout $layout = null, bool $overlay = false)
	{
		$this->caption = $caption;
		$this->layout = $layout;
		$this->setOverlay($overlay);
	}

	/**
	 * Get caption.
	 * @return mixed[]|\TablePress\PhpOffice\PhpSpreadsheet\RichText\RichText|string
	 */
	public function getCaption()
	{
		return $this->caption;
	}

	public function getCaptionText(?Spreadsheet $spreadsheet = null): string
	{
		if ($spreadsheet !== null) {
			$caption = $this->getCalculatedTitle($spreadsheet);
			if ($caption !== null) {
				return $caption;
			}
		}
		$caption = $this->caption;
		if (is_string($caption)) {
			return $caption;
		}
		if ($caption instanceof RichText) {
			return $caption->getPlainText();
		}
		$retVal = '';
		foreach ($caption as $textx) {
			/** @var RichText|string $text */
			$text = $textx;
			if ($text instanceof RichText) {
				$retVal .= $text->getPlainText();
			} else {
				$retVal .= $text;
			}
		}

		return $retVal;
	}

	/**
	 * Set caption.
	 *
	 * @return $this
	 * @param mixed[]|\TablePress\PhpOffice\PhpSpreadsheet\RichText\RichText|string $caption
	 */
	public function setCaption($caption)
	{
		$this->caption = $caption;

		return $this;
	}

	/**
	 * Get allow overlay of other elements?
	 */
	public function getOverlay(): bool
	{
		return $this->overlay;
	}

	/**
	 * Set allow overlay of other elements?
	 */
	public function setOverlay(bool $overlay): self
	{
		$this->overlay = $overlay;

		return $this;
	}

	public function getLayout(): ?Layout
	{
		return $this->layout;
	}

	public function setCellReference(string $cellReference): self
	{
		$this->cellReference = $cellReference;

		return $this;
	}

	public function getCellReference(): string
	{
		return $this->cellReference;
	}

	public function getCalculatedTitle(?Spreadsheet $spreadsheet): ?string
	{
		preg_match(self::TITLE_CELL_REFERENCE, $this->cellReference, $matches);
		if (count($matches) === 0 || $spreadsheet === null) {
			return null;
		}
		$sheetName = preg_replace("/^'(.*)'$/", '$1', $matches[1]) ?? '';

		return ($nullsafeVariable1 = ($nullsafeVariable2 = $spreadsheet->getSheetByName($sheetName)) ? $nullsafeVariable2->getCell($matches[2] . $matches[3]) : null) ? $nullsafeVariable1->getFormattedValue() : null;
	}

	public function getFont(): ?Font
	{
		return $this->font;
	}

	public function setFont(?Font $font): self
	{
		$this->font = $font;

		return $this;
	}

	/**
	 * Implement PHP __clone to create a deep clone, not just a shallow copy.
	 */
	public function __clone()
	{
		$this->layout = ($this->layout === null) ? null : clone $this->layout;
		$this->font = ($this->font === null) ? null : clone $this->font;
		if (is_array($this->caption)) {
			$captions = $this->caption;
			$this->caption = [];
			foreach ($captions as $caption) {
				$this->caption[] = is_object($caption) ? (clone $caption) : $caption;
			}
		} else {
			$this->caption = is_object($this->caption) ? (clone $this->caption) : $this->caption;
		}
	}
}
