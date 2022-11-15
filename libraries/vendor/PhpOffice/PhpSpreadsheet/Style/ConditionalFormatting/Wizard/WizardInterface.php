<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard;

use TablePress\PhpOffice\PhpSpreadsheet\Style\Conditional;
use TablePress\PhpOffice\PhpSpreadsheet\Style\Style;

interface WizardInterface
{
	public function getCellRange(): string;

	public function setCellRange(string $cellRange): void;

	public function getStyle(): Style;

	public function setStyle(Style $style): void;

	public function getStopIfTrue(): bool;

	public function setStopIfTrue(bool $stopIfTrue): void;

	public function getConditional(): Conditional;

	public static function fromConditional(Conditional $conditional, string $cellRange = 'A1'): self;
}
