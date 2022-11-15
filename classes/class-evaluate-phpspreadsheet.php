<?php
/**
 * TablePress Formula Evaluation PHPSpreadsheet Class.
 *
 * @package TablePress
 * @subpackage Formulas
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Formula Evaluation PHPSpreadsheet Class
 *
 * @package TablePress
 * @subpackage Formulas
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Evaluate_PHPSpreadsheet {

	/**
	 * Initializes the Formula Evaluation PHPSpreadsheet class.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		// Load PHPSpreadsheet via the Composer autoloading mechanism.
		TablePress::load_file( 'autoload.php', 'libraries' );
	}

	/**
	 * Evaluates formulas in the passed table.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $table_data Table data in which formulas shall be evaluated.
	 * @param string $table_id   ID of the passed table.
	 * @return array Table data with evaluated formulas.
	 */
	public function evaluate_table_data( array $table_data, $table_id ) {
		$table_has_formulas = false;

		// Loop through all cells to check for formulas and convert notations.
		foreach ( $table_data as &$row ) {
			foreach ( $row as &$cell_content ) {
				if ( '' === $cell_content || '=' === $cell_content || '=' !== $cell_content[0] ) {
					continue;
				}

				$table_has_formulas = true;

				// Convert legacy "formulas in text" notation to standard Excel notation (`=Text {A3+B3} Text` => `="Text "&A3+B3&" Text"`).
				$count = 0;
				$cell_content = preg_replace( '#{(.+?)}#', '"&$1&"', $cell_content, -1, $count );
				if ( $count > 0 ) {
					$cell_content = '="' . substr( $cell_content, 1 ) . '"';
				}
			}
		}
		unset( $row, $cell_content ); // Unset use-by-reference parameters of foreach loops.

		// No need to use the PHPSpreadsheet Calculation engine if the table does not contain formulas.
		if ( ! $table_has_formulas ) {
			return $table_data;
		}

		try {
			$spreadsheet = new \TablePress\PhpOffice\PhpSpreadsheet\Spreadsheet();
			$worksheet = $spreadsheet->setActiveSheetIndex( 0 );
			$worksheet->fromArray( /* $source */ $table_data, /* $nullValue */ '' );

			// Don't allow cyclic references.
			TablePress\PhpOffice\PhpSpreadsheet\Calculation\Calculation::getInstance( $spreadsheet )->cyclicFormulaCount = 0;

			/*
			 * Register variables as Named Formulas.
			 * The variables `ROW`, `COLUMN`, `CELL`, `PI`, and `E` should be considered deprecated and only their formulas should be used.
			 */
			$spreadsheet->addNamedFormula( new \TablePress\PhpOffice\PhpSpreadsheet\NamedFormula( 'TABLE_ID', $worksheet, $table_id ) );
			$num_rows = (string) count( $table_data );
			$spreadsheet->addNamedFormula( new \TablePress\PhpOffice\PhpSpreadsheet\NamedFormula( 'NUM_ROWS', $worksheet, $num_rows ) );
			$num_columns = (string) count( $table_data[0] );
			$spreadsheet->addNamedFormula( new \TablePress\PhpOffice\PhpSpreadsheet\NamedFormula( 'NUM_COLUMNS', $worksheet, $num_columns ) );
			$spreadsheet->addNamedFormula( new \TablePress\PhpOffice\PhpSpreadsheet\NamedFormula( 'ROW', $worksheet, '=ROW()' ) );
			$spreadsheet->addNamedFormula( new \TablePress\PhpOffice\PhpSpreadsheet\NamedFormula( 'COLUMN', $worksheet, '=COLUMN()' ) );
			$spreadsheet->addNamedFormula( new \TablePress\PhpOffice\PhpSpreadsheet\NamedFormula( 'CELL', $worksheet, '=ADDRESS(ROW(),COLUMN(),4)' ) );
			$spreadsheet->addNamedFormula( new \TablePress\PhpOffice\PhpSpreadsheet\NamedFormula( 'PI', $worksheet, '=PI()' ) );
			$spreadsheet->addNamedFormula( new \TablePress\PhpOffice\PhpSpreadsheet\NamedFormula( 'E', $worksheet, '=EXP(1)' ) );

			// Loop through all table cells and replace formulas with evaluated values.
			$cell_collection = $worksheet->getCellCollection();
			foreach ( $table_data as $row_idx => &$row ) {
				foreach ( $row as $column_idx => &$cell_content ) {
					if ( strlen( $cell_content ) > 1 && '=' === $cell_content[0] ) {
						// Adapted from \TablePress\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::rangeToArray().
						$cell_reference = \TablePress\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex( $column_idx + 1 ) . ( $row_idx + 1 );
						if ( $cell_collection->has( $cell_reference ) ) {
							$cell = $cell_collection->get( $cell_reference );
							try {
								$cell_content = (string) $cell->getCalculatedValue();
							} catch ( \TablePress\PhpOffice\PhpSpreadsheet\Calculation\Exception $exception ) {
								$message = str_replace( 'Worksheet!', '', $exception->getMessage() );
								$cell_content = "!ERROR! {$message}";
							}
						}
					}
				}
			}
			unset( $row, $cell_content ); // Unset use-by-reference parameters of foreach loops.

			// Save PHP memory.
			$spreadsheet->disconnectWorksheets();
			unset( $cell_collection, $worksheet, $spreadsheet );
		} catch ( \TablePress\PhpOffice\PhpSpreadsheet\Calculation\Exception $exception ) {
			$message = str_replace( 'Worksheet!', '', $exception->getMessage() );
			$table_data = array( array( "!ERROR! {$message}" ) );
		}

		return $table_data;
	}

} // class TablePress_Evaluate_PHPSpreadsheet
