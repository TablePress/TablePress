<?php
/**
 * Plugin Options View
 *
 * @package TablePress
 * @subpackage Plugin Options View
 * @author Tobias Bäthge
 * @since 1.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Plugin Options View class
 */
class TablePress_Options_View extends TablePress_View {

	/*
	 * Set up the view with data and do things that are specific for this view
	 *
	 * @param string $action Action for this view
	 * @param array $data Data for this view
	 */
	public function setup( $action, $data ) {
		parent::setup( $action, $data );

		$this->page_title = __( 'Plugin Options &lsaquo; TablePress', 'tablepress' );

		$action_messages = array(
			'success_save' => __( 'Options saved successfully.', 'tablepress' ),
			'error_save' => __( 'Error: Options could not be saved.', 'tablepress' )
		);
		if ( $data['message'] && isset( $action_messages[ $data['message'] ] ) ) {
			$class = ( in_array( $data['message'], array( 'error_save' ) ) ) ? 'error' : 'updated' ;
			$this->add_header_message( "<strong>{$action_messages[ $data['message'] ]}</strong>", $class );
		}

		$this->add_meta_box( 'table-information', __( 'Table Information', 'tablepress' ), array( &$this, 'postbox_table_information' ), 'normal' );
		$this->add_text_box( 'head', array( &$this, 'textbox_head' ), 'normal' );
		$this->add_meta_box( 'user-options', __( 'User Options', 'tablepress' ), array( &$this, 'postbox_user_options' ), 'normal' );
		$this->add_text_box( 'submit', array( &$this, 'textbox_submit_button' ), 'submit' );
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
	 * Render a form for user options
	 */
	public function postbox_user_options( $data, $box ) {
		?>
		<table class="form-table">
		<?php	
		// get list of current admin menu entries
		$entries = array();
		foreach ( $GLOBALS['menu'] as $entry ) {
			if ( false !== strpos( $entry[2], '.php' ) )
				$entries[ $entry[2] ] = $entry[0];
		}
		
		// remove <span> elements with notification bubbles (e.g. update or comment count)
		if ( isset( $entries['plugins.php'] ) )
			$entries['plugins.php'] = preg_replace( '/ <span.*span>/', '', $entries['plugins.php'] );
		if ( isset( $entries['edit-comments.php'] ) )
			$entries['edit-comments.php'] = preg_replace( '/ <span.*span>/', '', $entries['edit-comments.php'] );

		// add separator and generic positions
		$entries['-'] = __( '---', 'tablepress' );
		$entries['top'] = __( 'Top-Level (top)', 'tablepress' );
		$entries['middle'] = __( 'Top-Level (middle)', 'tablepress' );
		$entries['bottom'] = __( 'Top-Level (bottom)', 'tablepress' );
	
		$select_box = '<select id="options_admin_menu_parent_page" name="options[admin_menu_parent_page]">' . "\n";
		foreach ( $entries as $page => $entry ) {
			$select_box .= '<option' . selected( $page, $data['user_options']['parent_page'], false ) . disabled( $page, '-', false ) .' value="' . $page . '">' . $entry . "</option>\n";
		}
		$select_box .= "</select>\n";
		?>
		<tr>
			<th scope="row"><label for="options_admin_menu_parent_page"><?php _e( 'Admin menu entry', 'tablepress' ); ?>:</label></th>
			<td><?php printf( __( 'TablePress shall be shown in this section of the admin menu: %s', 'tablepress' ), $select_box ); ?></td>
		</tr>
		<?php
		$select_box = '<select id="options_plugin_language" name="options[plugin_language]">' . "\n";
		$select_box .= '<option' . selected( $data['user_options']['plugin_language'], 'auto', false ) . ' value="auto">' . sprintf( __( 'WordPress Default (currently %s)', 'tablepress' ), get_locale() ) . "</option>\n";
		$select_box .= '<option value="-" disabled="disabled">---</option>' . "\n";
		foreach ( $data['user_options']['available_plugin_languages'] as $lang_abbr => $language ) {
        	$select_box .= '<option' . selected( $data['user_options']['plugin_language'], $lang_abbr, false ) . ' value="' . $lang_abbr . '">' . "{$language} ({$lang_abbr})</option>\n";
		}
		$select_box .= "</select>\n";
		?>
        <tr>
            <th scope="row"><label for="options_plugin_language"><?php _e( 'Plugin Language', 'tablepress' ); ?>:</label></th>
			<td><?php printf( __( 'TablePress shall be shown in this language: %s', 'tablepress' ), $select_box ); ?></td>
        </tr>
		</table>
		<?php
	}

	/*
	 * Return the content for the help tab for this screen
	 */
	protected function help_tab_content() {
		return 'Help for the Plugin Options screen';
	}

} // class TablePress_Options_View