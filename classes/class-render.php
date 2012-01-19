<?php
/**
 * TablePress Rendering Class
 *
 * @package TablePress
 * @subpackage Rendering
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Rendering Class
 * @package TablePress
 * @subpackage Rendering
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Render {

	/**
	 * Table data that is rendered
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $table;

	/**
	 * Rendered HTML of the table
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $output;

	/**
	 * Instance of EvalMath class
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	protected $evalmath;

	/**
	 * Initialize the Rendering class, include the EvalMath class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->evalmath = TablePress::load_class( 'EvalMath', 'evalmath.class.php', 'libraries', true ); // true for some default constants
		$this->evalmath->suppress_errors = true; // don't raise PHP warnings
	}

	/**
	 * Set the table (data, options, visibility, ...) that is to be rendered
	 *
	 * @since 1.0.0
	 *
	 * @param array $table Table to be rendered
	 */
	public function set_input( $table ) {
		$this->table = $table;
	}

	/**
	 * Get the rendered HTML
	 *
	 * @since 1.0.0
	 *
	 * @return string|false HTML of the rendered table on success, false on error
	 */
	public function get_output() {
		$this->_evaluate_table_data();
		$this->_render_table();
		return $this->output;
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	protected function _evaluate_table_data() {
		foreach ( $this->table['data'] as $row_idx => $row ) {
			foreach ( $row as $col_idx => $cell_dummy ) {
				$this->table['data'][$row_idx][$col_idx] = $this->_parse_evaluate( $this->table['data'][$row_idx][$col_idx] );
			}
		}
	}

	/**
	 * Parse and evaluate the content of a cell
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Content of a cell
	 * @param array $parents List of cells that depend on this cell
	 */
	protected function _parse_evaluate( $content, $parents = array() ) {
		if ( ( '' == $content ) || ( '=' != $content[0] ) )
			return $content;

		$expression = substr( $content, 1 );

		if ( false !== strpos( $expression, '=' ) )
			return '!ERROR! Too many "="';

		if ( false !== strpos( $expression, '][' ) )
			return '!ERROR! Two cell references next to each other';

		$replaced_references = $replaced_ranges = array();

		$expression = preg_replace( '#\s#', '', $expression );

		// cell ranges (like [A3:B6]
		if ( preg_match_all( '#\[([a-z]+)([0-9]+):([a-z]+)([0-9]+)\]#i', $expression, $referenced_cell_ranges, PREG_SET_ORDER ) ) {
			foreach ( $referenced_cell_ranges as $cell_range ) {
				if ( in_array( $cell_range[0], $replaced_ranges ) )
					continue;

				$replaced_ranges[] = $cell_range[0];

				if ( isset( $this->known_ranges[ $cell_range[0] ] ) ) {
					$expression = str_replace( $cell_range[0], $this->known_ranges[ $cell_range[0] ], $expression );
					continue;
				}

				// no -1 necessary for this transformation, as we don't actually access the table
				$first_col = TablePress::letter_to_number( $cell_range[1] );
				$first_row = $cell_range[2];
				$last_col = TablePress::letter_to_number( $cell_range[3] );
				$last_row = $cell_range[4];

				$col_start = min( $first_col, $last_col );
				$col_end = max( $first_col, $last_col ) + 1; // +1 for loop below
				$row_start = min( $first_row, $last_row );
				$row_end = max( $first_row, $last_row ) + 1; // +1 for loop below


				$cell_list = array();
				for ( $col = $col_start; $col < $col_end; $col++ ) {
					for ( $row = $row_start; $row < $row_end; $row++ ) {
						$column = TablePress::number_to_letter( $col );
						$cell_list[] = "[{$column}{$row}]";
					}
				}
				$cell_list = implode( ',', $cell_list );

				$expression = str_replace( $cell_range[0], $cell_list, $expression );
				$this->known_ranges[ $cell_range[0] ] = $cell_list;
			}
		}

		// single cell references (like [A3] or [XY312]
		if ( preg_match_all( '#\[([a-z]+)([0-9]+)\]#i', $expression, $referenced_cells, PREG_SET_ORDER ) ) {
			foreach ( $referenced_cells as $cell_reference ) {
				if ( in_array( $cell_reference[0], $parents ) )
					return '!ERROR! Circle Reference';

				if ( in_array( $cell_reference[0], $replaced_references ) )
					continue;

				$replaced_references[] = $cell_reference[0];

				$ref_col = TablePress::letter_to_number( $cell_reference[1] ) - 1;
				$ref_row = $cell_reference[2] - 1;

				if ( ! ( isset( $this->table['data'][$ref_row] ) && isset( $this->table['data'][$ref_row][$ref_col] ) ) )
					return '!ERROR! Non-Existing Cell';

				$ref_parents = $parents;
				$ref_parents[] = $cell_reference[0];

				$result = $this->table['data'][$ref_row][$ref_col] = $this->_parse_evaluate( $this->table['data'][$ref_row][$ref_col], $ref_parents );
				if ( false !== strpos( $result, '!ERROR!' ) )
					return $result;

				$expression = str_replace( $cell_reference[0], $result, $expression );
			}
		}

		return $this->_evaluate( $expression );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	protected function _render_table() {
		$this->output = "Table {$this->table['name']} (ID {$this->table['id']})<br/>";
		$this->output .= '<table id="preview-table"><thead><tr><th></th>';
		foreach ( $this->table['data'][0] as $col_idx => $dummy ) {
			$column = TablePress::number_to_letter( $col_idx+1 );
			$this->output .= "\t\t\t<th>{$column}</th>\n";
		}

		$this->output .= '</tr></thead><tbody>';

		foreach ( $this->table['data'] as $row_idx => $row ) {
			$row_number = $row_idx + 1;
			$this->output .= "\t\t<tr>\n";
			$this->output .= "\t\t\t<td>{$row_number}</td>";
			foreach ( $row as $col_idx => $cell ) {
				// print formulas that are escaped with '= (like in Excel) as text:
				if ( strlen( $cell) > 2 && "'=" == substr( $cell, 0, 2 ) )
					$cell = substr( $cell, 1 );
				$this->output .= "<td>{$cell}</td>";
			}
			$this->output .= "\n\t\t</tr>\n";
		}

		$this->output .= '</tbody></table>';
	}

	/**
	 * Evaluate a math expression
	 *
	 * @param string $expression without leading =
	 * @return string Result of the evaluation
	 */
	protected function _evaluate( $expression ) {
		// straight up evaluation, without parsing of variable or function assignments (which is why we only need one instance of the object)
		$result = $this->evalmath->pfx( $this->evalmath->nfx( $expression ) );
		if ( false === $result )
			return '!ERROR! ' . $this->evalmath->last_error;
		else
			return $result;
	}

	/**
	 * Get the CSS code for the Preview iframe
	 *
	 * @since 1.0.0
	 *
	 * @return string CSS for the Preview iframe
	 */
	public function get_preview_css() {
		return <<<CSS
<style>
#preview-table {
	border-collapse: collapse;
	border: 2px solid #000;
	margin: 10px auto;
}
#preview-table td,
#preview-table th {
	box-sizing: border-box;
	width: 200px;
	border: 1px solid #ddd;
	padding: 3px;
}
#preview-table td:first-child,
#preview-table th:first-child {
	font-weight: bold;
	text-align: center;
	width: 50px;
}
</style>
CSS;
	}

} // class TablePress_Render