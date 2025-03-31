<?php
/**
 * Plugin Options View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Plugin Options View class
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Options_View extends TablePress_View {

	/**
	 * Set up the view with data and do things that are specific for this view.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $action Action for this view.
	 * @param array<string, mixed> $data   Data for this view.
	 */
	#[\Override]
	public function setup( /* string */ $action, array $data ) /* : void */ {
		// Don't use type hints in the method declaration to prevent PHP errors, as the method is inherited.

		parent::setup( $action, $data );

		$this->admin_page->enqueue_script( 'options' );

		$this->process_action_messages( array(
			'success_save'                  => __( 'Options saved successfully.', 'tablepress' ),
			'success_save_error_custom_css' => __( 'Options saved successfully, but &#8220;Custom CSS&#8221; was not saved to file.', 'tablepress' ),
			'error_save'                    => __( 'Error: Options could not be saved.', 'tablepress' ),
		) );

		$this->add_text_box( 'head', array( $this, 'textbox_head' ), 'normal' );
		if ( current_user_can( 'tablepress_edit_options' ) ) {
			// Enqueue WordPress copy of CodeMirror, with CSS linting, etc., for the "Custom CSS" textarea, which is only shown to admins.
			$codemirror_settings = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
			if ( ! empty( $codemirror_settings ) ) {
				// Load CSS adjustments for CodeMirror and the added vertical resizing.
				$this->admin_page->enqueue_style( 'codemirror', array( 'code-editor' ) );
				$this->admin_page->enqueue_script( 'codemirror' );
			}

			if ( ! TABLEPRESS_IS_PLAYGROUND_PREVIEW ) {
				$this->add_meta_box( 'default-style', sprintf( __( 'Default Styling %s', 'tablepress' ), tb_tp_fs()->is_free_plan() ? '<span class="pill-label">' . __( 'Premium', 'tablepress' ) . '</span>' : '' ), array( $this, 'postbox_default_style_customizer_screen' ), 'normal' );
			}
			$this->add_meta_box( 'frontend-options', __( 'Custom Styling', 'tablepress' ), array( $this, 'postbox_frontend_options' ), 'normal' );
		}
		$this->add_meta_box( 'user-options', __( 'User Options', 'tablepress' ), array( $this, 'postbox_user_options' ), 'normal' );
		$this->add_text_box( 'submit', array( $this, 'textbox_submit_button' ), 'submit' );
		if ( current_user_can( 'deactivate_plugin', TABLEPRESS_BASENAME ) && current_user_can( 'tablepress_edit_options' ) && current_user_can( 'tablepress_delete_tables' ) && ! is_plugin_active_for_network( TABLEPRESS_BASENAME ) ) {
			$this->add_text_box( 'uninstall-tablepress', array( $this, 'textbox_uninstall_tablepress' ), 'submit' );
		}
	}

	/**
	 * Prints the screen head text.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_head( array $data, array $box ): void {
		?>
		<p>
			<?php _e( 'TablePress has some options which affect the plugin&#8217;s behavior in different areas.', 'tablepress' ); ?>
		</p>
		<?php
	}

	/**
	 * Prints the content of the "Default Style Customizer Screen" post meta box.
	 *
	 * @since 2.2.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_default_style_customizer_screen( array $data, array $box ): void {
		if ( tb_tp_fs()->is_free_plan() ) :
			?>
			<div style="display:flex;max-width:800px;gap:20px;font-size:14px;">
				<div>
					<p style="font-size:14px;">
						<strong><?php _e( 'Did you know?', 'tablepress' ); ?></strong>
						<?php _e( 'The TablePress premium versions come with a table default style customizer!', 'tablepress' ); ?>
						<?php _e( 'Choose from multiple style variations or define your own color scheme in an easy-to-use visual tool!', 'tablepress' ); ?>
						<strong><?php _e( 'Change your tables’ default style without touching CSS code!', 'tablepress' ); ?></strong>
					</p>
					<div class="buttons" style="text-align:center;">
						<a href="https://tablepress.org/modules/default-style-customizer/?utm_source=plugin&utm_medium=textlink&utm_content=options-screen" class="tablepress-button">
							<span><?php _e( 'Find out more', 'tablepress' ); ?></span>
							<span class="dashicons dashicons-arrow-right-alt"></span>
						</a>
					</div>
				</div>
				<a href="https://tablepress.org/modules/default-style-customizer/?utm_source=plugin&utm_medium=textlink&utm_content=options-screen"><img src="<?php echo esc_url( plugins_url( 'admin/img/default-style-customizer.png', TABLEPRESS__FILE__ ) ); ?>" width="305" height="172" alt="<?php esc_attr_e( 'Screenshot of the Default Style Customizer that is part of the TablePress premium versions.', 'tablepress' ); ?>"></a>
			</div>
			<?php
		endif;
	}

	/**
	 * Prints the content of the "Frontend Options" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_frontend_options( array $data, array $box ): void {
		?>
<table class="tablepress-postbox-table fixed">
	<tr>
		<th class="column-1" scope="row"><label for="option-custom-css"><?php _e( 'Custom CSS', 'tablepress' ); ?></label>:</th>
		<td class="column-2"><label><input type="checkbox" id="option-use-custom-css" name="options[use_custom_css]" value="true"<?php checked( $data['frontend_options']['use_custom_css'] ); ?>> <?php _e( 'Load this &#8220;Custom CSS&#8221; code to change the table styling:', 'tablepress' ); ?></label>
		</td>
	</tr>
	<tr>
		<td class="column-1"></td>
		<td class="column-2">
			<textarea name="options[custom_css]" id="option-custom-css" class="large-text" rows="8" autocomplete="off"><?php echo esc_textarea( $data['frontend_options']['custom_css'] ); ?></textarea>
			<p class="description">
			<?php
				printf( __( '&#8220;Custom CSS&#8221; (<a href="%s">Cascading Style Sheets</a>) can be used to change the styling or layout of a table.', 'tablepress' ), 'https://www.htmldog.com/guides/css/beginner/' );
				echo ' ';
				printf( __( 'You can get styling examples from the <a href="%s">FAQ</a>.', 'tablepress' ), 'https://tablepress.org/faq/' );
				echo ' ';
				printf( __( 'Information on available CSS selectors can be found in the <a href="%s">Documentation</a>.', 'tablepress' ), 'https://tablepress.org/documentation/' );
				echo ' ';
				_e( 'Please note that invalid CSS code will be stripped, if it can not be corrected automatically.', 'tablepress' );
			?>
			</p>
		</td>
	</tr>
</table>
		<?php
	}

	/**
	 * Prints the content of the "User Options" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the meta box.
	 */
	public function postbox_user_options( array $data, array $box ): void {
		// Get list of current admin menu entries.
		$entries = array();
		foreach ( $GLOBALS['menu'] as $entry ) {
			if ( str_contains( $entry[2], '.php' ) ) {
				$entries[ $entry[2] ] = $entry[0];
			}
		}

		// Remove <span> elements with notification bubbles (e.g. update or comment count).
		if ( isset( $entries['plugins.php'] ) ) {
			$entries['plugins.php'] = preg_replace( '/ <span.*span>/', '', $entries['plugins.php'] );
		}
		if ( isset( $entries['edit-comments.php'] ) ) {
			$entries['edit-comments.php'] = preg_replace( '/ <span.*span>/', '', $entries['edit-comments.php'] );
		}

		// Add separator and generic positions.
		$entries['-'] = '---';
		$entries['top'] = __( 'Top-Level (top)', 'tablepress' );
		$entries['middle'] = __( 'Top-Level (middle)', 'tablepress' );
		$entries['bottom'] = __( 'Top-Level (bottom)', 'tablepress' );

		$select_box = '<select id="option-admin-menu-parent-page" name="options[admin_menu_parent_page]">' . "\n";
		foreach ( $entries as $page => $entry ) {
			$select_box .= '<option' . selected( $page, $data['user_options']['parent_page'], false ) . disabled( $page, '-', false ) . ' value="' . $page . '">' . $entry . "</option>\n";
		}
		$select_box .= "</select>\n";
		?>
<table class="tablepress-postbox-table fixed">
	<tr>
		<th class="column-1" scope="row"><label for="option-admin-menu-parent-page"><?php _e( 'Admin menu entry', 'tablepress' ); ?>:</label></th>
		<td class="column-2"><?php printf( __( 'TablePress shall be shown in this section of my admin menu: %s', 'tablepress' ), $select_box ); ?></td>
	</tr>
</table>
		<?php
	}

	/**
	 * Prints "Save Changes" button.
	 *
	 * @since 2.2.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	#[\Override]
	public function textbox_submit_button( array $data, array $box ): void {
		?>
			<p class="submit">
				<input type="submit" id="tablepress-options-save-changes" class="components-button is-primary button-save-changes" value="<?php esc_attr_e( 'Save Changes', 'tablepress' ); ?>" data-shortcut="<?php echo esc_attr( _x( '%1$sS', 'keyboard shortcut for Save Changes', 'tablepress' ) ); ?>">
			</p>
		<?php
	}

	/**
	 * Prints the content of the "Admin Options" post meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_uninstall_tablepress( array $data, array $box ): void {
		?>
		<h1 style="margin-top:40px;"><?php _e( 'Uninstall TablePress', 'tablepress' ); ?></h1>
		<p>
		<?php
			echo __( 'Uninstalling <strong>will permanently delete</strong> all TablePress tables and options from the database.', 'tablepress' ) . '<br>'
				. __( 'It is recommended that you create a backup of the tables (by exporting the tables in the JSON format), in case you later change your mind.', 'tablepress' ) . '<br>'
				. __( 'You will manually need to remove the plugin&#8217;s files from the plugin folder afterwards.', 'tablepress' ) . '<br>'
				. __( 'Be very careful with this and only click the button if you know what you are doing!', 'tablepress' );
		?>
		</p>
		<p><a href="<?php echo TablePress::url( array( 'action' => 'uninstall_tablepress' ), true, 'admin-post.php' ); ?>" id="uninstall-tablepress" class="components-button is-secondary is-destructive"><?php _e( 'Uninstall TablePress', 'tablepress' ); ?></a></p>
		<?php
	}

} // class TablePress_Options_View
