<?php
/**
 * TablePress Table Export Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @author Tobias Bäthge
 * @since 1.0.0
 */

declare(strict_types=1);

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
	 * Available exporters for the export.
	 *
	 * @since 3.4.0
	 * @var array<string, array{label: string, class: string, file: string, folder: string}>
	 */
	protected array $exporters = array();

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
	 * Initializes the Export class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Initiate here, because function call not possible outside a class method.
		$this->exporters = array(
			'csv'  => array(
				'label'  => __( 'CSV - Character-Separated Values', 'tablepress' ),
				'class'  => \TablePress\Export\CSV_Exporter::class,
				'file'   => 'class-exporter-csv.php',
				'folder' => 'classes/exporters',
			),
			'html' => array(
				'label'  => __( 'HTML - Hypertext Markup Language', 'tablepress' ),
				'class'  => \TablePress\Export\HTML_Exporter::class,
				'file'   => 'class-exporter-html.php',
				'folder' => 'classes/exporters',
			),
			'json' => array(
				'label'  => __( 'JSON - JavaScript Object Notation', 'tablepress' ),
				'class'  => \TablePress\Export\JSON_Exporter::class,
				'file'   => 'class-exporter-json.php',
				'folder' => 'classes/exporters',
			),
		);

		/**
		 * Filters the available export formats.
		 *
		 * @since 3.4.0
		 *
		 * @param array<string, array{label: string, class: string, file: string, folder: string}> $exporters Associative array of available export formats.
		 */
		$this->exporters = apply_filters( 'tablepress_exporters', $this->exporters );

		foreach ( $this->exporters as $format => $exporter ) {
			$this->export_formats[ $format ] = $exporter['label'];
		}

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
	 * Exports a table to the specified format using the corresponding exporter class.
	 *
	 * @since 1.0.0
	 * @since 3.4.0 The $options parameter is now an array. If a string is passed, it is treated as the CSV delimiter (deprecated).
	 *
	 * @param array<string, mixed>        $table         Table to be exported.
	 * @param string                      $export_format Format for the export, e.g. 'csv', 'html', or 'json'.
	 * @param array<string, mixed>|string $options       Options for the export if an array, or the CSV delimiter if a string. The latter is deprecated.
	 * @return string Exported table data.
	 */
	public function export_table( array $table, string $export_format, /* array|string */ $options ): string {
		if ( ! isset( $this->exporters[ $export_format ] ) ) {
			return '';
		}

		if ( ! is_array( $options ) ) {
			$options = array(
				'csv_delimiter' => $options,
			);
		}

		\TablePress::load_file( 'interface-exporter.php', 'classes/exporters' );

		if ( '' !== $this->exporters[ $export_format ]['file'] ) {
			\TablePress::load_file( $this->exporters[ $export_format ]['file'], $this->exporters[ $export_format ]['folder'] );
		}

		$exporter_class = $this->exporters[ $export_format ]['class'];
		$exporter = new $exporter_class();

		return $exporter->export( $table, $options );
	}

} // class TablePress_Export
