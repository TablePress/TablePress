<?php
/**
 * Options Model
 *
 * @package TablePress
 * @subpackage Models
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Options Model class
 * @package TablePress
 * @subpackage Models
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Options_Model extends TablePress_Model {

	/**
	 * Default Plugin Options
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $default_plugin_options = array(
		'plugin_options_db_version' => TablePress::db_version,
		'tablepress_version' => TablePress::version,
		'first_activation' => 0,
		'message_plugin_update' => true
	);

	/**
	 * Default User Options
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $default_user_options = array(
		'user_options_db_version' => TablePress::db_version,
		'admin_menu_parent_page' => 'tools.php',
		'plugin_language' => 'auto',
		'message_first_visit' => true
	);

	/**
	 * Instance of WP_Option class for Plugin Options
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	protected $plugin_options;

	/**
	 * Instance of WP_User_Option class for User Options
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	protected $user_options;

	/**
	 * Init Options Model by creating the object instances for the Plugin and User Options
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$params = array(
			'option_name' => 'tablepress_plugin_options',
			'default_value' => $this->default_plugin_options
		);
		$this->plugin_options = TablePress::load_class( 'TablePress_WP_Option', 'class-wp_option.php', 'classes', $params );

		$params = array(
			'option_name' => 'tablepress_user_options',
			'default_value' => $this->default_user_options
		);
		$this->user_options = TablePress::load_class( 'TablePress_WP_User_Option', 'class-wp_user_option.php', 'classes', $params );
	}

	/**
	 * Update a single option or an array of options with new values
	 *
	 * @since 1.0.0
	 *
	 * @param array|string $new_options Array of new options ( name => value ) or name of a single option
	 * @param mixed $single_value (optional) New value for a single option (only if $new_options is not an array)
	 */
	public function update( $new_options, $single_value = null ) {
		// allow saving of single options that are not in an array
		if ( ! is_array( $new_options ) )
			$new_options = array( $new_options => $single_value );

		$plugin_options = $this->plugin_options->get();
		$user_options = $this->user_options->get();
		foreach ( $new_options as $name => $value ) {
			if ( isset( $this->default_plugin_options[ $name ] ) ) {
				$plugin_options[ $name ] = $value;
			 } elseif ( isset( $this->default_user_options[ $name ] ) ) {
				$user_options[ $name ] = $value;
			} else {
				// no valid Plugin or User Option -> discard the name/value pair
			}
		}

		$this->plugin_options->update( $plugin_options );
		$this->user_options->update( $user_options );
	}

	/**
	 * Get the value of a single option, or an array with all options
	 *
	 * @since 1.0.0
	 *
	 * @param string $name (optional) Name of a single option to get, or false for all options
	 * @param mixed $default_value (optional) Default value, if the option $name does not exist
	 * @return mixed Value of the retrieved option $name, or $default_value if it does not exist, or array of all options
	 */
	public function get( $name = false, $default_value = null ) {
		if ( false === $name )
			return array_merge( $this->plugin_options->get(), $this->user_options->get() );

		// Single Option wanted
		if ( $this->plugin_options->is_set( $name ) ) {
			return $this->plugin_options->get( $name );
		} elseif ( $this->user_options->is_set( $name ) ) {
			return $this->user_options->get( $name );
		} else {
			// no valid Plugin or User Option
			return $default_value;
		}
	}

	/**
	 * Get all Plugin Options (only used in Debug)
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of all Plugin Options
	 */
	public function get_plugin_options() {
		return $this->plugin_options->get();
	}

	/**
	 * Get all User Options (only used in Debug)
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of all User Options
	 */
	public function get_user_options() {
		return $this->user_options->get();
	}

	/**
	 * Merge existing Plugin Options with default Plugin Options,
	 * remove (no longer) existing options, e.g. after a plugin update
	 *
	 * @since 1.0.0
	 */
	public function merge_plugin_options_defaults() {
		$plugin_options = $this->plugin_options->get();
		// remove old (i.e. no longer existing) Plugin Options:
		$plugin_options = array_intersect_key( $plugin_options, $this->default_plugin_options );
		// merge into new Plugin Options:
		$plugin_options = array_merge( $this->default_plugin_options, $plugin_options );

		$this->plugin_options->update( $plugin_options );
	}
	/**
	 * Merge existing User Options with default User Options,
	 * remove (no longer) existing options, e.g. after a plugin update
	 *
	 * @since 1.0.0
	 */
	public function merge_user_options_defaults() {
		$user_options = $this->user_options->get();
		// remove old (i.e. no longer existing) User Options:
		$user_options = array_intersect_key( $user_options, $this->default_user_options );
		// merge into new User Options:
		$user_options = array_merge( $this->default_user_options, $user_options );

		$this->user_options->update( $user_options );
	}

} // class TablePress_Options_Model