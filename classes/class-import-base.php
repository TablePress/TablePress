<?php
/**
 * TablePress Table Import Base Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Table Import Base Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @author Tobias Bäthge
 * @since 2.0.0
 */
abstract class TablePress_Import_Base {

	/**
	 * Makes sure that a passed table array is rectangular with all rows having the same number of columns (the highest one that is found).
	 *
	 * This function uses call by reference to save PHP memory on large arrays.
	 *
	 * @since 1.0.0
	 * @since 2.0.0 The $an_array parameter is handled by reference.
	 *
	 * @param array $an_array Two-dimensional array to be padded.
	 */
	public function pad_array_to_max_cols( array &$an_array ) {
		$max_columns = $this->count_max_columns( $an_array );
		// Extend the array to at least one column.
		$max_columns = max( 1, $max_columns );
		array_walk(
			$an_array,
			static function( &$row, $col_idx ) use ( $max_columns ) {
				$row = array_pad( $row, $max_columns, '' );
			}
		);
	}

	/**
	 * Get the highest number of columns in the rows.
	 *
	 * @since 1.0.0
	 *
	 * @param array $an_array Two-dimensional array.
	 * @return int Highest number of columns in the rows of the array.
	 */
	protected function count_max_columns( array $an_array ) {
		$max_columns = 0;
		foreach ( $an_array as $row_idx => $row ) {
			$num_columns = count( $row );
			$max_columns = max( $num_columns, $max_columns );
		}
		return $max_columns;
	}

	/**
	 * Replaces Windows line breaks \r\n with Unix line breaks \n.
	 *
	 * This function uses call by reference to save PHP memory on large arrays.
	 *
	 * @since 2.0.0
	 *
	 * @param array $an_array Two-dimensional array to be padded.
	 */
	protected function normalize_line_endings( array &$an_array ) {
		array_walk_recursive(
			$an_array,
			static function( &$cell_content, $col_idx ) {
				$cell_content = str_replace( "\r\n", "\n", $cell_content );
			}
		);
	}

} // class TablePress_Import_Base
