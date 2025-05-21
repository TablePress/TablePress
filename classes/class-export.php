<?php
/**
 * TablePress Table Export Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Table Export Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Export {

	/**
	 * File/Data Formats that are available for the export.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	public array $export_formats = array();

	/**
	 * Delimiters for the CSV export.
	 *
	 * @since 1.0.0
	 * @var array<string, string>
	 */
	public array $csv_delimiters = array();

	/**
	 * Whether ZIP archive support is available in the PHP installation on the server.
	 *
	 * @since 1.0.0
	 */
	public bool $zip_support_available = false;

	/**
	 * Initialize the Export class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Initiate here, because function call not possible outside a class method.
		$this->export_formats = array(
			'csv'  => __( 'CSV - Character-Separated Values', 'tablepress' ),
			'html' => __( 'HTML - Hypertext Markup Language', 'tablepress' ),
			'json' => __( 'JSON - JavaScript Object Notation', 'tablepress' ),
		);
		$this->csv_delimiters = array(
			';'   => __( '; (semicolon)', 'tablepress' ),
			','   => __( ', (comma)', 'tablepress' ),
			'tab' => __( '\t (tabulator)', 'tablepress' ),
		);

		if ( class_exists( 'ZipArchive', false ) ) {
			$this->zip_support_available = true;
		}
	}

	/**
	 * Export a table.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $table         Table to be exported.
	 * @param string               $export_format Format for the export ('csv', 'html', 'json').
	 * @param string               $csv_delimiter Delimiter for CSV export.
	 * @return string Exported table (only data for CSV and HTML, full tables (including options) for JSON).
	 */
	public function export_table( array $table, string $export_format, string $csv_delimiter ): string {
		switch ( $export_format ) {
			case 'csv':
				$output = '';
				if ( 'tab' === $csv_delimiter ) {
					$csv_delimiter = "\t";
				}
				foreach ( $table['data'] as $row_idx => $row ) {
					$csv_row = array();
					foreach ( $row as $column_idx => $cell_content ) {
						$csv_row[] = $this->csv_wrap_and_escape( $cell_content, $csv_delimiter );
					}
					$output .= implode( $csv_delimiter, $csv_row );
					$output .= "\n";
				}
				break;
			case 'html':
				$num_rows = count( $table['data'] );
				$last_row_idx = $num_rows - 1;
				$thead = '';
				$tfoot = '';
				$tbody = array();

				foreach ( $table['data'] as $row_idx => $row ) {
					// Table head rows, but only if there's at least one additional row.
					if ( $row_idx < $table['options']['table_head'] && $num_rows > $table['options']['table_head'] ) {
						$thead = $this->html_render_row( $row, 'th' );
						continue;
					}
					// Table foot rows, but only if there's at least one additional row.
					if ( $row_idx > $last_row_idx - $table['options']['table_foot'] && $num_rows > $table['options']['table_foot'] ) {
						$tfoot = $this->html_render_row( $row, 'th' );
						continue;
					}
					// Neither first nor last row (with respective head/foot enabled), so render as body row.
					$tbody[] = $this->html_render_row( $row, 'td' );
				}

				// <thead>, <tfoot>, and <tbody> tags.
				if ( ! empty( $thead ) ) {
					$thead = "\t<thead>\n{$thead}\t</thead>\n";
				}
				if ( ! empty( $tfoot ) ) {
					$tfoot = "\t<tfoot>\n{$tfoot}\t</tfoot>\n";
				}
				$tbody = "\t<tbody>\n" . implode( '', $tbody ) . "\t</tbody>\n";

				$output = "<table>\n" . $thead . $tfoot . $tbody . "</table>\n";
				break;
			case 'json':
				$output = wp_json_encode( $table, TABLEPRESS_JSON_OPTIONS );
				if ( false === $output ) {
					$output = '';
				}
				break;
			default:
				$output = '';
		}

		return $output;
	}

	/**
	 * Wrap and escape a cell for CSV export.
	 *
	 * @since 1.0.0
	 *
	 * @param string $cell_content Content of a cell.
	 * @param string $delimiter    CSV delimiter character.
	 * @return string Wrapped string for CSV export.
	 */
	protected function csv_wrap_and_escape( string $cell_content, string $delimiter ): string {
		// Return early if the cell is empty. No escaping or wrapping is needed then.
		if ( '' === $cell_content ) {
			return $cell_content;
		}

		// Escape potentially dangerous functions that could be used for CSV injection attacks in external spreadsheet software.
		$active_content_triggers = array( '=', '+', '-', '@' );
		if ( in_array( $cell_content[0], $active_content_triggers, true ) ) {
			$functions_to_escape = array(
				'cmd|',
				'rundll32',
				'DDE(',
				'IMPORTXML(',
				'IMPORTFEED(',
				'IMPORTHTML(',
				'IMPORTRANGE(',
				'IMPORTDATA(',
				'IMAGE(',
				'HYPERLINK(',
				'WEBSERVICE(',
			);

			$fn_stripos = function_exists( 'mb_stripos' ) ? 'mb_stripos' : 'stripos';

			foreach ( $functions_to_escape as $function ) {
				if ( false !== $fn_stripos( $cell_content, $function ) ) {
					$cell_content = "'" . $cell_content; // Prepend a ' to indicate that the cell format is a text string.
					break;
				}
			}
		}

		// Escape CSV delimiter for RegExp (e.g. '|').
		$delimiter = preg_quote( $delimiter, '#' );
		if ( 1 === preg_match( '#' . $delimiter . '|"|\n|\r#i', $cell_content ) || str_starts_with( $cell_content, ' ' ) || str_ends_with( $cell_content, ' ' ) ) {
			// Escape single " as double "".
			$cell_content = str_replace( '"', '""', $cell_content );
			// Wrap string in "".
			$cell_content = '"' . $cell_content . '"';
		}

		return $cell_content;
	}

	/**
	 * Generate the HTML of a row.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $row Cells of the row to be rendered.
	 * @param string   $tag HTML tag to use for the cells (td or th).
	 * @return string HTML code for the row.
	 */
	protected function html_render_row( array $row, string $tag ): string {
		$output = "\t\t<tr>\n";
		array_walk( $row, array( $this, 'html_wrap_and_escape' ), $tag );
		$output .= implode( '', $row );
		$output .= "\t\t</tr>\n";
		return $output;
	}

	/**
	 * Wrap and escape a cell for HTML export.
	 *
	 * @since 1.0.0
	 *
	 * @param string $cell_content Content of a cell.
	 * @param int    $column_idx   Column index, or -1 if omitted. Unused, but defined to be able to use function as callback in array_walk().
	 * @param string $html_tag     HTML tag that shall be used for the cell.
	 */
	protected function html_wrap_and_escape( string &$cell_content, int $column_idx, string $html_tag ): void {
		/*
		 * Replace any & with &amp; that is not already an encoded entity (from function htmlentities2 in WP 2.8).
		 * A complete htmlentities2() or htmlspecialchars() would encode <HTML> tags, which we don't want.
		 */
		$cell_content = (string) preg_replace( '/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};)/', '&amp;', $cell_content );
		$cell_content = "\t\t\t<{$html_tag}>{$cell_content}</{$html_tag}>\n";
	}

} // class TablePress_Export
