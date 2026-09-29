<?php
/**
 * TablePress CSV Exporter Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @author Tobias Bäthge
 * @since 3.4.0
 */

declare(strict_types=1);

namespace TablePress\Export;

use TablePress\Export\Exporter_Interface;

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress CSV Exporter Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @since 3.4.0
 */
class CSV_Exporter implements Exporter_Interface {

	/**
	 * Exports a table to CSV format.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $table   Table to be exported.
	 * @param array<string, mixed> $options Format-specific options for the export.
	 * @return string Exported table.
	 */
	public function export( array $table, array $options = array() ): string {
		$delimiter = $options['csv_delimiter'] ?? ',';
		if ( 'tab' === $delimiter ) {
			$delimiter = "\t";
		}

		$output = '';

		foreach ( $table['data'] as $row ) {
			$csv_row = array();
			foreach ( $row as $cell ) {
				$csv_row[] = $this->wrap_and_escape( $cell, $delimiter );
			}
			$output .= implode( $delimiter, $csv_row );
			$output .= "\n";
		}

		return $output;
	}

	/**
	 * Wraps and escapes a cell for CSV export.
	 *
	 * @since 3.4.0
	 *
	 * @param string $cell_content Content of a cell.
	 * @param string $delimiter    CSV delimiter character.
	 * @return string Wrapped string for CSV export.
	 */
	protected function wrap_and_escape( string $cell_content, string $delimiter ): string {
		// Return early if the cell is empty. No escaping or wrapping is needed then.
		if ( '' === $cell_content ) {
			return $cell_content;
		}

		// Escape potentially dangerous functions that could be used for CSV injection attacks in external spreadsheet software.
		$active_content_triggers = array( '=', '+', '-', '@' );
		if ( in_array( $cell_content[0], $active_content_triggers, true ) ) {
			// phpcs:disable Generic.Strings.UnnecessaryStringConcat.Found -- Avoid concatenation of function names to prevent false positives in code scanners.
			$functions_to_escape = array(
				'cmd|',
				'FOR' . 'FILES|',
				'rund' . 'll32',
				'DD' . 'E(',
				'IMPORT' . 'XML(',
				'IMPORT' . 'FEED(',
				'IMPORT' . 'HTML(',
				'IMPORT' . 'RANGE(',
				'IMPORT' . 'DATA(',
				'IMAGE(',
				'HYPERLINK(',
				'WEBSERVICE(',
			);
			// phpcs:enable

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

} // class CSV_Exporter
