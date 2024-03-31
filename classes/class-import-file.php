<?php
/**
 * TablePress Table Import File Class
 *
 * @package TablePress
 * @subpackage Import
 * @author Tobias Bäthge
 * @since 2.3.0
 */

namespace TablePress\Import;

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Table Import File Class
 *
 * @package TablePress
 * @subpackage Import
 * @author Tobias Bäthge
 * @since 2.3.0
 */
class File {

	/**
	 * Local file path location of the file to import.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	public $location = '';

	/**
	 * File extension of the file to import.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	public $extension = '';

	/**
	 * MIME type of the file to import.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	public $mime_type = '';

	/**
	 * Name of the file to import, used as the table name.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	public $name = '';

	/**
	 * Error code related to the import of the file.
	 *
	 * @since 2.3.0
	 * @var ?\WP_Error
	 */
	public $error = null;

	/**
	 * Whether the file should be kept or deleted after import.
	 *
	 * @since 2.3.0
	 * @var bool
	 */
	public $keep_file = false;

	/**
	 * Creates a new File object, from an array of file properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string|bool|\WP_Error> $properties Array of file properties.
	 */
	public function __construct( array $properties ) {
		foreach ( $properties as $property => $value ) {
			if ( property_exists( $this, $property ) ) {
				$this->$property = $value;
			}
		}
	}

} // class File
