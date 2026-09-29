<?php
/**
 * TablePress Exporter Interface
 *
 * @package TablePress
 * @subpackage Export/Import
 * @author Tobias Bäthge
 * @since 3.4.0
 */

declare(strict_types=1);

namespace TablePress\Export;

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Exporter Interface
 *
 * @package TablePress
 * @subpackage Export/Import
 * @since 3.4.0
 */
interface Exporter_Interface {

	/**
	 * Exports a table.
	 *
	 * @since 3.4.0
	 *
	 * @param array<string, mixed> $table   Table to be exported.
	 * @param array<string, mixed> $options Format-specific options for the export.
	 * @return string Exported table output.
	 */
	public function export( array $table, array $options = array() ): string;

} // interface Exporter_Interface
