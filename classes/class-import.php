<?php
/**
 * TablePress Table Import Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @author Tobias Bäthge
 * @since 1.0.0
 */

use TablePress\Import\File;

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

TablePress::load_file( 'class-import-file.php', 'classes' );

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
	 * Instance of the TablePress Legacy or PHPSpreadsheet Importer.
	 *
	 * @since 1.0.0
	 * @var TablePress_Import_Legacy|TablePress_Import_PHPSpreadsheet
	 */
	protected object $importer;

	/**
	 * Import configuration (mainly the data from the Import form).
	 *
	 * @since 2.0.0
	 * @var array<string, mixed>
	 */
	protected array $import_config = array();

	/**
	 * Whether ZIP archive support is available (which it always is, as PclZip is used as a fallback).
	 *
	 * @since 1.0.0
	 * @deprecated 2.3.0 ZIP support is now always available, either through `ZipArchive` or through `PclZip`.
	 */
	public bool $zip_support_available = true;

	/**
	 * List of table names/IDs for use when replacing/appending existing tables (except for the JSON format).
	 *
	 * @since 2.0.0
	 * @var array<string, string[]>
	 */
	protected array $table_names_ids = array();

	/**
	 * Runs the import process for a given import configuration.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $import_config Import configuration.
	 * @return array{tables: array<int, array<string, mixed>>, errors: File[]}|WP_Error List of imported tables on success, WP_Error on failure.
	 */
	public function run( array $import_config ) /* : array|WP_Error */ {
		// Unziping can use a lot of memory and execution time, but not this much hopefully.
		wp_raise_memory_limit( 'admin' );
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		$this->import_config = $import_config;

		$import_files = $this->get_files_to_import();
		if ( is_wp_error( $import_files ) ) {
			return $import_files;
		}

		$import_files = $this->convert_zip_files( $import_files );

		if ( in_array( $this->import_config['type'], array( 'replace', 'append' ), true ) ) {
			$this->table_names_ids = $this->get_list_of_table_names();
		}

		return $this->import_files( $import_files );
	}

	/**
	 * Extracts the files that shall be imported from the import configuration.
	 *
	 * @since 2.0.0
	 *
	 * @return File[]|WP_Error Array of files that shall be imported or WP_Error on failure.
	 */
	protected function get_files_to_import() /* : array|WP_Error */ {
		$import_files = array();

		switch ( $this->import_config['source'] ) {
			case 'file-upload':
				foreach ( $this->import_config['file-upload']['error'] as $key => $error ) {
					$file = new File( array(
						'location' => $this->import_config['file-upload']['tmp_name'][ $key ],
						'name'     => $this->import_config['file-upload']['name'][ $key ],
					) );
					if ( UPLOAD_ERR_OK !== $error ) {
						@unlink( $this->import_config['file-upload']['tmp_name'][ $key ] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
						$file->error = new WP_Error( 'table_import_file-upload_error', '', $error );
					}
					$import_files[] = $file;
				}
				break;
			case 'url':
				$host = wp_parse_url( $this->import_config['url'], PHP_URL_HOST );

				if ( empty( $host ) ) {
					return new WP_Error( 'table_import_url_host_invalid', '', $this->import_config['url'] );
				}

				// Check the IP address of the host against a blocklist of hosts which should not be accessible, e.g. for security considerations.
				$ip = gethostbyname( $host ); // If no IP address can be found, this will return the host name, which will then be checked against the blocklist.
				$blocked_ips = array(
					'169.254.169.254', // Meta-data API for various cloud providers.
					'169.254.170.2', // AWS task metadata endpoint.
					'192.0.0.192', // Oracle Cloud endpoint.
					'100.100.100.200', // Alibaba Cloud endpoint.
				);
				if ( in_array( $ip, $blocked_ips, true ) ) {
					return new WP_Error( 'table_import_url_host_blocked', '', array( 'url' => $this->import_config['url'], 'ip' => $ip ) );
				}

				// Automatically adjust URLs of common services to point to a direct download URL.
				$this->import_config['url'] = $this->fix_common_url_mistakes( $this->import_config['url'] );

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

				$import_files[] = new File( array(
					'location' => $location,
					'name'     => $this->import_config['url'],
				) );
				break;
			case 'server':
				if ( ABSPATH === $this->import_config['server'] ) {
					return new WP_Error( 'table_import_server_invalid', '', $this->import_config['server'] );
				}

				if ( ! is_readable( $this->import_config['server'] ) ) {
					return new WP_Error( 'table_import_server_not_readable', '', $this->import_config['server'] );
				}

				$import_files[] = new File( array(
					'location'  => $this->import_config['server'],
					'name'      => pathinfo( $this->import_config['server'], PATHINFO_BASENAME ),
					'keep_file' => true, // Files on the server must not be deleted.
				) );
				break;
			case 'form-field':
				$location = wp_tempnam();
				$num_written_bytes = file_put_contents( $location, $this->import_config['form-field'] );
				if ( false === $num_written_bytes || 0 === $num_written_bytes ) {
					@unlink( $location ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					return new WP_Error( 'table_import_form-field_temp_file_not_written' );
				}

				$import_files[] = new File( array(
					'location' => $location,
					'name'     => __( 'Imported from Manual Input', 'tablepress' ),
				) );
				break;
			default:
				return new WP_Error( 'table_import_invalid_source', '', $this->import_config['source'] );
		}

		return $import_files;
	}

	/**
	 * Fixes common mistakes in URLs from popular services to point to a direct download URL.
	 *
	 * Currently supports Google Sheets, Microsoft OneDrive, and Dropbox.
	 * See https://tablepress.org/tutorials/ for more specific instructions on how to get the correct URL.
	 *
	 * @since 3.2.4
	 *
	 * @param string $url URL that shall be fixed.
	 * @return string Fixed URL.
	 */
	protected function fix_common_url_mistakes( string $url ): string {
		/**
		 * Filters whether common URL mistakes shall be fixed automatically.
		 *
		 * @since 3.2.4
		 *
		 * @param bool $fix_common_url_mistakes Whether to fix common URL mistakes. Default true.
		 */
		if ( ! apply_filters( 'tablepress_import_fix_common_url_mistakes', true ) ) {
			return $url;
		}

		if ( str_starts_with( $url, 'https://docs.google.com/spreadsheets/' ) && str_ends_with( $url, '/edit?usp=sharing' ) ) {
			// Google Sheets "Sharing URL" to direct download URL.
			$url = str_replace( '/edit?usp=sharing', '/export?format=csv', $url );
		} elseif ( str_starts_with( $url, 'https://1drv.ms/' ) && ! str_ends_with( $url, '&download=1' ) ) {
			// OneDrive shared link to direct download link.
			$url .= '&download=1';
		} elseif ( str_starts_with( $url, 'https://www.dropbox.com/' ) && str_ends_with( $url, '&dl=0' ) ) {
			// Dropbox shared link to direct download link.
			$url = str_replace( '&dl=0', '&dl=1', $url );
		}

		return $url;
	}

	/**
	 * Replaces ZIP archives in the import files with a list of their contents.
	 *
	 * ZIP files are removed from the list and their contents are added to the end of the list.
	 *
	 * @since 2.0.0
	 *
	 * @param File[] $import_files Files that shall be imported, including ZIP archives.
	 * @return File[] Files that shall be imported, with all ZIP archives recursively replaced by their contents.
	 */
	protected function convert_zip_files( array $import_files ): array {
		foreach ( $import_files as $key => &$file ) {
			// $file has to be used by reference, so that $key points to the correct element, due to array modification with `unset()` and `array_push()`.

			// Skip files that already have an error.
			if ( is_wp_error( $file->error ) ) {
				continue;
			}

			$file->extension = strtolower( pathinfo( $file->name, PATHINFO_EXTENSION ) );

			if ( function_exists( 'mime_content_type' ) ) {
				$mime_type = mime_content_type( $file->location );
				if ( false !== $mime_type ) {
					$file->mime_type = $mime_type;
				}
			}

			// Detect ZIP files from their file extension or MIME type.
			if ( 'zip' === $file->extension || 'application/zip' === $file->mime_type ) {
				$extracted_files = $this->extract_zip_file( $file );
				if ( is_wp_error( $extracted_files ) ) {
					$file->error = $extracted_files;
					$this->maybe_unlink_file( $file );
					continue;
				}

				if ( empty( $extracted_files ) ) {
					$file->error = new WP_Error( 'table_import_zip_file_empty', '', $file->name );
					$this->maybe_unlink_file( $file );
					continue;
				}

				/*
				 * Remove the ZIP file from the list and instead append its contents.
				 * Appending ensures recursiveness, as the appended files will be checked again.
				 */
				unset( $import_files[ $key ] );
				array_push( $import_files, ...$extracted_files );

				$this->maybe_unlink_file( $file );
			}
		}
		unset( $file ); // Unset use-by-reference parameter of foreach loop.

		$import_files = array_merge( $import_files ); // Re-index.

		return $import_files;
	}

	/**
	 * Extracts the files of a ZIP file and returns a list of files and their location.
	 *
	 * Depending on availability, either the PHP's ZipArchive class or WordPress' PclZip class is used.
	 *
	 * @since 2.0.0
	 *
	 * @param File $zip_file File data of a ZIP file (likely in a temporary folder).
	 * @return File[]|WP_Error List of files to import that were extracted from the ZIP file or WP_Error on failure.
	 */
	protected function extract_zip_file( File $zip_file ) /* : array|WP_Error */ {
		if ( class_exists( 'ZipArchive', false ) ) {
			$ziparchive_result = $this->extract_zip_file_ziparchive( $zip_file );
			if ( is_array( $ziparchive_result ) ) {
				return $ziparchive_result;
			}
		} else {
			$ziparchive_result = new WP_Error( 'table_import_error_zip_open', '', array( 'ziparchive_error' => 'Class ZipArchive not available' ) );
		}

		// Fall through to PclZip if ZipArchive is not available or encountered an error opening the file.
		$pclzip_result = $this->extract_zip_file_pclzip( $zip_file );
		if ( is_wp_error( $pclzip_result ) ) {
			// Append the WP_Error from ZipArchive, to have all error information available.
			$pclzip_result->merge_from( $ziparchive_result );
		}

		return $pclzip_result;
	}

	/**
	 * Extracts the files of a ZIP file using the PHP ZipArchive class.
	 *
	 * The ZIP file is extracted to a temporary folder and a list of files and their location is returned.
	 *
	 * @since 2.3.0
	 *
	 * @param File $zip_file File data of a ZIP file (likely in a temporary folder).
	 * @return File[]|WP_Error List of files to import that were extracted from the ZIP file or WP_Error on failure.
	 */
	protected function extract_zip_file_ziparchive( File $zip_file ) /* : array|WP_Error */ {
		$archive = new ZipArchive();
		$archive_opened = $archive->open( $zip_file->location, ZipArchive::CHECKCONS );

		// If the ZIP file can't be opened with ZipArchive::CHECKCONS, try again without.
		if ( true !== $archive_opened ) {
			$archive_opened = $archive->open( $zip_file->location );
		}

		// If the ZIP file can't even be opened without ZipArchive::CHECKCONS, bail.
		if ( true !== $archive_opened ) {
			return new WP_Error( 'table_import_error_zip_open', '', array( 'ziparchive_error' => $archive_opened ) );
		}

		$files = array();

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		for ( $file_idx = 0; $file_idx < $archive->numFiles; $file_idx++ ) {
			$file_name = $archive->getNameIndex( $file_idx );

			if ( false === $file_name ) {
				$files[] = new File( array(
					'error' => new WP_Error( 'table_import_error_zip_stat', '', array( 'ziparchive_file_index' => $file_idx ) ),
				) );
				continue;
			}

			// Skip directories.
			if ( str_ends_with( $file_name, '/' ) ) {
				continue;
			}

			// Skip the __MACOSX directory that macOS adds to archives.
			if ( str_starts_with( $file_name, '__MACOSX/' ) ) {
				continue;
			}

			// Don't extract invalid files.
			if ( 0 !== validate_file( $file_name ) ) {
				continue;
			}

			$file_data = $archive->getFromIndex( $file_idx );
			if ( false === $file_data ) {
				$files[] = new File( array(
					'name'  => $file_name,
					'error' => new WP_Error( 'table_import_error_zip_get_data', '', array( 'ziparchive_file_index' => $file_idx, 'ziparchive_file_name' => $file_name ) ),
				) );
				continue;
			}

			$location = wp_tempnam();
			$num_written_bytes = file_put_contents( $location, $file_data );
			if ( false === $num_written_bytes || 0 === $num_written_bytes ) {
				@unlink( $location ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$files[] = new File( array(
					'name'  => $file_name,
					'error' => new WP_Error( 'table_import_error_zip_write_temp_data', '', array( 'ziparchive_file_index' => $file_idx, 'ziparchive_file_name' => $file_name ) ),
				) );
				continue;
			}

			$files[] = new File( array(
				'location' => $location,
				'name'     => $file_name,
			) );
		}

		$archive->close();

		return $files;
	}

	/**
	 * Extracts the files of a ZIP file using WordPress' PclZip class.
	 *
	 * The ZIP file is extracted to a temporary folder and a list of files and their location is returned.
	 *
	 * @since 2.3.0
	 *
	 * @param File $zip_file File data of a ZIP file (likely in a temporary folder).
	 * @return File[]|WP_Error List of files to import that were extracted from the ZIP file or WP_Error on failure.
	 */
	protected function extract_zip_file_pclzip( File $zip_file ) /* : array|WP_Error */ {
		mbstring_binary_safe_encoding();

		require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';

		$archive = new PclZip( $zip_file->location );
		$archive_files = $archive->extract( PCLZIP_OPT_EXTRACT_AS_STRING ); // @phpstan-ignore arguments.count (PclZip::extract() uses `func_get_args()` to handle optional arguments.)

		reset_mbstring_encoding();

		// If the ZIP file can't be opened, bail.
		if ( ! is_array( $archive_files ) ) {
			return new WP_Error( 'table_import_error_zip_open', '', array( 'pclzip_error' => $archive->errorInfo( true ) ) );
		}

		$files = array();

		foreach ( $archive_files as $file ) {
			// Skip directories.
			if ( $file['folder'] ) {
				continue;
			}

			// Skip the __MACOSX directory that macOS adds to archives.
			if ( str_starts_with( $file['filename'], '__MACOSX/' ) ) {
				continue;
			}

			// Don't extract invalid files.
			if ( 0 !== validate_file( $file['filename'] ) ) {
				continue;
			}

			$location = wp_tempnam();
			$num_written_bytes = file_put_contents( $location, $file['content'] );
			if ( false === $num_written_bytes || 0 === $num_written_bytes ) {
				@unlink( $location ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				$files[] = new File( array(
					'name'  => $file['filename'],
					'error' => new WP_Error( 'table_import_error_zip_write_temp_data', '', array( 'ziparchive_file_index' => $file['index'], 'ziparchive_file_name' => $file['filename'] ) ),
				) );
				continue;
			}

			$files[] = new File( array(
				'location' => $location,
				'name'     => $file['filename'],
			) );
		}

		return $files;
	}

	/**
	 * Deletes a file unless the `keep_file` property is set to `true`.
	 *
	 * @since 2.0.0
	 *
	 * @param File $file File that should maybe be deleted.
	 */
	protected function maybe_unlink_file( File $file ): void {
		if ( ! $file->keep_file && file_exists( $file->location ) ) {
			@unlink( $file->location ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}

	/**
	 * Prepares a list of table names/IDs for use when replacing/appending existing tables (except for the JSON format).
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, string[]> List of table names and IDs.
	 */
	protected function get_list_of_table_names(): array {
		$existing_tables = array();
		// Load all table IDs and names for a comparison with the file name.
		$table_ids = TablePress::$model_table->load_all( false );
		foreach ( $table_ids as $table_id ) {
			// Load table, without table data, options, and visibility settings.
			$table = TablePress::$model_table->load( $table_id, false, false );
			if ( ! is_wp_error( $table ) ) {
				$existing_tables[ (string) $table['name'] ][] = $table_id; // Attention: The table name is not unique!
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
	protected function should_use_legacy_import_class(): bool {
		// Allow overriding in the import config (coming e.g. from the import form UI).
		if ( $this->import_config['legacy_import'] ) {
			return true;
		}

		/**
		 * Filters whether the Legacy Table Import class shall be used.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $use_legacy_class Whether to use the legacy table import class. Default false.
		 */
		if ( apply_filters( 'tablepress_use_legacy_table_import_class', false ) ) {
			return true;
		}

		// Use the legacy import class, if the requirements for PHPSpreadsheet are not fulfilled.
		$phpspreadsheet_requirements_fulfilled = extension_loaded( 'mbstring' )
			&& class_exists( 'ZipArchive', false )
			&& class_exists( 'DOMDocument', false )
			&& function_exists( 'simplexml_load_string' )
			&& ( function_exists( 'libxml_disable_entity_loader' ) || PHP_VERSION_ID >= 80000 ); // This function is only needed for older versions of PHP.
		if ( ! $phpspreadsheet_requirements_fulfilled ) {
			return true;
		}

		return false;
	}

	/**
	 * Imports all found/extracted/configured files into TablePress.
	 *
	 * @since 2.0.0
	 *
	 * @param File[] $import_files Files that shall be imported.
	 * @return array{tables: array<int, array<string, mixed>>, errors: File[]} Imported tables and files that caused errors.
	 */
	protected function import_files( array $import_files ): array {
		$tables = array();
		$errors = array();

		$use_legacy_import_class = $this->should_use_legacy_import_class();

		// Load Import Base Class.
		TablePress::load_file( 'class-import-base.php', 'classes' );

		// Choose the Table Import library based on the PHP version and the filter hook value.
		if ( $use_legacy_import_class ) {
			// @phpstan-ignore assign.propertyType (The `load_class()` method returns `object` and not a specific type.)
			$this->importer = TablePress::load_class( 'TablePress_Import_Legacy', 'class-import-legacy.php', 'classes' );
		} else {
			// @phpstan-ignore assign.propertyType (The `load_class()` method returns `object` and not a specific type.)
			$this->importer = TablePress::load_class( 'TablePress_Import_PHPSpreadsheet', 'class-import-phpspreadsheet.php', 'classes' );
		}

		// If there is more than one valid import file, ignore the chosen existing table for replacing/appending.
		if ( in_array( $this->import_config['type'], array( 'replace', 'append' ), true ) && '' !== $this->import_config['existing_table'] ) {
			$valid_import_files = 0;
			foreach ( $import_files as $file ) {
				if ( ! is_wp_error( $file->error ) ) {
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
			if ( is_wp_error( $file->error ) ) {
				$errors[] = $file;
				continue;
			}

			// Use import method depending on chosen import class.
			if ( $use_legacy_import_class ) {
				$table = $this->load_table_from_file_legacy( $file );
			} else {
				$table = $this->load_table_from_file_phpspreadsheet( $file );
			}

			$this->maybe_unlink_file( $file );

			if ( is_wp_error( $table ) ) {
				$file->error = $table;
				$errors[] = $file;
				continue;
			}

			$table = $this->save_imported_table( $table, $file );
			if ( is_wp_error( $table ) ) {
				$file->error = $table;
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
	 * @param File $file File with the table data.
	 * @return array<string, mixed>|WP_Error Loaded table on success (either with all properties or just 'data'), WP_Error on failure.
	 */
	protected function load_table_from_file_legacy( File $file ) /* : array|WP_Error */ {
		// Guess the import format from the file extension.
		switch ( $file->extension ) {
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
				// If no format was found, try finding the format from the first character below.
				$format = '';
		}

		$data = file_get_contents( $file->location );
		if ( false === $data ) {
			return new WP_Error( 'table_import_legacy_data_read', '', $file->location );
		}
		if ( '' === $data ) {
			return new WP_Error( 'table_import_legacy_data_empty', '', $file->location );
		}

		// If no format could be determined from the file extension, try guessing from the file content.
		if ( '' === $format ) {
			$data = trim( $data );
			$first_character = $data[0];
			$last_character = $data[-1];

			if ( '<' === $first_character && '>' === $last_character ) {
				$format = 'html';
			} elseif ( ( '[' === $first_character && ']' === $last_character ) || ( '{' === $first_character && '}' === $last_character ) ) {
				$json_table = json_decode( $data, true );
				if ( ! is_null( $json_table ) ) {
					$format = 'json';
				}
			}
		}

		// Fall back to CSV if no file format could be determined.
		if ( '' === $format ) {
			$format = 'csv';
		}

		if ( ! in_array( $format, $this->importer->import_formats, true ) ) { // @phpstan-ignore property.notFound (`$this->importer` is an instance of `TablePress_Import_Legacy` which has the property `import_formats`.)
			return new WP_Error( 'table_import_legacy_unknown_format', '', $file->name );
		}

		$table = $this->importer->import_table( $format, $data );

		if ( false === $table ) {
			return new WP_Error( 'table_import_legacy_importer_failed', '', array( 'file_name' => $file->name, 'file_format' => $format ) );
		}

		return $table;
	}

	/**
	 * Loads a table from a file via the PHPSpreadsheet import class.
	 *
	 * @since 2.0.0
	 *
	 * @param File $file File with the table data.
	 * @return array<string, mixed>|WP_Error Loaded table on success (either with all properties or just 'data'), WP_Error on failure.
	 */
	protected function load_table_from_file_phpspreadsheet( File $file ) /* : array|WP_Error */ {
		// Convert File object to array, as those are not yet used outside of this class.
		return $this->importer->import_table( $file ); // @phpstan-ignore return.type (This is an instance of TablePress_Import_PHPSpreadsheet which does not return false.)
	}

	/**
	 * Imports a loaded table into TablePress.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $table The table to be imported, either with properties or just the $table['data'] property set.
	 * @param File                 $file  File with the table data.
	 * @return array<string, mixed>|WP_Error Imported table on success, WP_Error on failure.
	 */
	protected function save_imported_table( array $table, File $file ) /* : array|WP_Error */ {
		// If name and description are imported from a new table, use those.
		if ( ! isset( $table['name'] ) ) {
			$table['name'] = $file->name;
		}
		if ( ! isset( $table['description'] ) ) {
			$table['description'] = $file->name;
		}

		$import_type = $this->import_config['type'];
		$existing_table_id = $this->import_config['existing_table'];

		// If no existing table ID has been set (or if we are importing multiple tables), try to find a potential existing table from the table ID in the import data or by comparing the file name with the table name.
		if ( in_array( $import_type, array( 'replace', 'append' ), true ) && '' === $existing_table_id ) {
			if ( isset( $table['id'] ) ) {
				// If the table already contained a table ID (e.g. for the JSON format), use that.
				$existing_table_id = $table['id'];
			} elseif ( isset( $this->table_names_ids[ $file->name ] ) && 1 === count( $this->table_names_ids[ $file->name ] ) ) {
				// Use the replace/append ID of tables where the table name matches the file name, but only if there was exactly one file name match.
				$existing_table_id = $this->table_names_ids[ $file->name ][0];
			}
		}

		// If the table that is to be replaced or appended to does not exist, add the new table instead.
		if ( ! TablePress::$model_table->table_exists( $existing_table_id ) ) {
			$existing_table_id = '';
			$import_type = 'add';
		}

		$table = $this->import_tablepress_table( $table, $import_type, $existing_table_id );

		return $table;
	}

	/**
	 * Imports a table by either replacing or appending to an existing table or by adding it as a new table.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $imported_table    The table to be imported, either with properties or just the `name`, `description`, and `data` property set.
	 * @param string               $import_type       What to do with the imported data: "add", "replace", "append".
	 * @param string               $existing_table_id Empty string if table shall be added as a new table, ID of the table to be replaced or appended to otherwise.
	 * @return array<string, mixed>|WP_Error Table on success, WP_Error on error.
	 */
	protected function import_tablepress_table( array $imported_table, string $import_type, string $existing_table_id ) /* : array|WP_Error */ {
		// Full JSON format table can contain a table ID, try to keep that, by later changing the imported table ID to this.
		$table_id_in_import = $imported_table['id'] ?? '';

		// To be able to replace or append to a table, the user must be able to edit the table, or it must be a request via the Automatic Periodic Table Import module.
		if ( in_array( $import_type, array( 'replace', 'append' ), true )
			&& ! ( current_user_can( 'tablepress_edit_table', $existing_table_id ) || doing_action( 'tablepress_automatic_periodic_table_import_action' ) ) ) {
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
	 * @return array<string, mixed>|WP_Error|false Table array on success, WP_Error or false on error.
	 */
	public function import_table( string $format, string $data ) /* : array|false */ {
		TablePress::load_file( 'class-import-base.php', 'classes' );
		$importer = TablePress::load_class( 'TablePress_Import_Legacy', 'class-import-legacy.php', 'classes' );
		return $importer->import_table( $format, $data );
	}

} // class TablePress_Import
