<?php
/**
 * Plugin Options View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Plugin Options View class
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Options_View extends TablePress_View {

	/**
	 * List of WP feature pointers for this view
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	// protected $wp_pointers = array( 'tp100_custom_css' ); // @TODO: Temporarily disabled

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

		$this->admin_page->enqueue_style( 'codemirror' );
		$this->admin_page->enqueue_script( 'codemirror', array(), false, true );
		$this->admin_page->enqueue_script( 'options', array( 'jquery', 'tablepress-codemirror' ) );

		$action_messages = array(
			'success_save' => __( 'Options saved successfully.', 'tablepress' ),
			'success_save_error_custom_css' => __( 'Options saved successfully, but &quot;Custom CSS&quot; was not saved to file.', 'tablepress' ),
			'error_save' => __( 'Error: Options could not be saved.', 'tablepress' ),
			'success_import_wp_table_reloaded' => __( 'The WP-Table Reloaded &quot;Custom CSS&quot; was imported successfully.', 'tablepress' )
		);
		if ( $data['message'] && isset( $action_messages[ $data['message'] ] ) ) {
			$class = ( 'error' == substr( $data['message'], 0, 5 ) ) ? 'error' : 'updated';
			$this->add_header_message( "<strong>{$action_messages[ $data['message'] ]}</strong>", $class );
		}

		$this->add_text_box( 'head', array( $this, 'textbox_head' ), 'normal' );
		$this->add_meta_box( 'frontend-options', __( 'Frontend Options', 'tablepress' ), array( $this, 'postbox_frontend_options' ), 'normal' );
		$this->add_meta_box( 'user-options', __( 'User Options', 'tablepress' ), array( $this, 'postbox_user_options' ), 'normal' );
		$this->data['submit_button_caption'] = __( 'Save Changes', 'tablepress' );
		$this->add_text_box( 'submit', array( $this, 'textbox_submit_button' ), 'submit' );
	}

	/**
	 * Print the screen head text
	 *
	 * @since 1.0.0
	 */
	public function textbox_head( $data, $box ) {
		?>
		<p>
			<?php _e( 'TablePress has several options which affect the plugin\'s behavior in different areas.', 'tablepress' ); ?>
		</p>
		<p>
			<?php _e( 'Frontend Options influence the styling of tables in pages, posts, or text widgets, by defining which CSS code shall be loaded.', 'tablepress' ); ?>
		<br />
			<?php _e( 'In the User Options, every TablePress user can choose the position of the plugin in the WordPress admin menu, and the desired plugin language.', 'tablepress' ); ?>
			<?php // _e( 'Administrators have access to further Admin Options, e.g. to control which user groups are allowed to use TablePress.', 'tablepress' ); ?></p>
		<?php
	}

	/**
	 * Print the content of the "Frontend Options" post meta box
	 *
	 * @since 1.0.0
	 */
	public function postbox_frontend_options( $data, $box ) {
?>
<table class="tablepress-postbox-table fixed">
<tbody>
	<tr class="bottom-border">
		<th class="column-1" scope="row"><?php _e( 'Default CSS', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-use-default-css"><input type="checkbox" id="option-use-default-css" name="options[use_default_css]" value="true"<?php checked( $data['frontend_options']['use_default_css'] ); ?> /> <?php _e( 'Load the default table styling.', 'tablepress' ); ?> <?php _e( '<span class="description">(recommended)</span>', 'tablepress' ); ?></label>
		</td>
	</tr>
	<tr class="top-border">
		<th class="column-1" scope="row"><?php _e( 'Custom CSS', 'tablepress' ); ?>:</th>
		<td class="column-2"><label for="option-use-custom-css"><input type="checkbox" id="option-use-custom-css" name="options[use_custom_css]" value="true"<?php checked( $data['frontend_options']['use_custom_css'] ); ?> /> <?php _e( 'Load these "Custom CSS" commands to influence the table styling:', 'tablepress' ); ?></label>
		</td>
	</tr>
	<tr>
		<th class="column-1" scope="row"></th>
		<td class="column-2">
			<textarea name="options[custom_css]" id="option-custom-css" class="large-text" rows="8"><?php echo esc_textarea( $data['frontend_options']['custom_css'] ); ?></textarea>
			<p class="description"><?php
				_e( sprintf( '"Custom CSS" (<a href="%s">Cascading Style Sheets</a>) can be used to change the styling or layout of a table.', 'http://www.htmldog.com/guides/cssbeginner/' ), 'tablepress' );
				echo ' ';
				_e( sprintf( 'You can get styling examples from the <a href="%s">FAQ</a>.', 'http://tablepress.org/faq/' ), 'tablepress' );
				echo ' ';
				_e( sprintf( 'Information on available CSS selectors can be found in the <a href="%s">documentation</a>.', 'http://tablepress.org/documentation/' ), 'tablepress' );
			?></p>
			<label for="option-use-custom-css-file"><input type="checkbox" id="option-use-custom-css-file" name="options[use_custom_css_file]" value="true"<?php checked( $data['frontend_options']['use_custom_css_file'] ); ?> /> <?php _e( 'Use a file for storing and loading the "Custom CSS" code.', 'tablepress' ); ?> <?php _e( '<span class="description">(recommended)</span>', 'tablepress' ); ?></label><br />
			<input type="checkbox" style="visibility: hidden;" <?php // Dummy checkbox for space alignment ?>/> <?php echo content_url( 'tablepress-custom.css' ) . ' ' . ( ( $data['frontend_options']['custom_css_file_exists'] ) ? '(File exists.)' : '(File seems not to exist.)' ); ?>
		</td>
	</tr>
</tbody>
</table>
<?php
	}

	/**
	 * Print the content of the "User Options" post meta box
	 *
	 * @since 1.0.0
	 */
	public function postbox_user_options( $data, $box ) {
		?>
<table class="tablepress-postbox-table fixed">
<tbody>
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
		$entries['-'] = '---';
		$entries['top'] = __( 'Top-Level (top)', 'tablepress' );
		$entries['middle'] = __( 'Top-Level (middle)', 'tablepress' );
		$entries['bottom'] = __( 'Top-Level (bottom)', 'tablepress' );

		$select_box = '<select id="option-admin-menu-parent-page" name="options[admin_menu_parent_page]">' . "\n";
		foreach ( $entries as $page => $entry ) {
			$select_box .= '<option' . selected( $page, $data['user_options']['parent_page'], false ) . disabled( $page, '-', false ) .' value="' . $page . '">' . $entry . "</option>\n";
		}
		$select_box .= "</select>\n";
		?>
	<tr class="bottom-border">
		<th class="column-1" scope="row"><label for="option-admin-menu-parent-page"><?php _e( 'Admin menu entry', 'tablepress' ); ?>:</label></th>
		<td class="column-2"><?php printf( __( 'TablePress shall be shown in this section of the admin menu: %s', 'tablepress' ), $select_box ); ?></td>
	</tr>
		<?php
		$select_box = '<select id="option-plugin-language" name="options[plugin_language]">' . "\n";
		$select_box .= '<option' . selected( $data['user_options']['plugin_language'], 'auto', false ) . ' value="auto">' . sprintf( __( 'WordPress Default (currently %s)', 'tablepress' ), get_locale() ) . "</option>\n";
		$select_box .= '<option value="-" disabled="disabled">---</option>' . "\n";
		foreach ( $data['user_options']['plugin_languages'] as $lang_abbr => $language ) {
			$select_box .= '<option' . selected( $data['user_options']['plugin_language'], $lang_abbr, false ) . ' value="' . $lang_abbr . '">' . "{$language['name']} ({$lang_abbr})</option>\n";
		}
		$select_box .= "</select>\n";
		?>
	<tr class="top-border">
		<th class="column-1" scope="row"><label for="option-plugin-language"><?php _e( 'Plugin Language', 'tablepress' ); ?>:</label></th>
		<td class="column-2"><?php printf( __( 'TablePress shall be shown in this language: %s', 'tablepress' ), $select_box ); ?></td>
	</tr>
</tbody>
</table>
<?php
	}

	/**
	 * Return the content for the help tab for this screen
	 *
	 * @since 1.0.0
	 */
	protected function help_tab_content() {
		return 'Help for the Plugin Options screen';
	}

	/**
	 * Set the content for the WP feature pointer about the TablePress nav bar
	 *
	 * @since 1.0.0
	 */
	public function wp_pointer_tp100_custom_css() {
		$content  = '<h3>' . __( 'TablePress Feature: Custom CSS', 'tablepress' ) . '</h3>';
		$content .= '<p>' .	 __( 'This is the "Custom CSS" textarea where CSS code for table styling should be entered.', 'tablepress' ) . '</p>';

		$this->admin_page->print_wp_pointer_js( 'tp100_custom_css', '.CodeMirror', array(
			'content'  => $content,
			'position' => array( 'edge' => 'right', 'align' => 'center', 'offset' => '-16 0', 'defer_loading' => true ),
		) );
	}

} // class TablePress_Options_View