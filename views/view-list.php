<?php
/**
 * List Tables View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * List Tables View class
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_List_View extends TablePress_View {

	/**
	 * Object for the All Tables List Table.
	 *
	 * @since 1.0.0
	 * @var TablePress_All_Tables_List_Table
	 */
	protected $wp_list_table;

	/**
	 * Set up the view with data and do things that are specific for this view.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action for this view.
	 * @param array  $data   Data for this view.
	 */
	public function setup( $action, array $data ) {
		parent::setup( $action, $data );

		add_thickbox(); // For the table preview.
		$this->admin_page->enqueue_script( 'list' );

		if ( $data['messages']['first_visit'] ) {
			$message = '<p><strong style="font-size:14px;">' . __( 'Thank you for choosing TablePress, the most popular table plugin for WordPress!', 'tablepress' ) . '</strong></p>';
			$message .= '<p>' . sprintf( __( 'If you encounter any questions or problems, please visit the <a href="%1$s">FAQ</a>, the <a href="%2$s">Documentation</a>, and the <a href="%3$s">Support</a> section on the <a href="%4$s">plugin website</a>.', 'tablepress' ), 'https://tablepress.org/faq/', 'https://tablepress.org/documentation/', 'https://tablepress.org/support/', 'https://tablepress.org/' ) . '</p>';

			if ( tb_tp_fs()->is_free_plan() ) {
				$message .= '<p style="font-size:14px;"><strong>' . sprintf( __( 'More great features for you and your site’s visitors and priority email support are available with a Premium license plan of TablePress. <a href="%s">Go check them out!</a>', 'tablepress' ), 'https://tablepress.org/premium/' ) . '</strong></p>';
			}

			$message .= '<p style="margin-top:14px;">' . $this->ajax_link( array( 'action' => 'hide_message', 'item' => 'first_visit', 'return' => 'list' ), __( 'Hide this message', 'tablepress' ) ) . '</p>';

			$title = '<em>' . __( 'Welcome!', 'tablepress' ) . '</em>';

			$this->add_header_message( $message, 'notice-info not-dismissible', $title );
		}

		if ( $data['messages']['donation_message'] ) {
			$message = '<p><img alt="' . esc_attr__( 'Tobias Bäthge, developer of TablePress', 'tablepress' ) . '" src="https://secure.gravatar.com/avatar/50f1cff2e27a1f522b18ce229c057bc5?s=125" height="125" width="125" style="float:left;margin:2px 10px 30px 0;" />'
				. __( 'Hi, my name is Tobias, I&#8217;m the developer of the TablePress plugin.', 'tablepress' ) . '</p>';
			$message .= '<p>' . __( 'Thank you for using it!', 'tablepress' ) . ' ';
			if ( $data['table_count'] > 0 ) {
				$message .= sprintf( _n( 'I hope that everything works and that you are satisfied with the results of managing your %s table.', 'I hope that everything works and that you are satisfied with the results of managing your %s tables.', $data['table_count'], 'tablepress' ), $data['table_count'] );
			} else {
				$message .= sprintf( __( 'It looks like you haven’t added a table yet. If you need help to get started, please find more information in the FAQ and Documentation on the <a href="%s">TablePress website</a>.', 'tablepress' ), 'https://tablepress.org/' );
			}
			$message .= '</p>';
			$message .= '<p><strong>' . sprintf( __( 'I would like to invite you to check out the <a href="%s">Premium versions of TablePress</a>.', 'tablepress' ), 'https://tablepress.org/premium/' ) . ' '
				. __( 'The available Pro and Max plans offer user support and many exciting and helpful features for your tables.', 'tablepress' ) . '</strong></p>';
			$message .= '<p>' . __( 'Sincerely, Tobias', 'tablepress' ) . '</p>';
			$message .= '<p>' . sprintf( '<a href="%s" class="button button-primary" style="font-size:14px;margin-right:1em"><strong>%s</strong><span class="dashicons dashicons-arrow-right-alt" style="vertical-align:middle;margin:0 0 4px 4px"></span></a>', 'https://tablepress.org/premium/', __( 'Tell me more about the Premium features', 'tablepress' ) )
				. $this->ajax_link( array( 'action' => 'hide_message', 'item' => 'donation_nag', 'return' => 'list' ), __( 'Hide this message', 'tablepress' ) ) . '</p>';

			$title = '<em>' . __( 'TablePress has more to offer!', 'tablepress' ) . '</em>';

			$this->add_header_message( $message, 'notice-success not-dismissible', $title );
		}

		if ( $data['messages']['plugin_update_message'] ) {
			$message = '<p>' . sprintf( __( 'To find out more about what’s new, please read the <a href="%s"><strong>release announcement</strong></a>.', 'tablepress' ), 'https://tablepress.org/news/' ) . '</p>';

			if ( tb_tp_fs()->is_free_plan() ) {
				$message .= '<p style="font-size:14px;"><strong>' . sprintf( __( 'More great features for you and your site’s visitors and priority email support are available with a Premium license plan of TablePress. <a href="%s">Go check them out!</a>', 'tablepress' ), 'https://tablepress.org/premium/' ) . '</strong></p>';
			}

			$message .= '<p style="margin-top:14px;">' . $this->ajax_link( array( 'action' => 'hide_message', 'item' => 'plugin_update', 'return' => 'list' ), __( 'Hide this message', 'tablepress' ) ) . '</p>';

			$title = '<em>' . sprintf( __( 'Thank you for updating to TablePress %s!', 'tablepress' ), TablePress::version ) . '</em>';

			$this->add_header_message( $message, 'notice-info not-dismissible', $title );
		}

		$this->process_action_messages( array(
			'success_delete'              => _n( 'The table was deleted successfully.', 'The tables were deleted successfully.', 1, 'tablepress' ),
			'success_delete_plural'       => _n( 'The table was deleted successfully.', 'The tables were deleted successfully.', 2, 'tablepress' ),
			'error_delete'                => __( 'Error: The table could not be deleted.', 'tablepress' ),
			'success_copy'                => _n( 'The table was copied successfully.', 'The tables were copied successfully.', 1, 'tablepress' ) . ( ( false !== $data['table_id'] ) ? ' ' . sprintf( __( 'The copied table has the table ID &#8220;%s&#8221;.', 'tablepress' ), esc_html( $data['table_id'] ) ) : '' ),
			'success_copy_plural'         => _n( 'The table was copied successfully.', 'The tables were copied successfully.', 2, 'tablepress' ),
			'error_copy'                  => __( 'Error: The table could not be copied.', 'tablepress' ),
			'error_no_table'              => __( 'Error: You did not specify a valid table ID.', 'tablepress' ),
			'error_load_table'            => __( 'Error: This table could not be loaded!', 'tablepress' ),
			'error_bulk_action_invalid'   => __( 'Error: This bulk action is invalid!', 'tablepress' ),
			'error_no_selection'          => __( 'Error: You did not select any tables!', 'tablepress' ),
			'error_delete_not_all_tables' => __( 'Notice: Not all selected tables could be deleted!', 'tablepress' ),
			'error_copy_not_all_tables'   => __( 'Notice: Not all selected tables could be copied!', 'tablepress' ),
			'success_import'              => __( 'The tables were imported successfully.', 'tablepress' ),
		) );

		$this->add_text_box( 'head', array( $this, 'textbox_head' ), 'normal' );
		$this->add_text_box( 'tables-list', array( $this, 'textbox_tables_list' ), 'normal' );

		add_screen_option( 'per_page', array( 'label' => __( 'Tables', 'tablepress' ), 'default' => 20 ) ); // Admin_Controller contains function to allow changes to this in the Screen Options to be saved.
		$this->wp_list_table = TablePress::load_class( 'TablePress_All_Tables_List_Table', 'class-all-tables-list-table.php', 'views' );
		$this->wp_list_table->set_items( $this->data['table_ids'] );
		$this->wp_list_table->prepare_items();

		// Cleanup Request URI string, which WP_List_Table uses to generate the sort URLs.
		$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'message', 'table_id' ), $_SERVER['REQUEST_URI'] );
	}

	/**
	 * Render the current view (in this view: without form tag).
	 *
	 * @since 1.0.0
	 */
	public function render() {
		?>
		<div id="tablepress-page" class="wrap">
		<?php
			$this->print_nav_tab_menu();
		?>
		<div id="tablepress-body">
		<hr class="wp-header-end" />
		<?php
		// Print all header messages.
		foreach ( $this->header_messages as $message ) {
			echo $message;
		}

		// For this screen, this is done in textbox_tables_list(), to get the fields into the correct <form>:
		// $this->do_text_boxes( 'header' );.
		?>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-<?php echo ( isset( $GLOBALS['screen_layout_columns'] ) && ( 2 === $GLOBALS['screen_layout_columns'] ) ) ? '2' : '1'; ?>">
					<div id="postbox-container-2" class="postbox-container">
						<?php
						$this->do_text_boxes( 'normal' );
						$this->do_meta_boxes( 'normal' );

						$this->do_text_boxes( 'additional' );
						$this->do_meta_boxes( 'additional' );

						// Print all submit buttons.
						$this->do_text_boxes( 'submit' );
						?>
					</div>
					<div id="postbox-container-1" class="postbox-container">
					<?php
						// Print all boxes in the sidebar.
						$this->do_text_boxes( 'side' );
						$this->do_meta_boxes( 'side' );
					?>
					</div>
				</div>
				<br class="clear" />
			</div>
		</div>
		</div>
		<?php
	}

	/**
	 * Print the screen head text.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the text box.
	 */
	public function textbox_head( array $data, array $box ) {
		echo '<p>';
		_e( 'This is a list of your tables.', 'tablepress' );
		echo ' ';
		// Show the instructions string depending on whether the Block Editor is used on the site or not.
		if ( $data['site_uses_block_editor'] ) {
			printf( __( 'To insert a table into a post or page, add a “%1$s” block in the block editor and select the desired table.', 'tablepress' ), __( 'TablePress table', 'tablepress' ) );
		} else {
			_e( 'To insert a table into a post or page, paste its Shortcode at the desired place in the editor.', 'tablepress' );
			echo ' ';
			_e( 'Each table has a unique ID that needs to be adjusted in that Shortcode.', 'tablepress' );
		}
		echo '</p>';
	}

	/**
	 * Print the content of the "All Tables" text box.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Data for this screen.
	 * @param array $box  Information about the text box.
	 */
	public function textbox_tables_list( array $data, array $box ) {
		if ( ! empty( $_GET['s'] ) ) {
			printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;', 'tablepress' ) . '</span>', esc_html( wp_unslash( $_GET['s'] ) ) );
		}
		?>
<form method="get" action="">
		<?php
		if ( isset( $_GET['page'] ) ) {
			echo '<input type="hidden" name="page" value="' . esc_attr( $_GET['page'] ) . '" />' . "\n";
		}
		$this->wp_list_table->search_box( __( 'Search Tables', 'tablepress' ), 'tables_search' );
		?>
</form>
<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<?php
		// This prints the nonce and action fields for this screen (done here instead of render(), due to moved <form>).
		$this->do_text_boxes( 'header' );
		$this->wp_list_table->display();
		?>
</form>
		<?php
	}

	/**
	 * Create HTML code for an AJAXified link.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $params Parameters for the URL.
	 * @param string $text   Text for the link.
	 * @return string HTML code for the link.
	 */
	protected function ajax_link( array $params, $text ) {
		$class = 'ajax-link';
		if ( ! empty( $params['class'] ) ) {
			$class .= ' ' . esc_attr( $params['class'] );
		}
		$url = TablePress::url( $params, true, 'admin-post.php' );
		$action = esc_attr( $params['action'] );
		$item = esc_attr( $params['item'] );
		$target = isset( $params['target'] ) ? esc_attr( $params['target'] ) : '';
		return "<a class=\"{$class}\" href=\"{$url}\" data-action=\"{$action}\" data-item=\"{$item}\" data-target=\"{$target}\">{$text}</a>";
	}

} // class TablePress_List_View
