<?php
/**
 * Frontend Controller for TablePress with functionality for the frontend
 *
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Frontend Controller class, extends Base Controller Class
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Frontend_Controller extends TablePress_Controller {

	/**
	 * Initiate Frontend functionality
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		//require_once ( TABLEPRESS_ABSPATH . 'libraries/template-tag-functions.php' );
		//$this->model_table = TablePress::load_model( 'table' );
	}

} // class TablePress_Frontend_Controller