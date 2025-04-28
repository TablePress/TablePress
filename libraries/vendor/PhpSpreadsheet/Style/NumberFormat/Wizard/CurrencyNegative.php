<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard;

class CurrencyNegative
{
	public const minus = 'minus';
	public const redMinus = 'redminus';
	public const parentheses = 'parentheses';
	public const redParentheses = 'redparentheses';
	public function start(): string
	{
		switch ($this) {
						case self::minus:
						case self::redMinus:
							return '-';
						case self::parentheses:
						case self::redParentheses:
							return '\(';
					}
	}
	public function end(): string
	{
		switch ($this) {
						case self::minus:
						case self::redMinus:
							return '';
						case self::parentheses:
						case self::redParentheses:
							return '\)';
					}
	}
	public function color(): string
	{
		switch ($this) {
						case self::redParentheses:
						case self::redMinus:
							return '[Red]';
						case self::parentheses:
						case self::minus:
							return '';
					}
	}
}
