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
	 */
	protected \TablePress_All_Tables_List_Table $wp_list_table;

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

		$this->admin_page->enqueue_script( 'list' );

		if ( $data['messages']['superseded_extensions'] ) {
			$superseded_extensions = array(
				'tablepress-advanced-access-rights/tablepress-advanced-access-rights.php' => array(
					'name'       => 'TablePress Extension: Advanced Access Rights',
					'compatible' => true,
				),
				'tablepress-auto-export-tables/tablepress-auto-export-tables.php' => array(
					'name'       => 'TablePress Extension: Automatic Table Export',
					'compatible' => true,
				),
				'tablepress-cell-highlighting/tablepress-cell-highlighting.php' => array(
					'name'       => 'TablePress Extension: Cell Highlighting',
					'compatible' => true,
				),
				'tablepress-datatables-alphabetsearch/tablepress-datatables-alphabetsearch.php' => array(
					'name'       => 'TablePress Extension: DataTables AlphabetSearch',
					'compatible' => false,
				),
				'tablepress-datatables-buttons/tablepress-datatables-buttons.php' => array(
					'name'       => 'TablePress Extension: DataTables Buttons',
					'compatible' => true,
				),
				'tablepress-datatables-column-filter-widgets/tablepress-datatables-column-filter-widgets.php' => array(
					'name'       => 'TablePress Extension: DataTables ColumnFilterWidgets',
					'compatible' => false,
				),
				'tablepress-datatables-columnfilter/tablepress-datatables-columnfilter.php' => array(
					'name'       => 'TablePress Extension: DataTables Column Filter',
					'compatible' => false,
				),
				'tablepress-datatables-counter-column/tablepress-datatables-counter-column.php' => array(
					'name'       => 'TablePress Extension: DataTables Counter Column',
					'compatible' => true,
				),
				'tablepress-datatables-fixedcolumns/tablepress-datatables-fixedcolumns.php' => array(
					'name'       => 'TablePress Extension: DataTables FixedColumns',
					'compatible' => false,
				),
				'tablepress-datatables-fixedheader/tablepress-datatables-fixedheader.php' => array(
					'name'       => 'TablePress Extension: DataTables FixedHeader',
					'compatible' => true,
				),
				'tablepress-datatables-inverted-filter/tablepress-datatables-inverted-filter.php' => array(
					'name'       => 'TablePress Extension: DataTables Inverted Filter',
					'compatible' => false,
				),
				'tablepress-datatables-row-details/tablepress-datatables-row-details.php' => array(
					'name'       => 'TablePress Extension: DataTables Row Details',
					'compatible' => false,
				),
				'tablepress-datatables-rowgroup/tablepress-datatables-rowgroup.php' => array(
					'name'       => 'TablePress Extension: DataTables RowGroup',
					'compatible' => false,
				),
				'tablepress-responsive-tables/tablepress-responsive-tables.php' => array(
					'name'       => 'TablePress Extension: Responsive Tables',
					'compatible' => false,
				),
				'tablepress-row-filter/tablepress-row-filter.php' => array(
					'name'       => 'TablePress Extension: Row Filtering',
					'compatible' => true,
				),
				'tablepress-row-highlighting/tablepress-row-highlighting.php' => array(
					'name'       => 'TablePress Extension: Row Highlighting',
					'compatible' => true,
				),
				'tablepress-table-auto-update/tablepress-table-auto-update.php' => array(
					'name'       => 'TablePress Extension: Table Auto Update',
					'compatible' => true,
				),
				'tablepress-table-column-order/tablepress-table-column-order.php' => array(
					'name'       => 'TablePress Extension: Table Column Order',
					'compatible' => true,
				),
			);

			$active_compatible_superseded_extensions = array();
			$active_incompatible_superseded_extensions = array();
			foreach ( $superseded_extensions as $plugin => $extension ) {
				if ( is_plugin_active( $plugin ) ) {
					if ( $extension['compatible'] ) {
						$active_compatible_superseded_extensions[] = $extension['name'];
					} else {
						$active_incompatible_superseded_extensions[] = $extension['name'];
					}
				}
			}

			/*
			 * If no superseded extensions are used, hide the message, to prevent running the checks on every load of the view.
			 * This leaves a small risk of an Extension being activated later, but that's acceptable as they are no longer publicly available.
			 */
			if ( empty( $active_incompatible_superseded_extensions ) && ( empty( $active_compatible_superseded_extensions ) || ! tb_tp_fs()->is_free_plan() ) ) {
				TablePress::$model_options->update( 'message_superseded_extensions', false );
			}

			$message = '';

			$notice_css_classes = 'not-dismissible';

			if ( ! empty( $active_incompatible_superseded_extensions ) ) {
				$notice_css_classes .= ' is-error';

				$message .= '<p style="font-size:16px;">' . __( 'You are using <strong>TablePress Extension plugins</strong> on this website that have been retired more than 2 years ago.', 'tablepress' ) . '<br>' . __( 'For technical reasons, some or all features of these outdated plugins <strong>do no longer work with TablePress 3</strong>:', 'tablepress' ) . '</p>';
				$message .= '<ul style="list-style:disc;margin:0.5em 1em;font-size:16px;">';
				foreach ( $active_incompatible_superseded_extensions as $extension ) {
					$message .= '<li>' . esc_html( $extension ) . '</li>';
				}
				$message .= '</ul>';
				$message .= '<p style="font-size:16px;"><strong>' . __( 'Keeping them activated can lead to errors on your website.', 'tablepress' ) . '</strong></p>';

				if ( tb_tp_fs()->is_free_plan() ) {
					$message .= '<p style="font-size:16px;"><strong>' . sprintf( __( 'To continue using these features, <a href="%s">upgrade to a TablePress Premium license plan</a>!', 'tablepress' ), 'https://tablepress.org/upgrade-extensions/?utm_source=plugin&utm_medium=textlink&utm_content=superseded-extensions-message' ) . '</strong><br>'
					. __( 'TablePress Pro and TablePress Max come with updated and heavily improved versions of these features and include direct priority email support.', 'tablepress' ) . '</p>';
				}

				if ( ! empty( $active_compatible_superseded_extensions ) && tb_tp_fs()->is_free_plan() ) {
					$message .= '<p style="margin-top:1em;font-size:14px;">' . __( 'In addition, these TablePress Extension plugins have been retired and might become incompatible in the future as well:', 'tablepress' ) . '</strong></p>';
					$message .= '<ul style="list-style:disc;margin:0.5em 1em;font-size:14px;">';
					foreach ( $active_compatible_superseded_extensions as $extension ) {
						$message .= '<li>' . esc_html( $extension ) . '</li>';
					}
					$message .= '</ul>';
				}
			} elseif ( ! empty( $active_compatible_superseded_extensions ) && tb_tp_fs()->is_free_plan() ) {
				$notice_css_classes .= ' is-warning';

				$message .= '<p style="font-size:14px;"><strong>' . __( 'You are using TablePress Extension plugins on this website that have been retired and will no longer receive updates or support:', 'tablepress' ) . '</strong></p>';
				$message .= '<ul style="list-style:disc;margin:0.5em 1em;">';
				foreach ( $active_compatible_superseded_extensions as $extension ) {
					$message .= '<li>' . esc_html( $extension ) . '</li>';
				}
				$message .= '</ul>';
				$message .= '<p style="font-size:14px;"><strong>' . __( 'It is possible that these become incompatible with TablePress or WordPress in the future!', 'tablepress' ) . '</strong></p>';
				$message .= '<p style="margin-top:1em;font-size:14px;">' . sprintf( __( 'However, their features were heavily improved and are now part of the up-to-date <a href="%s">TablePress Premium versions</a>!', 'tablepress' ), 'https://tablepress.org/upgrade-extensions/?utm_source=plugin&utm_medium=textlink&utm_content=superseded-extensions-message' ) . '</p>';
			}

			if ( '' !== $message ) {
				if ( tb_tp_fs()->is_free_plan() ) {
					$message .= '<p style="font-size:16px;margin-top:1.5em;"><strong>' . __( 'Upgrade to a TablePress Premium license plan now and get:', 'tablepress' ) . '</strong></p>';
					$message .= '<ul style="list-style:disc;margin:0.5em 1em;font-size:16px;">';
					$message .= '<li>' . __( 'Updated and heavily improved versions of these features!', 'tablepress' ) . '</li>';
					$message .= '<li>' . __( 'Direct integration into the user interface!', 'tablepress' ) . '</li>';
					$message .= '<li>' . __( 'Regular updates that ensure compatibility with WordPress!', 'tablepress' ) . '</li>';
					$message .= '<li>' . __( 'Priority email support!', 'tablepress' ) . '</li>';
					$message .= '</ul>';
					$message .= '<p style="font-size:16px;"><strong>' . sprintf( __( 'And the best: %s', 'tablepress' ), sprintf( __( 'Use the promo code %1$s during the checkout process for a special offer!', 'tablepress' ), '<code>UPGRADE</code>' ) ) . '</strong></p>';
					$message .= '<p style="margin-top:2em;">' . sprintf( '<a href="%s" class="components-button is-primary" style="font-size:14px;margin-right:3em;background:linear-gradient(135deg,#00d184 0%%,#0791e3 100%%);border:none;font-weight:bold;">%s<span class="dashicons dashicons-arrow-right-alt" style="vertical-align:middle;margin:0 0 4px 4px"></span></a>', 'https://tablepress.org/upgrade-extensions/?utm_source=plugin&utm_medium=button&utm_content=superseded-extensions-message', __( 'Upgrade to a TablePress Premium version now!', 'tablepress' ) );
				}
				$message .= $this->ajax_link( array( 'action' => 'hide_message', 'item' => 'superseded_extensions', 'return' => 'list' ), __( 'Hide this message', 'tablepress' ) ) . '</p>';

				$title = '<em>' . __( 'Important Notice!', 'tablepress' ) . '</em>';

				$this->add_header_message( $message, $notice_css_classes, $title );
			}
		}

		if ( $data['messages']['first_visit'] ) {
			$message = '<p style="font-size:14px;"><strong>' . __( 'Thank you for choosing TablePress, the most popular table plugin for WordPress!', 'tablepress' ) . '</strong></p>';
			/* translators: %1$s: URL to FAQ, %2$s: URL to Documentation, %3$s: URL to Support, %4$s: URL to plugin website */
			$message .= '<p>' . sprintf( __( 'If you encounter any questions or problems, please visit the <a href="%1$s">FAQ</a>, the <a href="%2$s">Documentation</a>, and the <a href="%3$s">Support</a> section on the <a href="%4$s">plugin website</a>.', 'tablepress' ), 'https://tablepress.org/faq/', 'https://tablepress.org/documentation/', 'https://tablepress.org/support/', 'https://tablepress.org/' ) . '</p>';

			if ( tb_tp_fs()->is_free_plan() ) {
				$message .= '<p style="font-size:14px;"><strong>' . sprintf( __( 'More great features for you and your site’s visitors and priority email support are available with a Premium license plan of TablePress. <a href="%s">Go check them out!</a>', 'tablepress' ), 'https://tablepress.org/premium/?utm_source=plugin&utm_medium=textlink&utm_content=first-visit-message' ) . '</strong></p>';
			}

			$message .= '<p style="margin-top:1em;">' . $this->ajax_link( array( 'action' => 'hide_message', 'item' => 'first_visit', 'return' => 'list' ), __( 'Hide this message', 'tablepress' ) ) . '</p>';

			$title = '<em>' . __( 'Welcome!', 'tablepress' ) . '</em>';

			$this->add_header_message( $message, 'is-info not-dismissible', $title );
		}

		if ( $data['messages']['donation_nag'] ) {
			$message = '<p style="font-size:14px;"><img alt="' . esc_attr__( 'Tobias Bäthge, developer of TablePress', 'tablepress' ) . '" src="https://secure.gravatar.com/avatar/50f1cff2e27a1f522b18ce229c057bc5?s=300" height="150" width="150" style="float:left;margin:2px 15px 30px 0;">'
				. __( 'Hi, my name is Tobias, I&#8217;m the developer of the TablePress plugin.', 'tablepress' ) . '</p>';
			$message .= '<p style="font-size:14px;">' . __( 'Thank you for using it!', 'tablepress' ) . ' ';
			if ( $data['table_count'] > 0 ) {
				/* translators: %s: Number of tables */
				$message .= sprintf( _n( 'I hope that everything works and that you are satisfied with the results of managing your %s table.', 'I hope that everything works and that you are satisfied with the results of managing your %s tables.', $data['table_count'], 'tablepress' ), $data['table_count'] );
			} else {
				/* translators: %s: URL to TablePress website */
				$message .= sprintf( __( 'It looks like you haven’t added a table yet. If you need help to get started, please find more information in the FAQ and Documentation on the <a href="%s">TablePress website</a>.', 'tablepress' ), 'https://tablepress.org/' );
			}
			$message .= '</p>';
			$message .= '<p style="font-size:14px;"><strong>' . sprintf( __( 'I would like to invite you to check out the <a href="%s">Premium versions of TablePress</a>.', 'tablepress' ), 'https://tablepress.org/premium/?utm_source=plugin&utm_medium=textlink&utm_content=upgrade-message' ) . '<br>'
				. __( 'The available Pro and Max plans offer user support and many exciting and helpful features for your tables.', 'tablepress' ) . '</strong></p>';
			$message .= '<p style="font-size:14px;">' . __( 'Sincerely, Tobias', 'tablepress' ) . '</p>';
			$message .= '<p style="margin-top:1em;">' . sprintf( '<a href="%s" class="components-button is-primary" style="font-size:14px;margin-right:3em;">%s<span class="dashicons dashicons-arrow-right-alt" style="vertical-align:middle;margin:0 0 4px 4px"></span></a>', 'https://tablepress.org/premium/?utm_source=plugin&utm_medium=button&utm_content=upgrade-message', __( 'Tell me more about the Premium features', 'tablepress' ) )
				. $this->ajax_link( array( 'action' => 'hide_message', 'item' => 'donation_nag', 'return' => 'list' ), __( 'Hide this message', 'tablepress' ) ) . '</p>';

			$title = '<em>' . __( 'TablePress has more to offer!', 'tablepress' ) . '</em>';

			$this->add_header_message( $message, 'is-success not-dismissible', $title );
		}

		if ( $data['messages']['plugin_update'] ) {
			$message = '<p>' . sprintf( __( 'To find out more about what’s new, please read the <a href="%s"><strong>release announcement</strong></a>.', 'tablepress' ), 'https://tablepress.org/news/?utm_source=plugin&utm_medium=textlink&utm_content=plugin-update-message' ) . '</p>';

			if ( tb_tp_fs()->is_free_plan() ) {
				$message .= '<p><strong>' . sprintf( __( 'More great features and priority email support are available with a Premium license plan. <a href="%s">Check them out!</a>', 'tablepress' ), 'https://tablepress.org/premium/?utm_source=plugin&utm_medium=textlink&utm_content=plugin-update-message' ) . '</strong></p>';
			}

			$message .= '<p style="margin-top:1em;">' . $this->ajax_link( array( 'action' => 'hide_message', 'item' => 'plugin_update', 'return' => 'list' ), __( 'Hide this message', 'tablepress' ) ) . '</p>';

			$title = '<em>' . sprintf( __( 'Thank you for updating to TablePress %s!', 'tablepress' ), TablePress::version ) . '</em>';

			$this->add_header_message( $message, 'is-info not-dismissible', $title );
		}

		$this->process_action_messages( array(
			'success_delete'              => _n( 'The table was deleted successfully.', 'The tables were deleted successfully.', 1, 'tablepress' ),
			'success_delete_plural'       => _n( 'The table was deleted successfully.', 'The tables were deleted successfully.', 2, 'tablepress' ),
			'error_delete'                => __( 'Error: The table could not be deleted.', 'tablepress' ),
			'success_copy'                => _n( 'The table was copied successfully.', 'The tables were copied successfully.', 1, 'tablepress' )
				. ( ( false !== $data['table_id'] )
					? ' ' . ( current_user_can( 'tablepress_edit_table', $data['table_id'] )
						? sprintf( __( 'You can now <a href="%1$s">edit the copied table</a>, which has the table ID “%2$s”.', 'tablepress' ), esc_url( TablePress::url( array( 'action' => 'edit', 'table_id' => $data['table_id'] ) ) ), $data['table_id'] )
						: sprintf( __( 'The copied table has the table ID &#8220;%s&#8221;.', 'tablepress' ), esc_html( $data['table_id'] ) ) )
					: ''
				),
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
	#[\Override]
	public function render(): void {
		?>
		<div id="tablepress-page" class="wrap">
		<?php
			$this->print_nav_tab_menu();
		?>
		<div id="tablepress-body">
		<hr class="wp-header-end">
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
				<br class="clear">
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
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_head( array $data, array $box ): void {
		echo '<p>';
		_e( 'This is a list of your tables.', 'tablepress' );
		echo ' ';
		// Show the instructions string depending on whether the Block Editor is used on the site or not.
		if ( 'block' === $data['site_used_editor'] ) {
			printf( __( 'To insert a table into a post or page, add a “%1$s” block in the block editor and select the desired table.', 'tablepress' ), __( 'TablePress table', 'tablepress' ) );
		} elseif ( 'elementor' === $data['site_used_editor'] ) {
			printf( __( 'To insert a table into a post or page, add a “%1$s” widget in the Elementor editor and select the desired table.', 'tablepress' ), __( 'TablePress table', 'tablepress' ) );
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
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_tables_list( array $data, array $box ): void {
		if ( ! empty( $_GET['s'] ) ) {
			printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;', 'tablepress' ) . '</span>', esc_html( wp_unslash( $_GET['s'] ) ) );
		}
		?>
<form method="get">
		<?php
		if ( isset( $_GET['page'] ) ) {
			echo '<input type="hidden" name="page" value="' . esc_attr( $_GET['page'] ) . '">' . "\n";
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
<div id="tablepress-list-screen"></div>
		<?php
	}

	/**
	 * Create HTML code for an AJAXified link.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $params Parameters for the URL.
	 * @param string               $text   Text for the link.
	 * @return string HTML code for the link.
	 */
	protected function ajax_link( array $params, string $text ): string {
		$class = 'ajax-link';
		if ( ! empty( $params['class'] ) ) {
			$class .= ' ' . esc_attr( $params['class'] );
		}
		$url = TablePress::url( $params, true, 'admin-post.php' );
		$action = esc_attr( $params['action'] );
		$item = esc_attr( $params['item'] );
		$target = esc_attr( $params['target'] ?? '' );
		return "<a class=\"{$class}\" href=\"{$url}\" data-action=\"{$action}\" data-item=\"{$item}\" data-target=\"{$target}\">{$text}</a>";
	}

} // class TablePress_List_View
