<?php
/**
 * TablePress Base Controller with members and methods for all controllers
 *
 * @package TablePress
 * @subpackage Base Controller
 * @author Tobias Bäthge
 * @since 1.0
 */

/**
 * Base Controller class
 */
abstract class TablePress_Controller {

	/*
	 * @var object Instance of the Options Model
	 */
	protected $model_options;

	/*
	 * @var object Instance of the Table Model
	 */
	protected $model_table;

	/*
	 * @var string TablePress slug, used in actions/links/URLs
	 */
	public $slug = 'tablepress';

	/*
	 * @var string Slug/file name of the admin screens' parent page in the admin menu
	 */
	public $parent_page = 'tools.php';

	/*
	 * @var bool Whether TablePress admin screens are a top-level menu item in the admin menu
	 */ 
	public $is_top_level_page = false;

	/*
	 * Initialize all controllers, by loading Plugin and User Options, and performing an update check
	 */
	public function __construct() {
		$this->model_options = TablePress::load_model( 'options' );
		$this->plugin_update_check();

		// Admin Page Menu entry, needed for construction of plugin URLs
		$this->parent_page = apply_filters( 'tablepress_admin_menu_parent_page', $this->model_options->get( 'admin_menu_parent_page' ) );
		$this->is_top_level_page = in_array( $this->parent_page, array( 'top', 'middle', 'bottom' ) );		
	}

	/*
	 * Check if the plugin was updated and perform necessary actions, like updating the options
	 */
	private function plugin_update_check() {
		if ( version_compare( $this->model_options->get( 'plugin_options_version', '0' ), TablePress::version, '<' ) ) {
			$this->model_options->merge_plugin_options_defaults();
			$this->model_options->update( array(
				'plugin_options_version' => TablePress::version
			) );
		}

		if ( is_user_logged_in() &&	version_compare( $this->model_options->get( 'user_options_version', '0' ), TablePress::version, '<' ) ) {
			$this->model_options->merge_user_options_defaults();
			$this->model_options->update( array(
				'user_options_version' => TablePress::version
			) );			
		}
	}

} // class TablePress_Controller