<?php
/**
 * TablePress Base Controller with members and methods for all controllers
 *
 * @package TablePress
 * @subpackage Base Controller
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Base Controller class
 *
 * @since 1.0.0
 */
abstract class TablePress_Controller {

	/**
	 * @var object Instance of the Options Model
	 *
	 * @since 1.0.0
	 */
	public $model_options;

	/**
	 * @var object Instance of the Table Model
	 *
	 * @since 1.0.0
	 */
	public $model_table;

	/**
	 * @var string File name of the admin screens's parent page in the admin menu
	 *
	 * @since 1.0.0
	 */
	public $parent_page = 'tools.php';

	/**
	 * @var bool Whether TablePress admin screens are a top-level menu item in the admin menu
	 *
	 * @since 1.0.0
	 */ 
	public $is_top_level_page = false;

	/**
	 * Initialize all controllers, by loading Plugin and User Options, and performing an update check
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->model_options = TablePress::load_model( 'options' );
		$this->plugin_update_check(); // should be done very early

		// Admin Page Menu entry, needed for construction of plugin URLs
		$this->parent_page = apply_filters( 'tablepress_admin_menu_parent_page', $this->model_options->get( 'admin_menu_parent_page' ) );
		$this->is_top_level_page = in_array( $this->parent_page, array( 'top', 'middle', 'bottom' ) );		
	}

	/**
	 * Check if the plugin was updated and perform necessary actions, like updating the options
	 *
	 * @since 1.0.0
	 */
	protected function plugin_update_check() {
		// Update Plugin Options, if necessary
		if ( $this->model_options->get( 'plugin_options_db_version', 0 ) < TablePress::db_version ) {
			$this->model_options->merge_plugin_options_defaults();
			$this->model_options->update( array(
				'plugin_options_db_version' => TablePress::db_version,
				'tablepress_version' => TablePress::version,
				'message_plugin_update' => true
			) );
		}

		// Update User Options, if necessary
		if ( is_user_logged_in() &&	( $this->model_options->get( 'user_options_db_version', 0 ) < TablePress::db_version ) ) {
			$this->model_options->merge_user_options_defaults();
			$this->model_options->update( array(
				'user_options_db_version' => TablePress::db_version
			) );			
		}

		// Save time of first activation of the plugin in option
		if ( 0 == $this->model_options->get( 'first_activation', 0 ) ) {
			$this->model_options->update( array(
				'first_activation' => time()
			) );			
		}
	}

} // class TablePress_Controller