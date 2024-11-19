<?php
/**
 * TablePress WP Option Wrapper class for WordPress Options
 *
 * Wraps the WordPress Options API, so that (especially) arrays are stored as JSON, instead of being serialized by PHP.
 *
 * @package TablePress
 * @subpackage Classes
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress WP Option Wrapper class
 *
 * @package TablePress
 * @subpackage Classes
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_WP_Option {

	/**
	 * Name/Key of the Option (in its location in the database).
	 *
	 * @since 1.0.0
	 */
	protected string $option_name;

	/**
	 * Current value of the option.
	 *
	 * @since 1.0.0
	 * @var mixed[]
	 */
	protected array $option_value;

	/**
	 * Initialize with Option Name.
	 *
	 * @since 1.0.0
	 *
	 * @param array{option_name: string, default_value: mixed[]} $params {
	 *     An array of Option parameters.
	 *
	 *     @type string $option_name   Name of the Option.
	 *     @type array  $default_value Default values for the Option, as an array.
	 * }
	 */
	public function __construct( array $params ) {
		$this->option_name = $params['option_name'];

		$option_value = $this->_get_option( $this->option_name, null );
		if ( ! is_null( $option_value ) ) {
			$this->option_value = (array) json_decode( $option_value, true );
		} else {
			$this->option_value = $params['default_value'];
		}
	}

	/**
	 * Check if Option is set.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Name of the option to check.
	 * @return bool Whether the option is set.
	 */
	public function is_set( string $name ): bool {
		return isset( $this->option_value[ $name ] );
	}

	/**
	 * Get a single Option, or get all Options.
	 *
	 * @since 1.0.0
	 *
	 * @param string|false $name          Optional. Name of a single option to get, or false for all options.
	 * @param mixed        $default_value Optional. Default value to return, if a single option $name does not exist.
	 * @return mixed Value of the retrieved option $name or $default_value if it does not exist, or all options.
	 */
	public function get( /* string|false */ $name = false, /* string|int|float|bool|array|null */ $default_value = null ) /* : string|int|float|bool|array|null */ {
		if ( false === $name ) {
			return $this->option_value;
		}

		// Single Option wanted.
		if ( isset( $this->option_value[ $name ] ) ) {
			return $this->option_value[ $name ];
		} else {
			return $default_value;
		}
	}

	/**
	 * Update Option.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $new_options New options (name => value).
	 * @return bool True on success, false on failure.
	 */
	public function update( array $new_options ): bool {
		$this->option_value = $new_options;
		return $this->_update_option( $this->option_name, wp_json_encode( $this->option_value, TABLEPRESS_JSON_OPTIONS ) ); // @phpstan-ignore argument.type
	}

	/**
	 * Delete Option.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete(): bool {
		return $this->_delete_option( $this->option_name );
	}

	/*
	 * Internal functions mapping - This needs to be re-defined by child classes.
	 */

	/**
	 * Get the value of a WP Option with the WP Options API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name   Name of the WP Option.
	 * @param mixed  $default_value Default value of the WP Option.
	 * @return mixed Current value of the WP Option, or $default_value if it does not exist.
	 */
	protected function _get_option( string $option_name, /* string|int|float|bool|array|null */ $default_value ) /* : string|int|float|bool|array|null */ {
		return get_option( $option_name, $default_value );
	}

	/**
	 * Update the value of a WP Option with the WP Options API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name Name of the WP Option.
	 * @param string $new_value   New value of the WP Option.
	 * @return bool True on success, false on failure.
	 */
	protected function _update_option( string $option_name, string $new_value ): bool {
		return update_option( $option_name, $new_value );
	}

	/**
	 * Delete a WP Option with the WP Options API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_name Name of the WP Option.
	 * @return bool True on success, false on failure.
	 */
	protected function _delete_option( string $option_name ): bool {
		return delete_option( $option_name );
	}

} // class TablePress_WP_Option
