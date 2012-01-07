<?php
/**
 * Options Model
 *
 * @package TablePress
 * @subpackage Options Model
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Options Model class
 *
 * @since 1.0.0
 */
class TablePress_Options_Model extends TablePress_Model {

	/**
	 * @var string Name of the option for the Plugin Options in the "wp_options" database table
	 *
	 * @since 1.0.0
	 */
	protected $plugin_options_option_name = 'tablepress_plugin_options';

	/**
	 * @var string Name of the option for the User Options in the "wp_usermeta" database table
	 *
	 * @since 1.0.0
	 */
	protected $user_options_option_name = 'tablepress_user_options';

	/**
	 * @var array Default Plugin Options (on plugin installation)
	 *
	 * @since 1.0.0
	 */
	protected $default_plugin_options = array(
		'plugin_options_db_version' => TablePress::db_version,
		'tablepress_version' => TablePress::version,
		'first_activation' => 0,
		'message_plugin_update' => true
	);

	/**
	 * @var array Default User Options (on plugin installation)
	 *
	 * @since 1.0.0
	 */
	protected $default_user_options = array(
		'user_options_db_version' => TablePress::db_version,
		'admin_menu_parent_page' => 'tools.php',
		'plugin_language' => 'auto',
		'message_first_visit' => true
	);

	/**
	 * @var array Current set of Plugin Options
	 *
	 * @since 1.0.0
	 */
	protected $plugin_options = array();

	/**
	 * @var array Current set of User Options
	 *
	 * @since 1.0.0
	 */
	protected $user_options = array();

	/**
	 * Init Options Model
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		$this->_retrieve_plugin_options();
		$this->_retrieve_user_options();
	}

	/**
	 * Load Plugin Options from the database, decoded from JSON to an associative array
	 * If no options are stored in the database, return default options.
	 *
	 * @since 1.0.0
	 */
	protected function _retrieve_plugin_options() {
		$this->plugin_options = get_option( $this->plugin_options_option_name );
		if ( false === $this->plugin_options ) {
			// Adjust default Plugin Options that need to be set at run time
			$this->default_plugin_options['first_activation'] = time();
			$this->plugin_options = $this->default_plugin_options;
			$this->_store_plugin_options();
		} else {
			$this->plugin_options = json_decode( $this->plugin_options, true );
		}
	}

	/**
	 * Load User Options from the database, decoded from JSON to an associative array
	 * If no options are stored in the database, return default options.
	 *
	 * @since 1.0.0
	 */
	protected function _retrieve_user_options() {
		$this->user_options = get_user_option( $this->user_options_option_name );
		if ( false === $this->user_options ) {
			$this->user_options = $this->default_user_options;
			$this->_store_user_options();
		} else {
			$this->user_options = json_decode( $this->user_options, true );
		}
	}

	/**
	 * Save current set of Plugin Options to the database, encoded as JSON
	 * ($plugin_options_option_name field in "wp_options")
	 *
	 * @since 1.0.0
	 */
	protected function _store_plugin_options() {
		update_option( $this->plugin_options_option_name, json_encode( $this->plugin_options ) );
	}
	
	/**
	 * Save current set of User Options to the database, encoded as JSON
	 * ($user_options_option_name field in "wp_usermeta" for current user)
	 *
	 * @since 1.0.0
	 */
	protected function _store_user_options() {
		if ( is_user_logged_in() )
			update_user_option( get_current_user_id(), $this->user_options_option_name, json_encode( $this->user_options ), false );
	}

	/**
	 * Update a single option or an array of options with new values
	 *
	 * @since 1.0.0
	 *
	 * @param array|string $new_options Array of new options ( name => value ) or name of a single option
	 * @param mixed $value New value for a single option (only if $new_options is a string)
	 */
	public function update( $new_options, $value = null ) {
		// allow saving of single options that are not in an array
		if ( ! is_array( $new_options ) && ! is_null( $value ) )
			$new_options = array( $new_options => $value );

		foreach ( $new_options as $key => $value ) {
			if ( isset( $this->default_plugin_options[ $key ] ) ) {
				$this->plugin_options[ $key ] = $value;
			 } elseif ( isset( $this->default_user_options[ $key ] ) ) {
				$this->user_options[ $key ] = $value;
			} else {
				// no valid Plugin or User Option -> discard
			}
		}

		$this->_store_plugin_options();
		$this->_store_user_options();
	}

	/**
	 * Get the value of a single option, or an array with all options
	 *
	 * @since 1.0.0
	 *
	 * @param string|bool $single_option Name of a single option to get, or false for all options
	 * @param mixed $default Default value to return, if a $single_option does not exist
	 * @return mixed|array Value of the retrieved $single_option or array of all options
	 */
	public function get( $single_option = false, $default = null ) {
		if ( ! $single_option )
			return array_merge( $this->plugin_options, $this->user_options );

		// Single Option wanted
		if ( isset( $this->plugin_options[ $single_option ] ) ) {
			return $this->plugin_options[ $single_option ];
		} elseif ( isset( $this->user_options[ $single_option ] ) ) {
			return $this->user_options[ $single_option ];
		} else {
			// no valid Plugin or User Option
			return $default;
		}
	}

	/**
	 * Get all Plugin Options
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of all Plugin Options
	 */
	public function get_plugin_options() {
		return $this->plugin_options;
	}

	/**
	 * Get all User Options
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of all User Options
	 */
	public function get_user_options() {
		return $this->user_options;
	}

	/**
	 * Merge existing Plugin Options with default Plugin Options,
	 * remove (no longer) existing options, e.g. after a plugin update
	 *
	 * @since 1.0.0
	 */
	public function merge_plugin_options_defaults() {
		// remove old Plugin Options
		$this->plugin_options = array_intersect_key( $this->plugin_options, $this->default_plugin_options );
		// merge into new Plugin Options
		$this->plugin_options = array_merge( $this->default_plugin_options, $this->plugin_options );

		$this->_store_plugin_options();
	}
	/**
	 * Merge existing User Options with default User Options,
	 * remove (no longer) existing options, e.g. after a plugin update
	 *
	 * @since 1.0.0
	 */
	public function merge_user_options_defaults() {
		// remove old User Options
		$this->user_options = array_intersect_key( $this->user_options, $this->default_user_options );
		// merge into new User Options
		$this->user_options = array_merge( $this->default_user_options, $this->user_options );

		$this->_store_user_options();
	}

} // class TablePress_Options_Model