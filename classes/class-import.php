<?php
/**
 * TablePress Table Import Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Table Import Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Import {

	/**
	 * Instance of the TablePress Legacy Importer.
	 *
	 * @since 1.0.0
	 * @var TablePress_Import_Legacy
	 */
	protected $importer;

	/**
	 * Import configuration (mainly the data from the Import form).
	 *
	 * @since 2.0.0
	 * @var array
	 */
	protected $import_config = array();

	/**
	 * Whether ZIP archive support is available in the PHP installation on the server.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	public $zip_support_available = false;

	/**
	 * List of table names/IDs for use when replacing/appending existing tables (except for the JSON format).
	 *
	 * @since 2.0.0
	 * @var array
	 */
	protected $table_names_ids = array();

	/**
	 * Initializes the Import class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		/** This filter is documented in the WordPress function unzip_file() in wp-admin/includes/file.php */
		if ( class_exists( 'ZipArchive', false ) && apply_filters( 'unzip_file_use_ziparchive', true ) ) {
			$this->zip_support_available = true;
		}
	}

	/**
	 * Runs the import process for a given import configuration.
	 *
	 * @since 2.0.0
	 *
	 * @param array $import_config Import configuration.
	 * @return array|WP_Error List of imported tables on success, WP_Error on failure.
	 */
	public function run( array $import_config ) {
		// Unziping can use a lot of memory and execution time, but not this much hopefully.
		wp_raise_memory_limit( 'admin' );
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$this->import_config = $import_config;

		$import_files = $this->_get_import_files();
		if ( is_wp_error( $import_files ) ) {
			return $import_files;
		}

		if ( in_array( $this->import_config['type'], array( 'replace', 'append' ), true ) ) {
			$this->table_names_ids = $this->_get_list_of_table_names();
		}

		$import_files = $this->_convert_zip_files( $import_files );

		return $this->_import_files( $import_files );
	}

	/**
	 * Extracts the files that shall be imported from the import configuration.
	 *
	 * @since 2.0.0
	 *
	 * @return array|WP_Error Files that shall be imported or WP_Error on failure.
	 */
	protected function _get_import_files() {
		$import_files = array();

		switch ( $this->import_config['source'] ) {
			case 'file-upload':
				foreach ( $this->import_config['file-upload']['error'] as $key => $error ) {
					$file = array(
						'location' => $this->import_config['file-upload']['tmp_name'][ $key ],
						'name'     => $this->import_config['file-upload']['name'][ $key ],
					);
					if ( UPLOAD_ERR_OK !== $error ) {
						@unlink( $this->import_config['file-upload']['tmp_name'][ $key ] );
						$file['error'] = new WP_Error( 'table_import_file-upload_error', '', $error );
					}
					$import_files[] = $file;
				}
				break;
			case 'url':
				$host = wp_parse_url( $this->import_config['url'], PHP_URL_HOST );

				if ( empty( $host ) ) {
					return new WP_Error( 'table_import_url_host_invalid', '', $this->import_config['url'] );
				}

				// Check the host of the Import URL against a blacklist of hosts, which should not be accessible, e.g. for security considerations.
				$blocked_hosts = array(
					'169.254.169.254', // AWS Meta-data API.
				);
				if ( in_array( $host, $blocked_hosts, true ) ) {
					return new WP_Error( 'table_import_url_host_blocked', '', $this->import_config['url'] );
				}

				/**
				 * Load WP file functions to be sure that `download_url()` exists, in particular during Cron requests.
				 */
				require_once ABSPATH . 'wp-admin/includes/file.php';

				// Download URL to local file.
				$location = download_url( $this->import_config['url'] );
				if ( is_wp_error( $location ) ) {
					$error = new WP_Error( 'table_import_url_download_failed', '', $this->import_config['url'] );
					$error->merge_from( $location );
					return $error;
				}

				$import_files[] = array(
					'location' => $location,
					'name'     => $this->import_config['url'],
				);
				break;
			case 'server':
				if ( ABSPATH === $this->import_config['server'] ) {
					return new WP_Error( 'table_import_server_invalid', '', $this->import_config['server'] );
				}

				if ( ! is_readable( $this->import_config['server'] ) ) {
					return new WP_Error( 'table_import_server_not_readable', '', $this->import_config['server'] );
				}

				$import_files[] = array(
					'location'  => $this->import_config['server'],
					'name'      => pathinfo( $this->import_config['server'], PATHINFO_BASENAME ),
					'keep_file' => true, // Files on the server must not be deleted.
				);
				break;
			case 'form-field':
				$location = wp_tempnam();
				$num_written_bytes = file_put_contents( $location, $this->import_config['form-field'] );
				if ( false === $num_written_bytes || 0 === $num_written_bytes ) {
					@unlink( $location );
					return new WP_Error( 'table_import_form-field_temp_file_not_written' );
				}

				$import_files[] = array(
					'location' => $location,
					'name'     => __( 'Imported from Manual Input', 'tablepress' ),
				);
				break;
			default:
				return new WP_Error( 'table_import_invalid_source', '', $this->import_config['source'] );
		}

		return $import_files;
	}

	/**
	 * Replaces ZIP archives in the import files with a list of their contents.
	 *
	 * ZIP files are removed from the list and their contents are added to the end of the list.
	 *
	 * @since 2.0.0
	 *
	 * @param array $import_files Files that shall be imported, including ZIP archives.
	 * @return array Files that shall be imported, with all ZIP archives recursively replaced by their contents.
	 */
	protected function _convert_zip_files( array $import_files ) {
		/*
		 * Here, a for loop is used over a foreach loop, as the array is modified while being iterated over.
		 * The foreach approach works in PHP 7+ (via https://www.php.net/manual/en/migration70.incompatible.php#migration70.incompatible.foreach.by-ref), but not in PHP 5.6.
		 * Once PHP 7.x is required, this can be adjusted again.
		 */
		$num_files = count( $import_files ); // This number is growing inside the loop, if files are extracted from a ZIP file and appended to the list.
		for ( $key = 0; $key < $num_files; $key++ ) {
			$file = $import_files[ $key ];

			if ( isset( $file['error'] ) && is_wp_error( $file['error'] ) ) {
				continue;
			}

			$file['extension'] = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

			if ( function_exists( 'mime_content_type' ) ) {
				$file['mime_type'] = mime_content_type( $file['location'] );
				if ( false === $file['mime_type'] ) {
					$file['mime_type'] = '';
				}
			} else {
				$file['mime_type'] = '';
			}

			// Detect ZIP files from their file extension or MIME type.
			if ( 'zip' === $file['extension'] || 'application/zip' === $file['mime_type'] ) {
				if ( ! $this->zip_support_available ) {
					$file['error'] = new WP_Error( 'table_import_no_zip_support', '', $file['name'] );
					$this->_maybe_unlink_file( $file );
					continue;
				}

				$extracted_files = $this->_extract_zip_file( $file );
				if ( is_wp_error( $extracted_files ) ) {
					$file['error'] = $extracted_files->get_error_code();
					$this->_maybe_unlink_file( $file );
					continue;
				}

				if ( empty( $extracted_files ) ) {
					$file['error'] = new WP_Error( 'table_import_zip_file_empty', '', $file['name'] );
					$this->_maybe_unlink_file( $file );
					continue;
				}

				$this->_maybe_unlink_file( $file );

				// Mark the ZIP file as removed from the list (null), append its contents to the end, and increase the number of files counter.
				$file = null;
				array_push( $import_files, ...$extracted_files );
				$num_files += count( $extracted_files );

			}

			$import_files[ $key ] = $file;
		}

		// Actually remove files that are marked as removed (null).
		$import_files = array_filter(
			$import_files,
			static function( $file ) {
				return ! is_null( $file );
			}
		);

		$import_files = array_merge( $import_files ); // Re-index.

		return $import_files;
	}

	/**
	 * Extracts the files of a ZIP files to a temporary folder and returns a list of files and their location.
	 *
	 * @since 2.0.0
	 *
	 * @param array $zip_file File data of a ZIP file (likely in a temporary folder).
	 * @return array|WP_Error List of files (name and location where they were extracted to) of the ZIP file or WP_Error on failure.
	 */
	protected function _extract_zip_file( array $zip_file ) {
		$zip = new ZipArchive();
		$zip_opened = $zip->open( $zip_file['location'], ZIPARCHIVE::CHECKCONS );

		// If the ZIP file can't be opened with ZIPARCHIVE::CHECKCONS, try again without.
		if ( true !== $zip_opened ) {
			$zip_opened = $zip->open( $zip_file['location'] );
		}

		// If the ZIP file can't even be opened without ZIPARCHIVE::CHECKCONS, bail.
		if ( true !== $zip_opened ) {
			return new WP_Error( 'table_import_error_zip_open', '', array( 'ziparchive_error' => $zip_opened ) );
		}

		$files = array();

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		for ( $file_idx = 0; $file_idx < $zip->numFiles; $file_idx++ ) {
			$file_name = $zip->getNameIndex( $file_idx );

			if ( false === $file_name ) {
				$files[] = array(
					'location' => '',
					'name'     => '',
					'error'    => new WP_Error( 'table_import_error_zip_stat', '', array( 'ziparchive_file_index' => $file_idx ) ),
				);
				continue;
			}

			// Skip directories.
			if ( '/' === substr( $file_name, -1 ) ) {
				continue;
			}

			// Skip the __MACOSX directory that macOS adds to archives.
			if ( '__MACOSX/' === substr( $file_name, 0, 9 ) ) {
				continue;
			}

			$file_data = $zip->getFromIndex( $file_idx );
			if ( false === $file_data ) {
				$files[] = array(
					'location' => '',
					'name'     => $file_name,
					'error'    => new WP_Error( 'table_import_error_zip_get_data', '', array( 'ziparchive_file_index' => $file_idx, 'ziparchive_file_name' => $file_name ) ),
				);
				continue;
			}

			$location = wp_tempnam();
			$num_written_bytes = file_put_contents( $location, $file_data );
			if ( false === $num_written_bytes || 0 === $num_written_bytes ) {
				@unlink( $location );
				$files[] = array(
					'location' => '',
					'name'     => $file_name,
					'error'    => new WP_Error( 'table_import_error_zip_write_temp_data', '', array( 'ziparchive_file_index' => $file_idx, 'ziparchive_file_name' => $file_name ) ),
				);
				continue;
			}

			$files[] = array(
				'location' => $location,
				'name'     => $file_name,
			);
		}

		$zip->close();

		return $files;
	}

	/**
	 * Deletes a file unless the `keep_file` property is set to false.
	 *
	 * @since 2.0.0
	 *
	 * @param array $file File that should maybe be deleted.
	 */
	protected function _maybe_unlink_file( array $file ) {
		if ( ! isset( $file['keep_file'] ) || ! $file['keep_file'] ) {
			@unlink( $file['location'] );
		}
	}

	/**
	 * Prepares a list of table names/IDs for use when replacing/appending existing tables (except for the JSON format).
	 *
	 * @since 2.0.0
	 *
	 * @return array List of table names and IDs.
	 */
	protected function _get_list_of_table_names() {
		$existing_tables = array();
		// Load all table IDs and names for a comparison with the file name.
		$table_ids = TablePress::$model_table->load_all( false );
		foreach ( $table_ids as $table_id ) {
			// Load table, without table data, options, and visibility settings.
			$table = TablePress::$model_table->load( $table_id, false, false );
			if ( ! is_wp_error( $table ) ) {
				$existing_tables[ $table['name'] ][] = $table['id']; // Attention: The table name is not unique!
			}
		}
		return $existing_tables;
	}

	/**
	 * Checks whether the requirements for the PHPSpreadsheet import class are fulfilled or if the legacy import class should be used.
	 *
	 * @since 2.0.0
	 *
	 * @return bool Whether the legacy import class should be used.
	 */
	protected function _should_use_legacy_import_class() {
		// Allow overriding in the import config (coming e.g. from the import form UI).
		if ( $this->import_config['legacy_import'] ) {
			return true;
		}

		/**
		 * Filters whether the Legacy Table Import class shall be used.
		 *
		 * @since 2.0.0
		 *
		 * @param bool Whether to use the legacy table import class. Default false.
		 */
		if ( apply_filters( 'tablepress_use_legacy_table_import_class', false ) ) {
			return true;
		}

		// Use the legacy import class, if the requirements for PHPSpreadsheet are not fulfilled.
		$phpspreadsheet_requirements_fulfilled = PHP_VERSION_ID >= 70200
			&& extension_loaded( 'mbstring' )
			&& class_exists( 'ZipArchive', false )
			&& class_exists( 'DOMDocument', false )
			&& function_exists( 'simplexml_load_string' )
			&& function_exists( 'libxml_disable_entity_loader' );
		if ( ! $phpspreadsheet_requirements_fulfilled ) {
			return true;
		}

		// Use the legacy import class, if the PHPSpreadsheet files do not exist (e.g. because `composer install` was not run).
		if ( ! file_exists( TABLEPRESS_ABSPATH . 'libraries/autoload.php' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Imports all found/extracted/configured files into TablePress.
	 *
	 * @since 2.0.0
	 *
	 * @param array $import_files Files that shall be imported.
	 * @return array Import tables and import errors.
	 */
	protected function _import_files( array $import_files ) {
		$tables = array();
		$errors = array();

		$use_legacy_import_class = $this->_should_use_legacy_import_class();

		// Load Import Base Class.
		TablePress::load_file( 'class-import-base.php', 'classes' );

		// Choose the Table Import library based on the PHP version and the filter hook value.
		if ( $use_legacy_import_class ) {
			$this->importer = TablePress::load_class( 'TablePress_Import_Legacy', 'class-import-legacy.php', 'classes' );
		} else {
			$this->importer = TablePress::load_class( 'TablePress_Import_PHPSpreadsheet', 'class-import-phpspreadsheet.php', 'classes' );
		}

		// If there is more than one valid import file, ignore the chosen existing table for replacing/appending.
		if ( in_array( $this->import_config['type'], array( 'replace', 'append' ), true ) && '' !== $this->import_config['existing_table'] ) {
			$valid_import_files = 0;
			foreach ( $import_files as $file ) {
				if ( ! isset( $file['error'] ) || ! is_wp_error( $file['error'] ) ) {
					++$valid_import_files;
					if ( $valid_import_files > 1 ) {
						$this->import_config['existing_table'] = '';
						break;
					}
				}
			}
		}

		// Loop through all import files and import them.
		foreach ( $import_files as $file ) {
			if ( isset( $file['error'] ) && is_wp_error( $file['error'] ) ) {
				$errors[] = $file;
				continue;
			}

			// Use import method depending on chosen import class.
			if ( $use_legacy_import_class ) {
				$table = $this->_load_table_from_file_legacy( $file );
			} else {
				$table = $this->_load_table_from_file_phpspreadsheet( $file );
			}

			$this->_maybe_unlink_file( $file );

			if ( is_wp_error( $table ) ) {
				$file['error'] = $table;
				$errors[] = $file;
				continue;
			}

			$table = $this->_import_table( $table, $file );
			if ( is_wp_error( $table ) ) {
				$file['error'] = $table;
				$errors[] = $file;
				continue;
			}

			$tables[] = $table;
		}

		return array(
			'tables' => $tables,
			'errors' => $errors,
		);
	}

	/**
	 * Loads a table from a file via the legacy import class.
	 *
	 * @since 2.0.0
	 *
	 * @param array $file File with the table data.
	 * @return array|WP_Error Loaded table on success (either with all properties or just 'data'), WP_Error on failure.
	 */
	protected function _load_table_from_file_legacy( array $file ) {
		// Guess the import format from the file extension.
		switch ( $file['extension'] ) {
			case 'xlsx': // Excel (OfficeOpenXML) Spreadsheet.
			case 'xlsm': // Excel (OfficeOpenXML) Macro Spreadsheet (macros will be discarded).
			case 'xltx': // Excel (OfficeOpenXML) Template.
			case 'xltm': // Excel (OfficeOpenXML) Macro Template (macros will be discarded).
				$format = 'xlsx';
				break;
			case 'xls': // Excel (BIFF) Spreadsheet.
			case 'xlt': // Excel (BIFF) Template.
				$format = 'xls';
				break;
			case 'htm':
			case 'html':
				$format = 'html';
				break;
			case 'csv':
			case 'tsv':
				$format = 'csv';
				break;
			case 'json':
				$format = 'json';
				break;
			default:
				// If no format was found, pass the extension (which will likely result in an error).
				$format = $file['extension'];
		}

		$data = file_get_contents( $file['location'] );
		if ( false === $data ) {
			return new WP_Error( 'table_import_legacy_data_read', '', $file['location'] );
		}
		if ( '' === $data ) {
			return new WP_Error( 'table_import_legacy_data_empty', '', $file['location'] );
		}

		// If no format could be determined from the file extension, try guessing from the file content.
		if ( '' === $format ) {
			$first_character = $data[0];
			if ( '<' === $first_character ) {
				$format = 'html';
			} elseif ( '{' === $first_character || '[' === $first_character ) {
				$format = 'json';
			}
		}

		// Fall back to CSV if no file format could be determined.
		if ( '' === $format ) {
			$format = 'csv';
		}

		if ( ! isset( $this->importer->import_formats[ $format ] ) ) {
			return new WP_Error( 'table_import_legacy_unknown_format', '', $file['name'] );
		}

		$table = $this->importer->import_table( $format, $data );

		if ( false === $table ) {
			return new WP_Error( 'table_import_legacy_importer_failed', '', array( 'file_name' => $file['name'], 'file_format' => $format ) );
		}

		return $table;
	}

	/**
	 * Loads a table from a file via the PHPSpreadsheet import class.
	 *
	 * @since 2.0.0
	 *
	 * @param array $file File with the table data.
	 * @return array|WP_Error Loaded table on success (either with all properties or just 'data'), WP_Error on failure.
	 */
	protected function _load_table_from_file_phpspreadsheet( array $file ) {
		$table = $this->importer->import_table( $file );

		if ( is_wp_error( $table ) ) {
			return $table;
		}

		return $table;
	}

	/**
	 * Imports a loaded table into TablePress.
	 *
	 * @since 2.0.0
	 *
	 * @param array $table         The table to be imported, either with properties or just the $table['data'] property set.
	 * @param array $file          File with the table data.
	 * @return array|WP_Error Imported table on success, WP_Error on failure.
	 */
	protected function _import_table( array $table, array $file ) {
		// If name and description are imported from a new table, use those.
		if ( ! isset( $table['name'] ) ) {
			$table['name'] = $file['name'];
		}
		if ( ! isset( $table['description'] ) ) {
			$table['description'] = $file['name'];
		}

		$import_type = $this->import_config['type'];
		$existing_table_id = $this->import_config['existing_table'];

		// If no existing table ID has been set (or if we are importing multiple tables), try to find a potential existing table from the table ID in the import data or by comparing the file name with the table name.
		if ( in_array( $import_type, array( 'replace', 'append' ), true ) && '' === $existing_table_id ) {
			if ( isset( $table['id'] ) ) {
				// If the table already contained a table ID (e.g. for the JSON format), use that.
				$existing_table_id = $table['id'];
			} elseif ( isset( $this->table_names_ids[ $file['name'] ] ) && 1 === count( $this->table_names_ids[ $file['name'] ] ) ) {
				// Use the replace/append ID of tables where the table name matches the file name, but only if there was exactly one file name match.
				$existing_table_id = $this->table_names_ids[ $file['name'] ][0];
			}
		}

		// If the table that is to be replaced or appended to does not exist, add the new table instead.
		if ( ! TablePress::$model_table->table_exists( $existing_table_id ) ) {
			$existing_table_id = '';
			$import_type = 'add';
		}

		$table = $this->_import_tablepress_table( $table, $import_type, $existing_table_id );

		return $table;
	}

	/**
	 * Imports a table by either replacing or appending to an existing table or by adding it as a new table.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $imported_table    The table to be imported, either with properties or just the `name`, `description`, and `data` property set.
	 * @param string $import_type       What to do with the imported data: "add", "replace", "append".
	 * @param string $existing_table_id Empty string if table shall be added as a new table, ID of the table to be replaced or appended to otherwise.
	 * @return array|WP_Error Table on success, WP_Error on error.
	 */
	protected function _import_tablepress_table( array $imported_table, $import_type, $existing_table_id ) {
		// Full JSON format table can contain a table ID, try to keep that, by later changing the imported table ID to this.
		$table_id_in_import = isset( $imported_table['id'] ) ? $imported_table['id'] : '';

		// To be able to replace or append to a table, the user must be able to edit the table, or it must be a Cron request (e.g. via the Automatic Periodic Table Import module).
		if ( in_array( $import_type, array( 'replace', 'append' ), true ) && ! ( current_user_can( 'tablepress_edit_table', $existing_table_id ) || wp_doing_cron() ) ) {
			return new WP_Error( 'table_import_replace_append_capability_check_failed', '', $existing_table_id );
		}

		switch ( $import_type ) {
			case 'add':
				$existing_table = TablePress::$model_table->get_table_template();
				// Import visibility information if it exists, usually only for the JSON format.
				if ( isset( $imported_table['visibility'] ) ) {
					$existing_table['visibility'] = $imported_table['visibility'];
				}
				break;
			case 'replace':
				// Load table, without table data, but with options and visibility settings.
				$existing_table = TablePress::$model_table->load( $existing_table_id, false, true );
				if ( is_wp_error( $existing_table ) ) {
					$error = new WP_Error( 'table_import_replace_table_load', '', $existing_table_id );
					$error->merge_from( $existing_table );
					return $error;
				}
				// Don't change name and description when a table is replaced.
				$imported_table['name'] = $existing_table['name'];
				$imported_table['description'] = $existing_table['description'];
				// Replace visibility information if it exists.
				if ( isset( $imported_table['visibility'] ) ) {
					$existing_table['visibility'] = $imported_table['visibility'];
				}
				break;
			case 'append':
				// Load table, with table data, options, and visibility settings.
				$existing_table = TablePress::$model_table->load( $existing_table_id, true, true );
				if ( is_wp_error( $existing_table ) ) {
					$error = new WP_Error( 'table_import_append_table_load', '', $existing_table_id );
					$error->merge_from( $existing_table );
					return $error;
				}
				if ( isset( $existing_table['is_corrupted'] ) && $existing_table['is_corrupted'] ) {
					return new WP_Error( 'table_import_append_table_load_corrupted', '', $existing_table_id );
				}
				// Don't change name and description when a table is appended to.
				$imported_table['name'] = $existing_table['name'];
				$imported_table['description'] = $existing_table['description'];
				// Actual appending:.
				$imported_table['data'] = array_merge( $existing_table['data'], $imported_table['data'] );
				$this->importer->pad_array_to_max_cols( $imported_table['data'] );
				// Append visibility information for rows.
				if ( isset( $imported_table['visibility']['rows'] ) ) {
					$existing_table['visibility']['rows'] = array_merge( $existing_table['visibility']['rows'], $imported_table['visibility']['rows'] );
				}
				// When appending, do not overwrite options, e.g. coming from a JSON file.
				unset( $imported_table['options'] );
				break;
			default:
				return new WP_Error( 'table_import_import_type_invalid', '', $import_type );
		}

		// Merge new or existing table with information from the imported table.
		$imported_table['id'] = $existing_table['id']; // Will be false for new table or the existing table ID.
		// Cut visibility array (if the imported table is smaller), and pad correctly if imported table is bigger than existing table (or new template).
		$num_rows = count( $imported_table['data'] );
		$num_columns = count( $imported_table['data'][0] );
		$imported_table['visibility'] = array(
			'rows'    => array_pad( array_slice( $existing_table['visibility']['rows'], 0, $num_rows ), $num_rows, 1 ),
			'columns' => array_pad( array_slice( $existing_table['visibility']['columns'], 0, $num_columns ), $num_columns, 1 ),
		);

		// Check if the new table data is valid and consistent.
		$table = TablePress::$model_table->prepare_table( $existing_table, $imported_table, false );
		if ( is_wp_error( $table ) ) {
			$error = new WP_Error( 'table_import_table_prepare', '', $imported_table['id'] );
			$error->merge_from( $table );
			return $error;
		}

		// DataTables Custom Commands can only be edit by trusted users.
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$table['options']['datatables_custom_commands'] = $existing_table['options']['datatables_custom_commands'];
		}

		// Replace existing table or add new table.
		if ( in_array( $import_type, array( 'replace', 'append' ), true ) ) {
			// Replace existing table with imported/appended table.
			$table_id = TablePress::$model_table->save( $table );
		} else {
			// Add the imported table (and get its first ID).
			$table_id = TablePress::$model_table->add( $table );
		}

		if ( is_wp_error( $table_id ) ) {
			$error = new WP_Error( 'table_import_table_save_or_add', '', $table['id'] );
			$error->merge_from( $table_id );
			return $error;
		}

		// Try to use ID from imported file (e.g. in full JSON format table).
		if ( '' !== $table_id_in_import && $table_id !== $table_id_in_import && current_user_can( 'tablepress_edit_table_id', $table_id ) ) {
			$id_changed = TablePress::$model_table->change_table_id( $table_id, $table_id_in_import );
			if ( ! is_wp_error( $id_changed ) ) {
				$table_id = $table_id_in_import;
			}
		}

		$table['id'] = $table_id;

		return $table;
	}

	/**
	 * Imports a table in legacy versions of the Table Auto Update Extension.
	 *
	 * This method is deprecated and is only left for backward compatibility reasons. Do not use this in new code!
	 *
	 * @since 1.0.0
	 * @deprecated 2.0.0 Use `run()` instead.
	 *
	 * @param string $format Import format.
	 * @param string $data   Data to import.
	 * @return array|false Table array on success, false on error.
	 */
	public function import_table( $format, $data ) {
		TablePress::load_file( 'class-import-base.php', 'classes' );
		$importer = TablePress::load_class( 'TablePress_Import_Legacy', 'class-import-legacy.php', 'classes' );
		return $importer->import_table( $format, $data );
	}

} // class TablePress_Import
