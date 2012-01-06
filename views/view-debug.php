<?php
/**
 * Debug View
 *
 * @package TablePress
 * @subpackage Debug View
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Debug View class
 *
 * @since 1.0.0
 */
class TablePress_Debug_View extends TablePress_View {

	/**
	 * Set up the view with data and do things that are specific for this view
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action for this view
	 * @param array $data Data for this view
	 */
	public function setup( $action, $data ) {
		parent::setup( $action, $data );

		$this->page_title = __( 'Debug', 'tablepress' );

		$action_messages = array(
			'success_save' => __( 'Debug Changes saved successfully.', 'tablepress' ),
			'error_save' => __( 'Error: Debug Changes could not be saved.', 'tablepress' )
		);
		if ( $data['message'] && isset( $action_messages[ $data['message'] ] ) ) {
			$class = ( in_array( $data['message'], array( 'error_save' ) ) ) ? 'error' : 'updated' ;
			$this->add_header_message( "<strong>{$action_messages[ $data['message'] ]}</strong>", $class );
		}

		$this->add_meta_box( 'plugin-options', __( 'Plugin Options', 'tablepress' ), array( &$this, 'postbox_plugin_options' ), 'normal' );
		$this->add_meta_box( 'user-options', __( 'User Options', 'tablepress' ), array( &$this, 'postbox_user_options' ), 'normal' );
		$this->add_text_box( 'head', array( &$this, 'textbox_head' ), 'normal' );
		$this->add_text_box( 'submit', array( &$this, 'textbox_submit_button' ), 'submit' );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_plugin_options( $data, $box ) {
		echo 'tablepress_plugin_options (JSON):<br/><input type="text" class="large-text" name="debug[plugin_options]" value="' . esc_attr( $data['debug']['plugin_options'] ) . '" />';
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_user_options( $data, $box ) {
		echo 'tablepress_user_options (JSON):<br/><input type="text" class="large-text" name="debug[user_options]" value="' . esc_attr( $data['debug']['user_options'] ) . '" />';
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_head( $data, $box ) {
		?>
		<p><?php _e( 'head text box', 'tablepress' ); ?></p>
		<p><?php echo $this->page_title; ?></p>
		<?php
	}

	/**
	 * Return the content for the help tab for this screen
	 *
	 * @since 1.0.0
	 */
	protected function help_tab_content() {
		return 'Help for the Debug screen';
	}

} // class TablePress_Debug_View