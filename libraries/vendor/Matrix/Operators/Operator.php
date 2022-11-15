<?php

namespace TablePress\Matrix\Operators;

use TablePress\Matrix\Matrix;
use TablePress\Matrix\Exception;

abstract class Operator
{
	/**
	 * Stored internally as a 2-dimension array of values
	 *
	 * @property mixed[][] $matrix
	 **/
	protected $matrix;

	/**
	 * Number of rows in the matrix
	 *
	 * @property integer $rows
	 **/
	protected $rows;

	/**
	 * Number of columns in the matrix
	 *
	 * @property integer $columns
	 **/
	protected $columns;

	/**
	 * Create an new handler object for the operation
	 *
	 * @param TablePress\Matrix $matrix The base TablePress\Matrix object on which the operation will be performed
	 */
	public function __construct(TablePress\Matrix $matrix)
	{
		$this->rows = $matrix->rows;
		$this->columns = $matrix->columns;
		$this->matrix = $matrix->toArray();
	}

	/**
	 * Compare the dimensions of the matrices being operated on to see if they are valid for addition/subtraction
	 *
	 * @param TablePress\Matrix $matrix The second TablePress\Matrix object on which the operation will be performed
	 * @throws Exception
	 */
	protected function validateMatchingDimensions(TablePress\Matrix $matrix): void
	{
		if (($this->rows != $matrix->rows) || ($this->columns != $matrix->columns)) {
			throw new Exception('Matrices have mismatched dimensions');
		}
	}

	/**
	 * Compare the dimensions of the matrices being operated on to see if they are valid for multiplication/division
	 *
	 * @param TablePress\Matrix $matrix The second TablePress\Matrix object on which the operation will be performed
	 * @throws Exception
	 */
	protected function validateReflectingDimensions(TablePress\Matrix $matrix): void
	{
		if ($this->columns != $matrix->rows) {
			throw new Exception('Matrices have mismatched dimensions');
		}
	}

	/**
	 * Return the result of the operation
	 *
	 * @return TablePress\Matrix
	 */
	public function result(): TablePress\Matrix
	{
		return new Matrix($this->matrix);
	}
}
