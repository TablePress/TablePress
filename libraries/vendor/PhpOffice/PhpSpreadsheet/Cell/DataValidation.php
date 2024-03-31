<?php

namespace TablePress\PhpOffice\PhpSpreadsheet\Cell;

class DataValidation
{
	// Data validation types
	const TYPE_NONE = 'none';
	const TYPE_CUSTOM = 'custom';
	const TYPE_DATE = 'date';
	const TYPE_DECIMAL = 'decimal';
	const TYPE_LIST = 'list';
	const TYPE_TEXTLENGTH = 'textLength';
	const TYPE_TIME = 'time';
	const TYPE_WHOLE = 'whole';

	// Data validation error styles
	const STYLE_STOP = 'stop';
	const STYLE_WARNING = 'warning';
	const STYLE_INFORMATION = 'information';

	// Data validation operators
	const OPERATOR_BETWEEN = 'between';
	const OPERATOR_EQUAL = 'equal';
	const OPERATOR_GREATERTHAN = 'greaterThan';
	const OPERATOR_GREATERTHANOREQUAL = 'greaterThanOrEqual';
	const OPERATOR_LESSTHAN = 'lessThan';
	const OPERATOR_LESSTHANOREQUAL = 'lessThanOrEqual';
	const OPERATOR_NOTBETWEEN = 'notBetween';
	const OPERATOR_NOTEQUAL = 'notEqual';
	private const DEFAULT_OPERATOR = self::OPERATOR_BETWEEN;

	/**
	 * Formula 1.
	 * @var string
	 */
	private $formula1 = '';

	/**
	 * Formula 2.
	 * @var string
	 */
	private $formula2 = '';

	/**
	 * Type.
	 * @var string
	 */
	private $type = self::TYPE_NONE;

	/**
	 * Error style.
	 * @var string
	 */
	private $errorStyle = self::STYLE_STOP;

	/**
	 * Operator.
	 * @var string
	 */
	private $operator = self::DEFAULT_OPERATOR;

	/**
	 * Allow Blank.
	 * @var bool
	 */
	private $allowBlank = false;

	/**
	 * Show DropDown.
	 * @var bool
	 */
	private $showDropDown = false;

	/**
	 * Show InputMessage.
	 * @var bool
	 */
	private $showInputMessage = false;

	/**
	 * Show ErrorMessage.
	 * @var bool
	 */
	private $showErrorMessage = false;

	/**
	 * Error title.
	 * @var string
	 */
	private $errorTitle = '';

	/**
	 * Error.
	 * @var string
	 */
	private $error = '';

	/**
	 * Prompt title.
	 * @var string
	 */
	private $promptTitle = '';

	/**
	 * Prompt.
	 * @var string
	 */
	private $prompt = '';

	/**
	 * Create a new DataValidation.
	 */
	public function __construct()
	{
	}

	/**
	 * Get Formula 1.
	 */
	public function getFormula1(): string
	{
		return $this->formula1;
	}

	/**
	 * Set Formula 1.
	 *
	 * @return $this
	 */
	public function setFormula1(string $formula)
	{
		$this->formula1 = $formula;

		return $this;
	}

	/**
	 * Get Formula 2.
	 */
	public function getFormula2(): string
	{
		return $this->formula2;
	}

	/**
	 * Set Formula 2.
	 *
	 * @return $this
	 */
	public function setFormula2(string $formula)
	{
		$this->formula2 = $formula;

		return $this;
	}

	/**
	 * Get Type.
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * Set Type.
	 *
	 * @return $this
	 */
	public function setType(string $type)
	{
		$this->type = $type;

		return $this;
	}

	/**
	 * Get Error style.
	 */
	public function getErrorStyle(): string
	{
		return $this->errorStyle;
	}

	/**
	 * Set Error style.
	 *
	 * @param string $errorStyle see self::STYLE_*
	 *
	 * @return $this
	 */
	public function setErrorStyle(string $errorStyle)
	{
		$this->errorStyle = $errorStyle;

		return $this;
	}

	/**
	 * Get Operator.
	 */
	public function getOperator(): string
	{
		return $this->operator;
	}

