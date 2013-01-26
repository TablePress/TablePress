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
		'plugin_options_db_version' => 0,
		'table_scheme_db_version' => 0,
		'prev_tablepress_version' => '0',
		'tablepress_version' => TablePress::version,
		'first_activation' => 0,
		'message_plugin_update' => false,
		'message_plugin_update_content' => '',
		'message_donation_nag' => true,
		'use_custom_css' => true,
		'use_custom_css_file' => true,
		'custom_css' => '',
		'custom_css_minified' => '',
		'custom_css_version' => 0
	);

	/**
	 * Default User Options
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $default_user_options = array(
		'user_options_db_version' => TablePress::db_version, // to prevent saving on first load
		'admin_menu_parent_page' => 'middle',
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

		// Filter to map Meta capabilities to Primitive Capabilities
		add_filter( 'map_meta_cap', array( $this, 'map_tablepress_meta_caps' ), 10, 4 );
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
	public function _debug_get_plugin_options() {
		return $this->plugin_options->get();
	}

	/**
	 * Get all User Options (only used in Debug)
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of all User Options
	 */
	public function _debug_get_user_options() {
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

	/**
	 * Add default capabilities to "Administrator", "Editor", and "Author" roles
	 *
	 * @since 1.0.0
	 */
	public function add_access_capabilities() {
		// Capabilities for all roles
		$roles = array( 'administrator', 'editor', 'author' );
		foreach ( $roles as $role ) {
			$role =& get_role( $role );
			if ( empty( $role ) )
				continue;

			// from get_post_type_capabilities()
			$role->add_cap( 'tablepress_edit_tables' );
			// $role->add_cap( 'tablepress_edit_others_tables' );
			$role->add_cap( 'tablepress_delete_tables' );
			// $role->add_cap( 'tablepress_delete_others_tables' );

			// custom capabilities()
			$role->add_cap( 'tablepress_list_tables' );
			$role->add_cap( 'tablepress_add_tables' );
			$role->add_cap( 'tablepress_copy_tables' );
			$role->add_cap( 'tablepress_import_tables' );
			$role->add_cap( 'tablepress_export_tables' );
			$role->add_cap( 'tablepress_access_options_screen' );
			$role->add_cap( 'tablepress_access_about_screen' );
		}

		// Capabilities for single roles
		$role =& get_role( 'administrator' );
		if ( ! empty( $role ) ) {
			$role->add_cap( 'tablepress_import_tables_wptr' );
			$role->add_cap( 'tablepress_edit_options' );
		}

		// Refresh current set of capabilities of the user, to be able to directly use the new caps
		$user = wp_get_current_user();
		$user->get_role_caps();
	}

	/**
	 * Map TablePress meta capabilities to primitive capabilities
	 *
	 * @since 1.0.0
	 *
	 * @param array $caps Current set of primitive caps
	 * @param string $cap Meta cap that is to be checked/mapped
	 * @param int $user_id User ID for which meta cap is to be checked
	 * @param array $args Arguments for the check, here e.g. the table ID
	 * @return bool
	 */
	public function map_tablepress_meta_caps( $caps, $cap, $user_id, $args ) {
		if ( ! in_array( $cap, array( 'tablepress_edit_table', 'tablepress_edit_table_id', 'tablepress_copy_table', 'tablepress_delete_table', 'tablepress_export_table', 'tablepress_preview_table' ), true ) )
			return $caps;

		// $user = get_userdata( $user_id );
		// $username = $user->user_login);
		// $table_id = ( ! empty( $args ) ) ? $args[0] : false;

		// reset current set of primitive caps
		$caps = array();

		switch ( $cap ) {
			case 'tablepress_edit_table':
				$caps[] = 'tablepress_edit_tables';
				break;
			case 'tablepress_edit_table_id':
				$caps[] = 'tablepress_edit_tables';
				break;
			case 'tablepress_copy_table':
				$caps[] = 'tablepress_copy_tables';
				break;
			case 'tablepress_delete_table':
				$caps[] = 'tablepress_delete_tables';
				break;
			case 'tablepress_export_table':
				$caps[] = 'tablepress_export_tables';
				break;
			case 'tablepress_preview_table':
				$caps[] = 'tablepress_edit_tables';
				break;
			default:
				// something went wrong; deny access to be on the safe side
				$caps[] = 'do_not_allow';
				break;
		}

		$caps = apply_filters( 'tablepress_map_meta_caps', $caps, $cap, $user_id, $args );

		return $caps;
	}

	/**
	 * Retrieve the update message from the development server to notify users of the included changes in this update, in his language
	 *
	 * @since 1.0.0
	 *
	 * @param string $current_version Version before the update
	 * @param string $new_version Version after the update
	 * @param string $locale Desired locale of the message
	 * @return string Plugin update message
	 */
	public function plugin_update_message( $current_version, $new_version, $locale ) {
		$update_message = wp_remote_fopen( "http://dev.tablepress.org/plugin/update/{$current_version}/{$new_version}/{$locale}/" );
		if ( empty( $update_message ) )
			$update_message = '';
		return $update_message;
	}

	/**
	 * Load the contents of the file with the "Custom CSS"
	 *
	 * @since 1.0.0
	 *
	 * @param string $type "normal" version or "minified" version
	 * @return string|bool Custom CSS on success, false on error
	 */
	public function load_custom_css_from_file( $type = 'normal' ) {
		$suffix = ( 'minified' == $type ) ? '.min' : '';
		$filter_name = ( 'minified' == $type ) ? 'tablepress_custom_css_minified_file_name' : 'tablepress_custom_css_file_name';
		$filename = WP_CONTENT_DIR . "/tablepress-custom{$suffix}.css";
		$filename = apply_filters( $filter_name, $filename );
		// Check if file name is valid (0 means yes)
		if ( 0 !== validate_file( $filename ) )
			return false;
		if ( ! @is_file( $filename ) )
			return false;
		if ( ! @is_readable( $filename ) )
			return false;
		return file_get_contents( $filename );
	}

	/**
	 * Save "Custom CSS" to a file, or return HTML for the credentials form
	 *
	 * @since 1.0.0
	 *
	 * @return string (if necessary) HTML for the credentials form for the WP_Filesystem API
	 */
	public function save_custom_css_to_file() {
		// Set current screen to get Screen Icon to have a custom HTML ID, so that we can hide it with CSS
		set_current_screen( 'tablepress_options_invisible' );

		// Start capturing the output, to get HTML of the credentials form (if needed)
		ob_start();

		$url = ''; // same page
		$credentials = request_filesystem_credentials( $url, '', false, false, null );
		// do we have credentials already? (Otherwise the form will have been rendered already.)
		if ( false === $credentials ) {
			$form_data = ob_get_contents();
			ob_end_clean();
			$form_data = str_replace( 'name="upgrade" id="upgrade" class="button"', 'name="upgrade" id="upgrade" class="button button-primary button-large"', $form_data );
			return $form_data;
		}

		// we have received credentials, but don't know if they are valid yet
		if ( ! WP_Filesystem( $credentials ) ) {
			// credentials failed, so ask again (with $error flag true)
			request_filesystem_credentials( $url, '', true, false, null );
			$form_data = ob_get_contents();
			ob_end_clean();
			$form_data = str_replace( 'name="upgrade" id="upgrade" class="button"', 'name="upgrade" id="upgrade" class="button button-primary button-large"', $form_data );
			return $form_data;
		}

		// we have valid access to the filesystem now -> try to save the file
		$filename = WP_CONTENT_DIR . '/tablepress-custom.css';
		$filename = apply_filters( 'tablepress_custom_css_file_name', $filename );
		$filename_min = WP_CONTENT_DIR . '/tablepress-custom.min.css';
		$filename_min = apply_filters( 'tablepress_custom_css_minified_file_name', $filename_min );
		// Check if file name is valid (0 means yes)
		if ( 0 !== validate_file( $filename ) || 0 !== validate_file( $filename_min ) )
			TablePress::redirect( array( 'action' => 'options', 'message' => 'success_save_error_custom_css' ) );
		global $wp_filesystem;

		// WP_CONTENT_DIR and (FTP-)Content-Dir can be different (e.g. if FTP working dir is /)
		// We need to account for that by replacing the path difference in the filename
		$path_difference = str_replace( $wp_filesystem->wp_content_dir(), '', trailingslashit( WP_CONTENT_DIR ) );
		if ( '' != $path_difference ) {
			$filename = str_replace( $path_difference, '', $filename );
			$filename_min = str_replace( $path_difference, '', $filename_min );
		}

		$custom_css = $this->get( 'custom_css' );
		$custom_css_minified = $this->get( 'custom_css_minified' );
		$result = $wp_filesystem->put_contents( $filename, $custom_css, FS_CHMOD_FILE );
		$result_min = $wp_filesystem->put_contents( $filename_min, $custom_css_minified, FS_CHMOD_FILE );
		if ( ! $result || ! $result_min )
			TablePress::redirect( array( 'action' => 'options', 'message' => 'success_save_error_custom_css' ) );

		// at this point, saving was successful, so enable the checkbox again
		// (if it was not enabled before, we would never have tried to save)
		// and also increase the "Custom CSS" version number (for cache busting)
		$this->update( array(
			'use_custom_css_file' => true,
			'custom_css_version' => $this->get( 'custom_css_version' ) + 1
		) );
		TablePress::redirect( array( 'action' => 'options', 'message' => 'success_save' ) );
	}

} // class TablePress_Options_Model