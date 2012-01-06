<?php
/**
 * Import Table View
 *
 * @package TablePress
 * @subpackage Import Table View
 * @author Tobias Bäthge
 * @since 1.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Import Table View class
 */
class TablePress_Import_View extends TablePress_View {

	/*
	 * Set up the view with data and do things that are specific for this view
	 *
	 * @param string $action Action for this view
	 * @param array $data Data for this view
	 */
	public function setup( $action, $data ) {
		parent::setup( $action, $data );

		$this->page_title = __( 'Import Table', 'tablepress' );

		$this->add_meta_box( 'table-information', __( 'Table Information', 'tablepress' ), array( &$this, 'postbox_table_information' ), 'normal' );
		$this->add_text_box( 'head', array( &$this, 'textbox_head' ), 'normal' );
	}

	/*
	 *
	 */
	public function postbox_table_information( $data, $box ) {
		_e( 'Table Information:', 'tablepress' );
	}

	/*
	 *
	 */
	public function textbox_head( $data, $box ) {
		?>
		<p><?php _e( 'head text box', 'tablepress' ); ?></p>
		<p><?php echo $this->page_title; ?></p>
		<?php
	}

	/*
	 * Return the content for the help tab for this screen
	 */
	protected function help_tab_content() {
		return 'Help for the Import Table screen';
	}

} // class TablePress_Import_View