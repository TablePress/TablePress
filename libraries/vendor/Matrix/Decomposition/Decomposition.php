<?php

namespace TablePress\Matrix\Decomposition;

use TablePress\Matrix\Exception;
use TablePress\Matrix\Matrix;

class Decomposition
{
	const LU = 'LU';
	const QR = 'QR';

	/**
	 * @throws Exception
	 */
	public static function decomposition($type, TablePress\Matrix $matrix)
	{
		switch (strtoupper($type)) {
			case self::LU:
				return new LU($matrix);
			case self::QR:
				return new QR($matrix);
			default:
				throw new Exception('Invalid Decomposition');
		}
	}
}
