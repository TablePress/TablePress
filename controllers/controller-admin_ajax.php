<?php
/**
 * Admin AJAX Controller for TablePress with functionality for the AJAX backend
 *
 * @package TablePress
 * @subpackage Admin AJAX Controller
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Admin AJAX Controller class, extends Base Controller Class
 *
 * @since 1.0.0
 */
class TablePress_Admin_AJAX_Controller extends TablePress_Controller {

/*	protected $table = array();
	protected $known_ranges = array();
*/
	/**
	 * Initiate Admin AJAX functionality
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		// $this->model_table = TablePress::load_model( 'table' ); // add this where needed, actually rather here, for post_type support

		$ajax_actions = array( 'hide_message' ); //array( 'hide_message', 'save_table', 'preview_table' );
		foreach ( $ajax_actions as $action ) {
			add_action( "wp_ajax_tablepress_{$action}", array( &$this, "ajax_action_{$action}" ) );
		}
	}

	/**
	 * Hide a header message on an admin screen
	 *
	 * @since 1.0.0
	 */ 
	public function ajax_action_hide_message() {
		if ( empty( $_GET['item'] ) )
			die( '0' );
		else
			$message_item =  $_GET['item'];

		TablePress::check_nonce( 'hide_message', $message_item, true );

		$this->model_options->update( "message_{$message_item}", false );
		die( '1' );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
/*	public function ajax_action_save_table() {
		sleep( 3 ); // 3s Dummy Pause, zum Testen von AJAX-Sendeverhalten
	
		// check to see if the submitted nonce matches with the generated nonce we created earlier
		if ( ! check_ajax_referer( 'tp-save-table', false, false ) )
			die( '-1' );
	 
		// ignore the request if the current user doesn't have sufficient permissions
		//if ( ! current_user_can( 'edit_tp_tables' ) )
		//	die( '-1' );
	
		$_POST['tp'] = stripslashes_deep( $_POST['tp'] );
	
		$table['id'] = $_POST['tp']['id'];
		$table['rows'] = (int)$_POST['tp']['rows'];
		$table['columns'] = (int)$_POST['tp']['columns'];
		$table['visibility'] = json_decode( $_POST['tp']['visibility'], true );
		$table['options'] = json_decode( $_POST['tp']['options'], true );
		$table['data'] = json_decode( $_POST['tp']['data'], true );
	
		// make checks
		$success = true;
		
		// Number of rows and columns
		if ( $table['rows'] != count( $table['data'] ) )
			$success = false;
		if ( $table['columns'] != count( $table['data'][0] ) )
			$success = false;
	
		// Number of rows and columns for visibility arrays
		if ( $table['rows'] != count( $table['visibility']['rows'] ) )
			$success = false;
		if ( $table['columns'] != count( $table['visibility']['columns'] ) )
			$success = false;
	
		// Number of hidden and visible rows
		$visibility_rows = array_count_values( $table['visibility']['rows'] );
		if ( $table['visibility']['hidden_rows'] != $visibility_rows[ 0 ] )
			$success = false;
		if ( ( $table['rows'] - $table['visibility']['hidden_rows'] ) != $visibility_rows[ 1 ] )
			$success = false;
	
		// Number of hidden and visible columns
		$visibility_columns = array_count_values( $table['visibility']['columns'] );
		if ( $table['visibility']['hidden_columns'] != $visibility_columns[ 0 ] )
			$success = false;
		if ( ( $table['columns'] - $table['visibility']['hidden_columns'] ) != $visibility_columns[ 1 ] )
			$success = false;
	
		// save stuff here
	
		// generate the response
		$response = array(
			'success' => $success,
			'table' => $table
		);
	
		// response output
		header( 'Content-Type: application/json' );
		echo json_encode( $response );
		
		exit;
	}
*/
	/**
	 *
	 *
	 * @since 1.0.0
	 */
/*	public function ajax_action_preview_table() {
		sleep( 3 ); // 3s Dummy Pause, zum Testen von AJAX-Sendeverhalten
	
		// check to see if the submitted nonce matches with the generated nonce we created earlier
		if ( ! check_ajax_referer( 'tp-preview-table', false, false ) )
			die( '-1' );
	 
		// ignore the request if the current user doesn't have sufficient permissions
		//if ( ! current_user_can( 'preview_tp_tables' ) )
		//	die( '-1' );

		$_POST['tp'] = stripslashes_deep( $_POST['tp'] );

		require_once ( TABLEPRESS_ABSPATH . 'libraries/evalmath.class.php' );
	
		$table['id'] = $_POST['tp']['id'];
		$table['rows'] = (int)$_POST['tp']['rows'];
		$table['columns'] = (int)$_POST['tp']['columns'];
		$table['visibility'] = json_decode( $_POST['tp']['visibility'], true );
		$table['options'] = json_decode( $_POST['tp']['options'], true );
		$table['data'] = json_decode( $_POST['tp']['data'], true );

		// make checks
		$success = true;

		// Number of rows and columns
		if ( $table['rows'] != count( $table['data'] ) )
			$success = false;
		if ( $table['columns'] != count( $table['data'][0] ) )
			$success = false;

		// Number of rows and columns for visibility arrays
		if ( $table['rows'] != count( $table['visibility']['rows'] ) )
			$success = false;
		if ( $table['columns'] != count( $table['visibility']['columns'] ) )
			$success = false;

		// Number of hidden and visible rows
		$visibility_rows = array_count_values( $table['visibility']['rows'] );
		if ( $table['visibility']['hidden_rows'] != $visibility_rows[ 0 ] )
			$success = false;
		if ( ( $table['rows'] - $table['visibility']['hidden_rows'] ) != $visibility_rows[ 1 ] )
			$success = false;

		// Number of hidden and visible columns
		$visibility_columns = array_count_values( $table['visibility']['columns'] );
		if ( $table['visibility']['hidden_columns'] != $visibility_columns[ 0 ] )
			$success = false;
		if ( ( $table['columns'] - $table['visibility']['hidden_columns'] ) != $visibility_columns[ 1 ] )
			$success = false;

		if ( true === $success ) {
			$this->table = $table['data'];
			$html = $this->_render_table();
		} else {
			$html = '';
		}
	
		// generate the response
		$response = array(
			'success' => $success,
			'head_html' => "<style>#tp-preview-result {
		border-collapse: collapse;
		border: 2px solid #000;
		margin: 10px auto;
	}
	
	#tp-preview-result td,
	#tp-preview-result th {
		box-sizing: border-box;
		width: 200px;
		border: 1px solid #ddd;
		padding: 3px;
	}
	
	#tp-preview-result td:first-child,
	#tp-preview-result th:first-child {
		font-weight: bold;
		text-align: center;
		width: 50px;
	}
	<style>",
			'body_html' => $html
		);
	
		// response output
		header( 'Content-Type: application/json' );
		echo json_encode( $response );
		
		exit;
	}

	protected function _render_table() {
		foreach ( $this->table as $row_idx => $row ) {
			foreach ( $row as $col_idx => $cell_dummy ) {
				$this->table[$row_idx][$col_idx] = $this->_parse_evaluate( $this->table[$row_idx][$col_idx] );		
			}
		}
		
		return $this->_print_table();
	}
*/
/*	protected function _parse_evaluate( $content, $parents = array() ) {
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
				$first_col = $this->letter_to_number( $cell_range[1] );
				$first_row = $cell_range[2];
				$last_col = $this->letter_to_number( $cell_range[3] );
				$last_row = $cell_range[4];
	
				$col_start = min( $first_col, $last_col );
				$col_end = max( $first_col, $last_col ) + 1; // +1 for loop below
				$row_start = min( $first_row, $last_row );
				$row_end = max( $first_row, $last_row ) + 1; // +1 for loop below
	
	
				$cell_list = array();
				for ( $col = $col_start; $col < $col_end; $col++ ) {
					for ( $row = $row_start; $row < $row_end; $row++ ) {
						$column = $this->number_to_letter( $col );
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
	
				$ref_col = letter_to_number( $cell_reference[1] ) - 1;
				$ref_row = $cell_reference[2] - 1;
	
				if ( ! ( isset( $this->table[$ref_row] ) && isset( $this->table[$ref_row][$ref_col] ) ) )
					return '!ERROR! Non-Existing Cell';
	
				$ref_parents = $parents;
				$ref_parents[] = $cell_reference[0];
	
				$result = $this->table[$ref_row][$ref_col] = $this->_parse_evaluate( $this->table[$ref_row][$ref_col], $ref_parents );
				if ( false !== strpos( $result, '!ERROR!' ) )
					return $result;
	
				$expression = str_replace( $cell_reference[0], $result, $expression );
			}
		}
	
		return $this->_evaluate( $expression );
	}
*/

/*	protected function _print_table() {
		$output = '<table id="tp-preview-result"><thead><tr><th></th>';
		foreach ( $this->table[0] as $col_idx => $dummy ) {
			$column = $this->number_to_letter( $col_idx+1 );
			$output .= "\t\t\t<th>{$column}</th>\n";
		}

		$output .= '</tr></thead><tbody>';

		foreach ( $this->table as $row_idx => $row ) {
			$row_number = $row_idx + 1;
			$output .= "\t\t<tr>\n";
			$output .= "\t\t\t<td>{$row_number}</td>";
			foreach ( $row as $col_idx => $cell ) {
				// print formula:
				if ( strlen( $cell) > 2 && "'=" == substr( $cell, 0, 2 ) )
					$cell = substr( $cell, 1 );
				$output .= "<td>{$cell}</td>";
			}	
			$output .= "\t\t</tr>\n";
		}

		$output .= '</tbody></table>';
		return $output;
	}
*/
	/**
	 * Evaluate a math expression
	 *
	 * @param string $expression without leading =
	 */
/*	protected function _evaluate( $expression ) {
		$em = new EvalMath( true, false ); // ( $allowconstants (for pi and e), $allowimplicitmultiplication )
		$em->suppress_errors = true; // don't raise PHP warnings
	
		// straight up evaluation, without parsing of variable or function assignments
		$result = $em->pfx( $em->nfx( $expression ) );
		if ( false === $result )
			return '!ERROR! ' . $em->last_error;
	
		return $result;
	}
*/
} // class TablePress_Admin_AJAX_Controller