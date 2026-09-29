<?php
/**
 * TablePress HTML Exporter Class
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
 * TablePress HTML Exporter Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @since 3.4.0
 */
class HTML_Exporter implements Exporter_Interface {

	/**
	 * Exports a table to HTML format.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $table   Table to be exported.
	 * @param array<string, mixed> $options Format-specific options for the export.
	 * @return string Exported table.
	 */
	public function export( array $table, array $options = array() ): string {
		$num_rows = count( $table['data'] );
		$last_row_idx = $num_rows - 1;
		$thead = '';
		$tfoot = '';
		$tbody = array();

		foreach ( $table['data'] as $row_idx => $row ) {
			// Table head rows, but only if there's at least one additional row.
			if ( $row_idx < $table['options']['table_head'] && $num_rows > $table['options']['table_head'] ) {
				$thead = $this->render_row( $row, 'th' );
				continue;
			}
			// Table foot rows, but only if there's at least one additional row.
			if ( $row_idx > $last_row_idx - $table['options']['table_foot'] && $num_rows > $table['options']['table_foot'] ) {
				$tfoot = $this->render_row( $row, 'th' );
				continue;
			}
			// Neither first nor last row (with respective head/foot enabled), so render as body row.
			$tbody[] = $this->render_row( $row, 'td' );
		}

		// <thead>, <tbody>, and <tfoot> tags.
		if ( ! empty( $thead ) ) {
			$thead = "\t<thead>\n{$thead}\t</thead>\n";
		}
		$tbody = "\t<tbody>\n" . implode( '', $tbody ) . "\t</tbody>\n";
		if ( ! empty( $tfoot ) ) {
			$tfoot = "\t<tfoot>\n{$tfoot}\t</tfoot>\n";
		}

		return "<table>\n" . $thead . $tbody . $tfoot . "</table>\n";
	}

	/**
	 * Generates the HTML code of a row.
	 *
	 * @since 3.4.0
	 *
	 * @param string[] $row Cells of the row to be rendered.
	 * @param string   $tag HTML tag to use for the cells (td or th).
	 * @return string HTML code for the row.
	 */
	protected function render_row( array $row, string $tag ): string {
		$output = "\t\t<tr>\n";
		array_walk( $row, array( $this, 'wrap_and_escape' ), $tag );
		$output .= implode( '', $row );
		$output .= "\t\t</tr>\n";
		return $output;
	}

	/**
	 * Wraps and escapes a cell for HTML export.
	 *
	 * @since 3.4.0
	 *
	 * @param string $cell_content Content of a cell.
	 * @param int    $column_idx   Column index, or -1 if omitted. Unused, but defined to be able to use function as callback in array_walk().
	 * @param string $html_tag     HTML tag that shall be used for the cell.
	 */
	protected function wrap_and_escape( string &$cell_content, int $column_idx, string $html_tag ): void {
		/*
		 * Replace any & with &amp; that is not already an encoded entity (from function htmlentities2 in WP 2.8).
		 * A complete htmlentities2() or htmlspecialchars() would encode <HTML> tags, which we don't want.
		 */
		$cell_content = (string) preg_replace( '/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,4};)/', '&amp;', $cell_content );
		$cell_content = "\t\t\t<{$html_tag}>{$cell_content}</{$html_tag}>\n";
	}

} // class HTML_Exporter