	/**
	 * Set Operator.
	 *
	 * @return $this
	 */
	public function setOperator(string $operator)
	{
		$this->operator = ($operator === '') ? self::DEFAULT_OPERATOR : $operator;

		return $this;
	}

	/**
	 * Get Allow Blank.
	 */
	public function getAllowBlank(): bool
	{
		return $this->allowBlank;
	}

	/**
	 * Set Allow Blank.
	 *
	 * @return $this
	 */
	public function setAllowBlank(bool $allowBlank)
	{
		$this->allowBlank = $allowBlank;

		return $this;
	}

	/**
	 * Get Show DropDown.
	 */
	public function getShowDropDown(): bool
	{
		return $this->showDropDown;
	}

	/**
	 * Set Show DropDown.
	 *
	 * @return $this
	 */
	public function setShowDropDown(bool $showDropDown)
	{
		$this->showDropDown = $showDropDown;

		return $this;
	}

	/**
	 * Get Show InputMessage.
	 */
	public function getShowInputMessage(): bool
	{
		return $this->showInputMessage;
	}

	/**
	 * Set Show InputMessage.
	 *
	 * @return $this
	 */
	public function setShowInputMessage(bool $showInputMessage)
	{
		$this->showInputMessage = $showInputMessage;

		return $this;
	}

	/**
	 * Get Show ErrorMessage.
	 */
	public function getShowErrorMessage(): bool
	{
		return $this->showErrorMessage;
	}

	/**
	 * Set Show ErrorMessage.
	 *
	 * @return $this
	 */
	public function setShowErrorMessage(bool $showErrorMessage)
	{
		$this->showErrorMessage = $showErrorMessage;

		return $this;
	}

	/**
	 * Get Error title.
	 */
	public function getErrorTitle(): string
	{
		return $this->errorTitle;
	}

	/**
	 * Set Error title.
	 *
	 * @return $this
	 */
	public function setErrorTitle(string $errorTitle)
	{
		$this->errorTitle = $errorTitle;

		return $this;
	}

	/**
	 * Get Error.
	 */
	public function getError(): string
	{
		return $this->error;
	}

	/**
	 * Set Error.
	 *
	 * @return $this
	 */
	public function setError(string $error)
	{
		$this->error = $error;

		return $this;
	}

	/**
	 * Get Prompt title.
	 */
	public function getPromptTitle(): string
	{
		return $this->promptTitle;
	}

	/**
	 * Set Prompt title.
	 *
	 * @return $this
	 */
	public function setPromptTitle(string $promptTitle)
	{
		$this->promptTitle = $promptTitle;

		return $this;
	}

	/**
	 * Get Prompt.
	 */
	public function getPrompt(): string
	{
		return $this->prompt;
	}

	/**
	 * Set Prompt.
	 *
	 * @return $this
	 */
	public function setPrompt(string $prompt)
	{
		$this->prompt = $prompt;

		return $this;
	}

	/**
	 * Get hash code.
	 *
	 * @return string Hash code
	 */
	public function getHashCode(): string
	{
		return md5(
			$this->formula1
			. $this->formula2
			. $this->type
			. $this->errorStyle
			. $this->operator
			. ($this->allowBlank ? 't' : 'f')
			. ($this->showDropDown ? 't' : 'f')
			. ($this->showInputMessage ? 't' : 'f')
			. ($this->showErrorMessage ? 't' : 'f')
			. $this->errorTitle
			. $this->error
			. $this->promptTitle
			. $this->prompt
			. $this->sqref
			. __CLASS__
		);
	}

	/**
	 * Implement PHP __clone to create a deep clone, not just a shallow copy.
	 */
	public function __clone()
	{
		$vars = get_object_vars($this);
		foreach ($vars as $key => $value) {
			if (is_object($value)) {
				$this->$key = clone $value;
			} else {
				$this->$key = $value;
			}
		}
	}

	/**
	 * @var string|null
	 */
	private $sqref;

	public function getSqref(): ?string
	{
		return $this->sqref;
	}

	public function setSqref(?string $str): self
	{
		$this->sqref = $str;

		return $this;
	}
}
