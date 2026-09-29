<?php
/**
 * TablePress JSON Exporter Class
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
 * TablePress JSON Exporter Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @since 3.4.0
 */
class JSON_Exporter implements Exporter_Interface {

	/**
	 * Exports a table to JSON format.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $table   Table to be exported.
	 * @param array<string, mixed> $options Format-specific options for the export.
	 * @return string Exported table.
	 */
	public function export( array $table, array $options = array() ): string {
		$output = wp_json_encode( $table, TABLEPRESS_JSON_OPTIONS );
		if ( false === $output ) {
			$output = '';
		}
		return $output;
	}

} // class JSON_Exporter
