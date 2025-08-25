<?php
/**
 * Admin Controller for TablePress with the functionality for the non-AJAX backend
 *
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Admin Controller class, extends Base Controller Class
 *
 * @package TablePress
 * @subpackage Controllers
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Admin_Controller extends TablePress_Controller {

	/**
	 * Page hooks (i.e. names) WordPress uses for the TablePress admin screens,
	 * populated in add_admin_menu_entry().
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	protected array $page_hooks = array();

	/**
	 * Actions that have a view and admin menu or nav tab menu entry.
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, bool|string>>
	 */
	protected array $view_actions = array();

	/**
	 * Instance of the TablePress Admin View that is rendered.
	 *
	 * @since 1.0.0
	 */
	protected \TablePress_View $view;

	/**
	 * Initialize the Admin Controller, determine location the admin menu, set up actions.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		// Handler for changing the number of shown tables in the list of tables (via WP List Table class).
		add_filter( 'set_screen_option_tablepress_list_per_page', array( $this, 'save_list_tables_screen_option' ), 10, 3 );

		add_action( 'admin_menu', array( $this, 'add_admin_menu_entry' ) );
		add_action( 'admin_init', array( $this, 'add_admin_actions' ) );

		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
	}

	/**
	 * Handler for changing the number of shown tables in the list of tables (via WP List Table class).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed  $screen_option Current value of the filter (probably bool false).
	 * @param string $option        Option in which the setting is stored.
	 * @param int    $value         Current value of the setting.
	 * @return int Changed value of the setting
	 */
	public function save_list_tables_screen_option( /* mixed */ $screen_option, string $option, int $value ): int {
		return $value;
	}

	/**
	 * Add admin screens to the correct place in the admin menu.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu_entry(): void {
		// Callback for all menu entries.
		$callback = array( $this, 'show_admin_page' );
		/**
		 * Filters the TablePress admin menu entry name.
		 *
		 * @since 1.0.0
		 *
		 * @param string $entry_name The admin menu entry name. Default "TablePress".
		 */
		$admin_menu_entry_name = apply_filters( 'tablepress_admin_menu_entry_name', 'TablePress' );

		$this->init_view_actions();
		$min_access_cap = $this->view_actions['list']['required_cap'];

		if ( TablePress::$controller->is_top_level_page ) {
			$icon_url = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iLTMyIC0zMiA2NCA2NCIgZmlsbD0iI2ZmZiI+PHBhdGggZD0iTTAtMjUuODU0aC0yNS44NTR2NTEuNzA4aDUxLjcwOFYwSDIxdjIxaC00MnYtNDJIMFoiLz48cGF0aCBkPSJNLTE4LTE4aDEwdjEwaC0xMHpNLTE4LTVoMTBWNWgtMTB6TS01LTVINVY1SC01ek0tMTggOGgxMHYxMGgtMTB6TS01IDhINXYxMEgtNXpNOCA4aDEwdjEwSDh6TTUtMzFoNi4xOHY2LjE4SDV6TTE5LTI1aDYuMTh2Ni4xOEgxOXpNMC0xNWgzLjgydjMuODJIMHpNMTAtMjBoMy44MnYzLjgySDEwek0yNS0xMmgzLjgydjMuODJIMjV6TTgtMTNoMTB2MTBIOHoiLz48L3N2Zz4=';
			switch ( TablePress::$controller->parent_page ) {
				case 'top':
					$position = 3; // Position of Dashboard + 1.
					break;
				case 'bottom':
					$position = isset( $GLOBALS['_wp_last_utility_menu'] ) ? ++$GLOBALS['_wp_last_utility_menu'] : 80;
					break;
				case 'middle':
				default:
					$position = isset( $GLOBALS['_wp_last_object_menu'] ) ? ++$GLOBALS['_wp_last_object_menu'] : 25;
					break;
			}
			// Prevent overwriting existing menu entries.
			while ( isset( $GLOBALS['menu'][ $position ] ) ) {
				++$position;
			}
			add_menu_page( 'TablePress', $admin_menu_entry_name, $min_access_cap, 'tablepress', $callback, $icon_url, $position ); // @phpstan-ignore argument.type
			foreach ( $this->view_actions as $action => $entry ) {
				if ( ! $entry['show_entry'] ) {
					continue;
				}
				$slug = 'tablepress';
				if ( 'list' !== $action ) {
					$slug .= '_' . $action;
				}
				/* translators: %1$s: Page title, %2$s: Plugin name (TablePress) */
				$page_hook = add_submenu_page( 'tablepress', sprintf( __( '%1$s &lsaquo; %2$s', 'tablepress' ), $entry['page_title'], 'TablePress' ), $entry['admin_menu_title'], $entry['required_cap'], $slug, $callback ); // @phpstan-ignore argument.type, argument.type
				if ( false !== $page_hook ) {
					$this->page_hooks[] = $page_hook;
				}
			}
		} else {
			// @phpstan-ignore argument.type
			$page_hook = add_submenu_page( TablePress::$controller->parent_page, 'TablePress', $admin_menu_entry_name, $min_access_cap, 'tablepress', $callback );
			if ( false !== $page_hook ) {
				$this->page_hooks[] = $page_hook;
			}
		}
	}

	/**
	 * Set up handlers for user actions in the backend that exceed plain viewing.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_actions(): void {
		// Register the callbacks for processing action requests.
		$post_actions = array( 'list', 'add', 'options', 'export', 'import' );
		$get_actions = array( 'hide_message', 'delete_table', 'copy_table', 'preview_table', 'editor_button_thickbox', 'uninstall_tablepress' );
		foreach ( $post_actions as $action ) {
			add_action( "admin_post_tablepress_{$action}", array( $this, "handle_post_action_{$action}" ) );
		}
		foreach ( $get_actions as $action ) {
			add_action( "admin_post_tablepress_{$action}", array( $this, "handle_get_action_{$action}" ) );
		}

		// Register callbacks to trigger load behavior for admin pages.
		foreach ( $this->page_hooks as $page_hook ) {
			add_action( "load-{$page_hook}", array( $this, 'load_admin_page' ) );
		}

		/**
		 * Filters whether the legacy editor button should be loaded on the post editing screen.
		 *
		 * @since 2.1.0
		 *
		 * @param bool $load_button Whether to load the legacy editor button. Default true.
		 */
		if ( apply_filters( 'tablepress_add_legacy_editor_button', true ) ) {
			$pages_with_editor_button = array( 'post.php', 'post-new.php' );
			foreach ( $pages_with_editor_button as $editor_page ) {
				add_action( "load-{$editor_page}", array( $this, 'add_editor_buttons' ) );
			}
		}

		if ( ! is_network_admin() && ! is_user_admin() ) {
			add_action( 'admin_bar_menu', array( $this, 'add_wp_admin_bar_new_content_menu_entry' ), 71 );
		}

		add_action( 'load-plugins.php', array( $this, 'plugins_page' ) );
	}

	/**
	 * Loads additional JavaScript code for the TablePress table block (in the block editor context).
	 *
	 * @since 2.2.0
	 */
	public function enqueue_block_editor_assets(): void {
		/*
		 * Register the `react-jsx-runtime` polyfill, if it is not already registered.
		 * This is needed as a polyfill for WP < 6.6, and can be removed once WP 6.6 is the minimum requirement for TablePress.
		 */
		if ( ! wp_script_is( 'react-jsx-runtime', 'registered' ) ) {
			wp_register_script( 'react-jsx-runtime', plugins_url( 'admin/js/react-jsx-runtime.min.js', TABLEPRESS__FILE__ ), array( 'react' ), TablePress::version, true );
		}

		// Add table information for the block editor to the page.
		$handle = generate_block_asset_handle( 'tablepress/table', 'editorScript' );
		$data = $this->get_block_editor_data();
		wp_add_inline_script( $handle, $data, 'before' );
	}

	/**
	 * Loads additional CSS code for the TablePress table block (inside the block editor iframe).
	 *
	 * @since 2.2.0
	 */
	public function enqueue_block_assets(): void {
		// Load the TablePress default CSS and the user's "Custom CSS" in the block editor iframe.
		if ( is_admin() ) {
			TablePress::$controller->maybe_enqueue_css();
		}
	}

	/**
	 * Gets the inline data that is referenced by the Block Editor JavaScript code for the TablePress blocks.
	 *
	 * @since 2.0.0
	 *
	 * @return string JavaScript code for the Block Editor.
	 */
	protected function get_block_editor_data(): string {
		$tables = array();
		// Load all table IDs without priming the post meta cache, as table options/visibility are not needed.
		$table_ids = TablePress::$model_table->load_all( false );
		foreach ( $table_ids as $table_id ) {
			// Load table, without table data, options, and visibility settings.
			$table = TablePress::$model_table->load( $table_id, false, false );

			// Skip tables that could not be loaded.
			if ( is_wp_error( $table ) ) {
				continue;
			}

			if ( '' === trim( $table['name'] ) ) {
				$table['name'] = __( '(no name)', 'tablepress' );
			}
			$tables[ $table_id ] = esc_html( $table['name'] );
		}

		/**
		 * Filters the list of table IDs and names that is passed to the block editor, and is then used in the dropdown of the TablePress table block.
		 *
		 * @since 2.0.0
		 *
		 * @param array<string, string> $tables List of table names, the table ID is the array key.
		 */
		$tables = apply_filters( 'tablepress_block_editor_tables_list', $tables );

		$tables = wp_json_encode( $tables, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES );
		if ( false === $tables ) {
			// JSON encoding failed, return an error object. Use a prefixed "_error" key to avoid conflicts with intentionally added "error" keys.
			$tables = '{ "_error": "The data could not be encoded to JSON!" }';
		}
		// Print the JSON data inside a `JSON.parse()` call in JS for speed gains, with necessary escaping of `\` and `'`.
		$tables = str_replace( array( '\\', "'" ), array( '\\\\', "\'" ), $tables );

		$shortcode = esc_js( TablePress::$shortcode );

		$template = TablePress::$model_table->get_table_template();
		$template = wp_json_encode( $template['options'], JSON_HEX_TAG | JSON_UNESCAPED_SLASHES );
		if ( false === $template ) {
			// JSON encoding failed, return an error object. Use a prefixed "_error" key to avoid conflicts with intentionally added "error" keys.
			$template = '{ "_error": "The data could not be encoded to JSON!" }';
		}
		// Print the JSON data inside a `JSON.parse()` call in JS for speed gains, with necessary escaping of `\` and `'`.
		$template = str_replace( array( '\\', "'" ), array( '\\\\', "\'" ), $template );

		/**
		 * Filters whether the table block preview should be loaded via a <ServerSideRender> in the block editor.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $load_block_preview Whether the table block preview should be loaded.
		 */
		$load_block_preview = apply_filters( 'tablepress_show_block_editor_preview', true );
		$load_block_preview = (bool) $load_block_preview ? 'true' : 'false';

		$url = '';
		if ( current_user_can( 'tablepress_list_tables' ) ) {
			$url = TablePress::url( array( 'action' => 'list' ) );
		}

		return <<<JS
			// Ensure the global `tp` object exists.
			window.tp = window.tp || {};
			tp.url = '{$url}';
			tp.load_block_preview = {$load_block_preview};
			tp.table = {};
			tp.table.shortcode = '{$shortcode}';
			tp.table.template = JSON.parse( '{$template}' );
			tp.tables = JSON.parse( '{$tables}' );
			JS;
	}

	/**
	 * Register actions to add "Table" button to "HTML editor" and "Visual editor" toolbars.
	 *
	 * @since 1.0.0
	 */
	public function add_editor_buttons(): void {
		if ( ! current_user_can( 'tablepress_list_tables' ) ) {
			return;
		}

		// Only load the toolbar integration if the Block Editor is not used.
		if ( 'block' === TablePress::site_used_editor() ) {
			return;
		}

		add_thickbox(); // The files are usually already loaded by media upload functions.
		$admin_page = TablePress::load_class( 'TablePress_Admin_Page', 'class-admin-page-helper.php', 'classes' );
		$admin_page->enqueue_script(
			'quicktags-button',
			array( 'quicktags', 'media-upload' ),
			array(
				'editor_button' => array(
					'caption'        => __( 'Table', 'tablepress' ),
					'title'          => __( 'Insert a TablePress table', 'tablepress' ),
					'thickbox_title' => __( 'Insert a TablePress table', 'tablepress' ),
					'thickbox_url'   => TablePress::url( array( 'action' => 'editor_button_thickbox' ), true, 'admin-post.php' ),
				),
			),
		);

		// TinyMCE integration.
		if ( user_can_richedit() ) {
			add_filter( 'mce_external_plugins', array( $this, 'add_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( $this, 'add_tinymce_button' ) );
		}
	}

	/**
	 * Adds the "Table" button to the TinyMCE toolbar.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $buttons Current set of buttons in the TinyMCE toolbar.
	 * @return string[] Extended set of buttons in the TinyMCE toolbar, including the "Table" button.
	 */
	public function add_tinymce_button( array $buttons ): array {
		$buttons[] = 'tablepress_insert_table';
		return $buttons;
	}

	/**
	 * Registers the "Table" button plugin for the TinyMCE editor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $plugins Current set of registered TinyMCE plugins.
	 * @return array<string, string> Extended set of registered TinyMCE plugins, including the "Table" button plugin.
	 */
	public function add_tinymce_plugin( array $plugins ): array {
		$plugins['tablepress_tinymce'] = plugins_url( 'admin/js/build/tinymce-button.js', TABLEPRESS__FILE__ );
		return $plugins;
	}

	/**
	 * Add "TablePress Table" entry to "New" dropdown menu in the WP Admin Bar.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The current WP Admin Bar object.
	 */
	public function add_wp_admin_bar_new_content_menu_entry( WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! current_user_can( 'tablepress_add_tables' ) ) {
			return;
		}

		// Don't load TablePress assets on the Freemius opt-in/activation screen.
		if ( tb_tp_fs()->is_activation_mode() && tb_tp_fs()->is_activation_page() ) {
			return;
		}

		$wp_admin_bar->add_menu( array(
			'parent' => 'new-content',
			'id'     => 'new-tablepress-table',
			'title'  => __( 'TablePress table', 'tablepress' ),
			'href'   => TablePress::url( array( 'action' => 'add' ) ),
		) );
	}

	/**
	 * Handle actions for loading of Plugins page.
	 *
	 * @since 1.0.0
	 */
	public function plugins_page(): void {
		// Add additional links on Plugins page.
		add_filter( 'plugin_action_links_' . TABLEPRESS_BASENAME, array( $this, 'add_plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_plugin_row_meta' ), 10, 2 );
		$incompatible_superseded_extensions = array(
			'tablepress-datatables-alphabetsearch/tablepress-datatables-alphabetsearch.php',
			'tablepress-datatables-column-filter-widgets/tablepress-datatables-column-filter-widgets.php',
			'tablepress-datatables-columnfilter/tablepress-datatables-columnfilter.php',
			'tablepress-datatables-fixedcolumns/tablepress-datatables-fixedcolumns.php',
			'tablepress-datatables-inverted-filter/tablepress-datatables-inverted-filter.php',
			'tablepress-datatables-row-details/tablepress-datatables-row-details.php',
			'tablepress-datatables-rowgroup/tablepress-datatables-rowgroup.php',
			'tablepress-responsive-tables/tablepress-responsive-tables.php',
		);
		foreach ( $incompatible_superseded_extensions as $plugin_file ) {
			add_action( "after_plugin_row_{$plugin_file}", array( $this, 'add_superseded_extension_meta_row' ), 10, 3 );
		}
	}

	/**
	 * Add links to the TablePress entry in the "Plugin" column on the Plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $links List of links to print in the "Plugin" column on the Plugins page.
	 * @return string[] Extended list of links to print in the "Plugin" column on the Plugins page.
	 */
	public function add_plugin_action_links( array $links ): array {
		if ( current_user_can( 'tablepress_list_tables' ) ) {
			$links[] = '<a href="' . esc_url( TablePress::url() ) . '">' . __( 'Plugin page', 'tablepress' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Add links to the TablePress entry in the "Description" column on the Plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $links List of links to print in the "Description" column on the Plugins page.
	 * @param string   $file  Name of the plugin.
	 * @return string[] Extended list of links to print in the "Description" column on the Plugins page.
	 */
	public function add_plugin_row_meta( array $links, string $file ): array {
		if ( TABLEPRESS_BASENAME === $file ) {
			$links[] = '<a href="https://tablepress.org/faq/" title="' . esc_attr__( 'Frequently Asked Questions', 'tablepress' ) . '">' . __( 'FAQ', 'tablepress' ) . '</a>';
			$links[] = '<a href="https://tablepress.org/documentation/">' . __( 'Documentation', 'tablepress' ) . '</a>';
			$links[] = '<a href="https://tablepress.org/support/">' . __( 'Support', 'tablepress' ) . '</a>';
			if ( ! TABLEPRESS_IS_PLAYGROUND_PREVIEW && tb_tp_fs()->is_free_plan() ) {
				$links[] = '<a href="https://tablepress.org/premium/?utm_source=plugin&utm_medium=textlink&utm_content=plugins-screen" title="' . esc_attr__( 'Check out the Premium version of TablePress!', 'tablepress' ) . '"><strong>' . __( 'Go Premium', 'tablepress' ) . '</strong></a>';
			}
		}
		return $links;
	}

	/**
	 * Prints a superseded extension notice below certain TablePress Extension plugins' meta rows on the "Plugins" screen.
	 *
	 * @since 2.4.1
	 *
	 * @param string                           $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array<int, string|string[]|bool> $plugin_data An array of plugin data.
	 * @param string                           $status      Status filter currently applied to the plugin list.
	 */
	public function add_superseded_extension_meta_row( string $plugin_file, array $plugin_data, string $status ): void {
		if ( ! is_plugin_active( $plugin_file ) ) {
			return;
		}
		?>
		<tr class="plugin-update-tr active">
			<td colspan="<?php echo esc_attr( $GLOBALS['wp_list_table']->get_column_count() ); ?>" class="plugin-update colspanchange">
				<div class="update-message notice inline notice-error notice-alt">
					<?php
					if ( tb_tp_fs()->is_free_plan() ) {
						echo '<p style="font-size:14px;">';
						_e( 'This TablePress Extension was retired.', 'tablepress' );
						echo ' ';
						_e( '<strong>The plugin does no longer work with TablePress 3</strong> and will no longer receive updates or support!', 'tablepress' );
						echo '<br>';
						_e( 'Keeping it activated can lead to errors on your website!', 'tablepress' );
						echo ' <strong>' . sprintf( __( '<a href="%s">Find out what you can do to continue using its features!</a>', 'tablepress' ), 'https://tablepress.org/upgrade-extensions/?utm_source=plugin&utm_medium=textlink&utm_content=plugins-list-table' ) . '</strong>';
						echo '</p>';
					}
					?>
					<style>
						/* Remove the separator line between the plugin's and the notice's table row. */
						.plugins .active[data-plugin="<?php echo $plugin_file; ?>"] th,
						.plugins .active[data-plugin="<?php echo $plugin_file; ?>"] td {
							box-shadow: none;
						}
						/* Hide the plugin update row for the Extension as those won't work anymore anyways. */
						.plugins .plugin-update-tr[data-plugin="<?php echo $plugin_file; ?>"] {
							display: none;
						}
					</style>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Prepare the rendering of an admin screen, by determining the current action, loading necessary data and initializing the view.
	 *
	 * @since 1.0.0
	 */
	public function load_admin_page(): void {
		// Determine the action from either the GET parameter (for sub-menu entries, and the main admin menu entry).
		$action = ( ! empty( $_GET['action'] ) ) ? $_GET['action'] : 'list'; // Default action is list.
		if ( TablePress::$controller->is_top_level_page ) {
			// Or, for sub-menu entry of an admin menu "TablePress" entry, get it from the "page" GET parameter.
			if ( 'tablepress' !== $_GET['page'] ) {
				// Actions that are top-level entries, but don't have an action GET parameter (action is after last _ in string).
				$action = substr( $_GET['page'], 11 ); // $_GET['page'] has the format 'tablepress_{$action}'
			}
		}

		// Check if action is a supported action, and whether the user is allowed to access this screen.
		if ( ! isset( $this->view_actions[ $action ] ) || ! current_user_can( $this->view_actions[ $action ]['required_cap'] ) ) { // @phpstan-ignore argument.type (The array value for the capability is always a string.)
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'default' ), 403 );
		}

		// Don't load TablePress assets on the Freemius opt-in/activation screen.
		if ( tb_tp_fs()->is_activation_mode() && tb_tp_fs()->is_activation_page() ) {
			return;
		}

		// Changes current screen ID and pagenow variable in JS, to enable automatic meta box JS handling.
		set_current_screen( "tablepress_{$action}" );

		/*
		 * Set the `$typenow` global to the current CPT ourselves, as `WP_Screen::get()` does not determine the CPT correctly.
		 * This is necessary as the WP Admin Menu can otherwise highlight wrong entries, see https://github.com/TablePress/TablePress/issues/24.
		 */
		if ( isset( $_GET['post_type'] ) && post_type_exists( $_GET['post_type'] ) ) {
			$GLOBALS['typenow'] = $_GET['post_type']; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		// Pre-define some view data.
		$data = array(
			'view_actions'     => $this->view_actions,
			'message'          => ( ! empty( $_GET['message'] ) ) ? $_GET['message'] : false,
			'error_details'    => ( ! empty( $_GET['error_details'] ) ) ? $_GET['error_details'] : '',
			'site_used_editor' => TablePress::site_used_editor(),
		);

		// Depending on the action, load more necessary data for the corresponding view.
		switch ( $action ) {
			case 'list':
				$data['table_id'] = ( ! empty( $_GET['table_id'] ) ) ? $_GET['table_id'] : false;
				// Prime the post meta cache for cached loading of last_editor.
				$data['table_ids'] = TablePress::$model_table->load_all( true );
				$data['messages']['donation_nag'] = $this->maybe_show_donation_message();
				$data['messages']['first_visit'] = ! $data['messages']['donation_nag'] && TablePress::$model_options->get( 'message_first_visit' );
				$data['messages']['plugin_update'] = TablePress::$model_options->get( 'message_plugin_update' );
				$data['messages']['superseded_extensions'] = current_user_can( 'manage_options' ) && TablePress::$model_options->get( 'message_superseded_extensions' );
				$data['table_count'] = count( $data['table_ids'] );
				break;
			case 'about':
				$data['first_activation'] = TablePress::$model_options->get( 'first_activation' );
				break;
			case 'options':
				/*
				 * Maybe try saving "Custom CSS" to a file:
				 * (called here, as the credentials form posts to this handler again, due to how `request_filesystem_credentials()` works)
				 */
				if ( isset( $_GET['item'] ) && 'save_custom_css' === $_GET['item'] ) {
					TablePress::check_nonce( 'options', $_GET['item'] ); // Nonce check here, as we don't have an explicit handler, and even viewing the screen needs to be checked.
					$action = 'options_custom_css'; // To load a different view.
					// Try saving "Custom CSS" to a file, otherwise this gets the HTML for the credentials form.
					$tablepress_css = TablePress::load_class( 'TablePress_CSS', 'class-css.php', 'classes' );
					$result = $tablepress_css->save_custom_css_to_file_plugin_options( TablePress::$model_options->get( 'custom_css' ), TablePress::$model_options->get( 'custom_css_minified' ) );
					if ( is_string( $result ) ) {
						$data['credentials_form'] = $result; // This will only be called if the save function doesn't do a redirect.
					} elseif ( true === $result ) {
						/*
						 * At this point, saving was successful, so enable usage of CSS in files again,
						 * and also increase the "Custom CSS" version number (for cache busting).
						 */
						TablePress::$model_options->update( array(
							'use_custom_css_file' => true,
							'custom_css_version'  => TablePress::$model_options->get( 'custom_css_version' ) + 1,
						) );
						TablePress::redirect( array( 'action' => 'options', 'message' => 'success_save' ) );
					} else { // Leaves only $result === false.
						TablePress::redirect( array( 'action' => 'options', 'message' => 'success_save_error_custom_css' ) );
					}
					break;
				}
				$data['frontend_options']['use_custom_css'] = TablePress::$model_options->get( 'use_custom_css' );
				$data['frontend_options']['custom_css'] = TablePress::$model_options->get( 'custom_css' );
				$data['user_options']['parent_page'] = TablePress::$controller->parent_page;
				break;
			case 'edit':
				if ( empty( $_GET['table_id'] ) ) {
					TablePress::redirect( array( 'action' => 'list', 'message' => 'error_no_table' ) );
				}
				// Load table, with table data, options, and visibility settings.
				$data['table'] = TablePress::$model_table->load( $_GET['table_id'], true, true );
				if ( is_wp_error( $data['table'] ) ) {
					TablePress::redirect( array( 'action' => 'list', 'message' => 'error_load_table', 'error_details' => TablePress::get_wp_error_string( $data['table'] ) ) );
				}
				if ( ! current_user_can( 'tablepress_edit_table', $_GET['table_id'] ) ) {
					wp_die( __( 'Sorry, you are not allowed to access this page.', 'default' ), 403 );
				}
				break;
			case 'export':
				// Load all table IDs without priming the post meta cache, as table options/visibility are not needed.
				$table_ids = TablePress::$model_table->load_all( false );
				$data['tables'] = array();
				foreach ( $table_ids as $table_id ) {
					if ( ! current_user_can( 'tablepress_export_table', $table_id ) ) {
						continue;
					}
					// Load table, without table data, options, and visibility settings.
					$table = TablePress::$model_table->load( $table_id, false, false );

					// Skip tables that could not be loaded.
					if ( is_wp_error( $table ) ) {
						continue;
					}

					$data['tables'][ $table['id'] ] = $table['name'];
				}
				$data['tables_count'] = TablePress::$model_table->count_tables();
				$data['export_ids'] = ( ! empty( $_GET['table_id'] ) ) ? explode( ',', $_GET['table_id'] ) : array();
				$exporter = TablePress::load_class( 'TablePress_Export', 'class-export.php', 'classes' );
				$data['zip_support_available'] = $exporter->zip_support_available;
				$data['export_formats'] = $exporter->export_formats;
				$data['csv_delimiters'] = $exporter->csv_delimiters;
				$data['export_format'] = ( ! empty( $_GET['export_format'] ) ) ? $_GET['export_format'] : 'csv';
				$data['csv_delimiter'] = ( ! empty( $_GET['csv_delimiter'] ) ) ? $_GET['csv_delimiter'] : _x( ',', 'Default CSV delimiter in the translated language (";", ",", or "tab")', 'tablepress' );
				break;
			case 'import':
				// Load all table IDs without priming the post meta cache, as table options/visibility are not needed.
				$table_ids = TablePress::$model_table->load_all( false );
				$data['tables'] = array();
				foreach ( $table_ids as $table_id ) {
					if ( ! current_user_can( 'tablepress_edit_table', $table_id ) ) {
						continue;
					}
					// Load table, without table data, options, and visibility settings.
					$table = TablePress::$model_table->load( $table_id, false, false );

					// Skip tables that could not be loaded.
					if ( is_wp_error( $table ) ) {
						continue;
					}

					$data['tables'][ $table['id'] ] = $table['name'];
				}
				$data['table_ids'] = $table_ids; // Backward compatibility for the retired "Table Auto Update" Extension, which still relies on this variable name.
				$data['tables_count'] = TablePress::$model_table->count_tables();
				$importer = TablePress::load_class( 'TablePress_Import', 'class-import.php', 'classes' );
				$data['import_type'] = ( ! empty( $_GET['import_type'] ) ) ? $_GET['import_type'] : 'add';
				$data['import_existing_table'] = ( ! empty( $_GET['import_existing_table'] ) ) ? $_GET['import_existing_table'] : '';
				$data['import_source'] = ( ! empty( $_GET['import_source'] ) ) ? $_GET['import_source'] : 'file-upload';
				$data['import_url'] = ( ! empty( $_GET['import_url'] ) ) ? wp_unslash( $_GET['import_url'] ) : 'https://';
				$data['import_server'] = ( ! empty( $_GET['import_server'] ) ) ? wp_unslash( $_GET['import_server'] ) : ABSPATH;
				$data['import_form-field'] = ( ! empty( $_GET['import_form-field'] ) ) ? wp_unslash( $_GET['import_form-field'] ) : '';
				$data['legacy_import'] = ( ! empty( $_GET['legacy_import'] ) ) ? $_GET['legacy_import'] : 'false';
				break;
		}

		/**
		 * Filters the data that is passed to the current TablePress View.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $data   Data for the view.
		 * @param string               $action The current action for the view.
		 */
		$data = apply_filters( 'tablepress_view_data', $data, $action );

		// Prepare and initialize the view.
		$this->view = TablePress::load_view( $action, $data );
	}

	/**
	 * Render the view that has been initialized in load_admin_page() (called by WordPress when the actual page content is needed).
	 *
	 * @since 1.0.0
	 */
	public function show_admin_page(): void {
		$this->view->render();
	}

	/**
	 * Decides whether a message about Premium versions (previously, about donations) shall be shown on the "All Tables" screen, depending on passed days since installation and whether it was shown before.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the message shall be shown on the "All Tables" screen.
	 */
	protected function maybe_show_donation_message(): bool {
		// Only show the message to plugin admins.
		if ( ! current_user_can( 'tablepress_edit_options' ) ) {
			return false;
		}

		if ( ! TablePress::$model_options->get( 'message_donation_nag' ) ) {
			return false;
		}

		// Determine, how long has the plugin been installed.
		$seconds_installed = time() - TablePress::$model_options->get( 'first_activation' );
		return ( $seconds_installed > MONTH_IN_SECONDS / 2 );
	}

	/**
	 * Init list of actions that have a view with their titles/names/caps.
	 *
	 * @since 1.0.0
	 */
	protected function init_view_actions(): void {
		$this->view_actions = array(
			'list'    => array(
				'show_entry'       => true,
				'page_title'       => __( 'All Tables', 'tablepress' ),
				'admin_menu_title' => __( 'All Tables', 'tablepress' ),
				'nav_tab_title'    => __( 'All Tables', 'tablepress' ),
				'required_cap'     => 'tablepress_list_tables',
			),
			'add'     => array(
				'show_entry'       => true,
				'page_title'       => __( 'Add New Table', 'tablepress' ),
				'admin_menu_title' => __( 'Add New Table', 'tablepress' ),
				'nav_tab_title'    => __( 'Add New', 'tablepress' ),
				'required_cap'     => 'tablepress_add_tables',
			),
			'edit'    => array(
				'show_entry'       => false,
				'page_title'       => __( 'Edit Table', 'tablepress' ),
				'admin_menu_title' => '',
				'nav_tab_title'    => '',
				'required_cap'     => 'tablepress_edit_tables',
			),
			'import'  => array(
				'show_entry'       => true,
				'page_title'       => __( 'Import a Table', 'tablepress' ),
				'admin_menu_title' => __( 'Import a Table', 'tablepress' ),
				'nav_tab_title'    => _x( 'Import', 'navigation bar', 'tablepress' ),
				'required_cap'     => 'tablepress_import_tables',
			),
			'export'  => array(
				'show_entry'       => true,
				'page_title'       => __( 'Export a Table', 'tablepress' ),
				'admin_menu_title' => __( 'Export a Table', 'tablepress' ),
				'nav_tab_title'    => _x( 'Export', 'navigation bar', 'tablepress' ),
				'required_cap'     => 'tablepress_export_tables',
			),
			'options' => array(
				'show_entry'       => true,
				'page_title'       => __( 'Plugin Options', 'tablepress' ),
				'admin_menu_title' => __( 'Plugin Options', 'tablepress' ),
				'nav_tab_title'    => __( 'Plugin Options', 'tablepress' ),
				'required_cap'     => 'tablepress_access_options_screen',
			),
			'about'   => array(
				'show_entry'       => true,
				'page_title'       => __( 'About', 'tablepress' ),
				'admin_menu_title' => __( 'About TablePress', 'tablepress' ),
				'nav_tab_title'    => __( 'About', 'tablepress' ),
				'required_cap'     => 'tablepress_access_about_screen',
			),
		);

		/**
		 * Filters the available TablePres Views/Actions and their parameters.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, array<string, bool|string>> $view_actions The available Views/Actions and their parameters.
		 */
		$this->view_actions = apply_filters( 'tablepress_admin_view_actions', $this->view_actions );
	}

	/*
	 * HTTP POST actions.
	 */

	/**
	 * Handle Bulk Actions (Copy, Export, Delete) on "All Tables" list screen.
	 *
	 * @since 1.0.0
	 */
	public function handle_post_action_list(): void {
		TablePress::check_nonce( 'list' );

		if ( isset( $_POST['bulk-action-selector-top'] ) && '-1' !== $_POST['bulk-action-selector-top'] ) {
			$bulk_action = $_POST['bulk-action-selector-top'];
		} elseif ( isset( $_POST['bulk-action-selector-bottom'] ) && '-1' !== $_POST['bulk-action-selector-bottom'] ) {
			$bulk_action = $_POST['bulk-action-selector-bottom'];
		} else {
			$bulk_action = false;
		}

		if ( ! in_array( $bulk_action, array( 'copy', 'export', 'delete' ), true ) ) {
			TablePress::redirect( array( 'action' => 'list', 'message' => 'error_bulk_action_invalid' ) );
		}

		if ( empty( $_POST['table'] ) || ! is_array( $_POST['table'] ) ) {
			TablePress::redirect( array( 'action' => 'list', 'message' => 'error_no_selection' ) );
		}

		$tables = wp_unslash( $_POST['table'] );

		$no_success = array(); // To store table IDs that failed.

		switch ( $bulk_action ) {
			case 'copy':
				foreach ( $tables as $table_id ) {
					if ( current_user_can( 'tablepress_copy_table', $table_id ) ) {
						$copy_table_id = TablePress::$model_table->copy( $table_id );
						if ( is_wp_error( $copy_table_id ) ) {
							$no_success[] = $table_id;
						}
					} else {
						$no_success[] = $table_id;
					}
				}
				break;
			case 'export':
				/*
				 * Cap check is done on redirect target page.
				 * To export, redirect to "Export" screen, with selected table IDs.
				 */
				$table_ids = implode( ',', $tables );
				TablePress::redirect( array( 'action' => 'export', 'table_id' => $table_ids ) );
				// break; // unreachable.
			case 'delete':
				foreach ( $tables as $table_id ) {
					if ( current_user_can( 'tablepress_delete_table', $table_id ) ) {
						$deleted = TablePress::$model_table->delete( $table_id );
						if ( is_wp_error( $deleted ) ) {
							$no_success[] = $table_id;
						}
					} else {
						$no_success[] = $table_id;
					}
				}
				break;
		}

		if ( 0 !== count( $no_success ) ) { // @todo maybe pass this information to the view?
			$message = "error_{$bulk_action}_not_all_tables";
		} else {
			$plural = ( count( $tables ) > 1 ) ? '_plural' : '';
			$message = "success_{$bulk_action}{$plural}";
		}

		/*
		 * Slightly more complex redirect method, to account for sort, search, and pagination in the WP_List_Table on the List View,
		 * but only if this action succeeds, to have everything fresh in the event of an error.
		 */
		$sendback = wp_get_referer();
		if ( ! $sendback ) {
			$sendback = TablePress::url( array( 'action' => 'list', 'message' => $message ) );
		} else {
			$sendback = remove_query_arg( array( 'action', 'message', 'table_id' ), $sendback );
			$sendback = add_query_arg( array( 'action' => 'list', 'message' => $message ), $sendback );
		}
		wp_redirect( $sendback );
		exit;
	}

	/**
	 * Add a table, according to the parameters on the "Add new Table" screen.
	 *
	 * @since 1.0.0
	 */
	public function handle_post_action_add(): void {
		TablePress::check_nonce( 'add' );

		if ( ! current_user_can( 'tablepress_add_tables' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'default' ), 403 );
		}

		if ( empty( $_POST['table'] ) || ! is_array( $_POST['table'] ) ) {
			TablePress::redirect( array( 'action' => 'add', 'message' => 'error_add', 'error_details' => 'The HTTP POST data is empty.' ) );
		}

		$add_table = wp_unslash( $_POST['table'] );

		// Perform confidence checks of posted data.
		$name = $add_table['name'] ?? '';
		$description = $add_table['description'] ?? '';
		if ( ! isset( $add_table['rows'], $add_table['columns'] ) ) {
			TablePress::redirect( array( 'action' => 'add', 'message' => 'error_add', 'error_details' => 'The HTTP POST data does not contain the table size.' ) );
		}

		$num_rows = absint( $add_table['rows'] );
		$num_columns = absint( $add_table['columns'] );
		if ( 0 === $num_rows || 0 === $num_columns ) {
			TablePress::redirect( array( 'action' => 'add', 'message' => 'error_add', 'error_details' => 'The table size is invalid.' ) );
		}

		// Create a new table array with information from the posted data.
		$new_table = array(
			'name'        => $name,
			'description' => $description,
			'data'        => array_fill( 0, $num_rows, array_fill( 0, $num_columns, '' ) ),
			'visibility'  => array(
				'rows'    => array_fill( 0, $num_rows, 1 ),
				'columns' => array_fill( 0, $num_columns, 1 ),
			),
		);
		// Merge this data into an empty table template.
		$table = TablePress::$model_table->prepare_table( TablePress::$model_table->get_table_template(), $new_table, false );
		if ( is_wp_error( $table ) ) {
			TablePress::redirect( array( 'action' => 'add', 'message' => 'error_add', 'error_details' => TablePress::get_wp_error_string( $table ) ) );
		}

		// Add the new table (and get its first ID).
		$table_id = TablePress::$model_table->add( $table );
		if ( is_wp_error( $table_id ) ) {
			TablePress::redirect( array( 'action' => 'add', 'message' => 'error_add', 'error_details' => TablePress::get_wp_error_string( $table_id ) ) );
		}

		TablePress::redirect( array( 'action' => 'edit', 'table_id' => $table_id, 'message' => 'success_add' ) );
	}

	/**
	 * Save changed "Plugin Options".
	 *
	 * @since 1.0.0
	 */
	public function handle_post_action_options(): void {
		TablePress::check_nonce( 'options' );

		if ( ! current_user_can( 'tablepress_access_options_screen' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'default' ), 403 );
		}

		if ( empty( $_POST['options'] ) || ! is_array( $_POST['options'] ) ) {
			TablePress::redirect( array( 'action' => 'options', 'message' => 'error_save' ) );
		}

		$posted_options = wp_unslash( $_POST['options'] );

		// Valid new options that will be merged into existing ones.
		$new_options = array();

		// Check each posted option value, and (maybe) add it to the new options.
		if ( ! empty( $posted_options['admin_menu_parent_page'] ) && '-' !== $posted_options['admin_menu_parent_page'] ) {
			$new_options['admin_menu_parent_page'] = $posted_options['admin_menu_parent_page'];
			// Re-init parent information, as `TablePress::redirect()` URL might be wrong otherwise.
			/** This filter is documented in classes/class-controller.php */
			TablePress::$controller->parent_page = apply_filters( 'tablepress_admin_menu_parent_page', $posted_options['admin_menu_parent_page'] );
			TablePress::$controller->is_top_level_page = in_array( TablePress::$controller->parent_page, array( 'top', 'middle', 'bottom' ), true );
		}

		// Custom CSS can only be saved if the user is allowed to do so.
		$update_custom_css_files = false;
		if ( current_user_can( 'tablepress_edit_options' ) ) {
			// Checkbox.
			$new_options['use_custom_css'] = ( isset( $posted_options['use_custom_css'] ) && 'true' === $posted_options['use_custom_css'] );

			if ( isset( $posted_options['custom_css'] ) ) {
				$new_options['custom_css'] = $posted_options['custom_css'];

				$tablepress_css = TablePress::load_class( 'TablePress_CSS', 'class-css.php', 'classes' );

				if ( '' !== $new_options['custom_css'] ) {
					// Update "Custom CSS" to use DataTables 2 variants instead of old DataTables 1.x CSS classes.
					$new_options['custom_css'] = TablePress::convert_datatables_api_data( $new_options['custom_css'] );
					// Sanitize and tidy up Custom CSS.
					$new_options['custom_css'] = $tablepress_css->sanitize_css( $new_options['custom_css'] );
					// Minify Custom CSS.
					$new_options['custom_css_minified'] = $tablepress_css->minify_css( $new_options['custom_css'] );
				} else {
					$new_options['custom_css_minified'] = '';
				}

				// Maybe update CSS files as well.
				$custom_css_file_contents = $tablepress_css->load_custom_css_from_file( 'normal' );
				if ( false === $custom_css_file_contents ) {
					$custom_css_file_contents = '';
				}
				// Don't write to file if it already has the desired content.
				if ( $new_options['custom_css'] !== $custom_css_file_contents ) {
					$update_custom_css_files = true;
					// Set to false again. As it was set here, it will be set true again, if file saving succeeds.
					$new_options['use_custom_css_file'] = false;
				}
			}
		}

		// Save gathered new options (will be merged into existing ones), and flush caches of caching plugins, to make sure that the new Custom CSS is used.
		if ( ! empty( $new_options ) ) {
			TablePress::$model_options->update( $new_options );
			TablePress::$model_table->_flush_caching_plugins_caches();
		}

		if ( $update_custom_css_files ) { // Capability check is performed above.
			TablePress::redirect( array( 'action' => 'options', 'item' => 'save_custom_css' ), true );
		}

		TablePress::redirect( array( 'action' => 'options', 'message' => 'success_save' ) );
	}

	/**
	 * Export selected tables.
	 *
	 * @since 1.0.0
	 */
	public function handle_post_action_export(): void {
		TablePress::check_nonce( 'export' );

		if ( ! current_user_can( 'tablepress_export_tables' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'default' ), 403 );
		}

		if ( empty( $_POST['export'] ) || ! is_array( $_POST['export'] ) ) {
			TablePress::redirect( array( 'action' => 'export', 'message' => 'error_export', 'error_details' => 'The HTTP POST data is empty.' ) );
		}

		$export = wp_unslash( $_POST['export'] );

		if ( empty( $export['tables_list'] ) ) {
			TablePress::redirect( array( 'action' => 'export', 'message' => 'error_export', 'error_details' => 'The HTTP POST data does not contain tables.' ) );
		}

		/** @var TablePress_Export $exporter */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort
		$exporter = TablePress::load_class( 'TablePress_Export', 'class-export.php', 'classes' );

		if ( empty( $export['format'] ) || ! isset( $exporter->export_formats[ $export['format'] ] ) ) {
			TablePress::redirect( array( 'action' => 'export', 'message' => 'error_export', 'error_details' => 'The export format is invalid.' ) );
		}
		if ( empty( $export['csv_delimiter'] ) ) {
			// Set a value, so that the variable exists.
			$export['csv_delimiter'] = '';
		}
		if ( 'csv' === $export['format'] && ! isset( $exporter->csv_delimiters[ $export['csv_delimiter'] ] ) ) {
			TablePress::redirect( array( 'action' => 'export', 'message' => 'error_export', 'error_details' => 'The CSV delimiter is invalid.' ) );
		}

		$tables = explode( ',', $export['tables_list'] );

		// Determine if ZIP file support is available.
		if ( $exporter->zip_support_available
		&& ( ( isset( $export['zip_file'] ) && 'true' === $export['zip_file'] ) || count( $tables ) > 1 ) ) {
			// Export to ZIP only if ZIP is desired or if more than one table were selected (mandatory then).
			$export_to_zip = true;
		} else {
			$export_to_zip = false;
		}

		if ( ! $export_to_zip ) {
			// Exporting without a ZIP file is only possible for one table, so take the first one.
			if ( ! current_user_can( 'tablepress_export_table', $tables[0] ) ) {
				wp_die( __( 'Sorry, you are not allowed to access this page.', 'default' ), 403 );
			}
			// Load table, with table data, options, and visibility settings.
			$table = TablePress::$model_table->load( $tables[0], true, true );
			if ( is_wp_error( $table ) ) {
				TablePress::redirect( array( 'action' => 'export', 'message' => 'error_load_table', 'export_format' => $export['format'], 'csv_delimiter' => $export['csv_delimiter'], 'error_details' => TablePress::get_wp_error_string( $table ) ) );
			}
			if ( isset( $table['is_corrupted'] ) && $table['is_corrupted'] ) {
				TablePress::redirect( array( 'action' => 'export', 'message' => 'error_table_corrupted', 'export_format' => $export['format'], 'csv_delimiter' => $export['csv_delimiter'] ) );
			}
			$download_filename = sprintf( '%1$s-%2$s-%3$s.%4$s', $table['id'], $table['name'], wp_date( 'Y-m-d' ), $export['format'] );
			/**
			 * Filters the download filename of the exported table.
			 *
			 * @since 2.0.0
			 *
			 * @param string $download_filename The download filename of exported table.
			 * @param string $table_id          Table ID of the exported table.
			 * @param string $table_name        Table name of the exported table.
			 * @param string $export_format     Format for the export ('csv', 'html', 'json', 'zip').
			 * @param bool   $export_to_zip     Whether the export is to a ZIP file (of multiple export files).
			 */
			$download_filename = apply_filters( 'tablepress_export_filename', $download_filename, $table['id'], $table['name'], $export['format'], $export_to_zip );
			$download_filename = sanitize_file_name( $download_filename );
			// Export the table.
			$export_data = $exporter->export_table( $table, $export['format'], $export['csv_delimiter'] );
			/**
			 * Filters the exported table data.
			 *
			 * @since 1.6.0
			 *
			 * @param string               $export_data   The exported table data.
			 * @param array<string, mixed> $table         Table to be exported.
			 * @param string               $export_format Format for the export ('csv', 'html', 'json').
			 * @param string               $csv_delimiter Delimiter for CSV export.
			 */
			$export_data = apply_filters( 'tablepress_export_data', $export_data, $table, $export['format'], $export['csv_delimiter'] );
			$download_data = $export_data;
		} else {
			// Zipping can use a lot of memory and execution time, but not this much hopefully.
			wp_raise_memory_limit( 'admin' );
			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}

			$zip_file = new ZipArchive();
			$download_filename = sprintf( 'tablepress-export-%1$s-%2$s.zip', wp_date( 'Y-m-d-H-i-s' ), $export['format'] );
			/** This filter is documented in controllers/controller-admin.php */
			$download_filename = apply_filters( 'tablepress_export_filename', $download_filename, '', '', $export['format'], $export_to_zip );
			$download_filename = sanitize_file_name( $download_filename );
			$full_filename = wp_tempnam( $download_filename );
			if ( true !== $zip_file->open( $full_filename, ZipArchive::OVERWRITE ) ) {
				@unlink( $full_filename ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				TablePress::redirect( array( 'action' => 'export', 'message' => 'error_create_zip_file', 'export_format' => $export['format'], 'csv_delimiter' => $export['csv_delimiter'], 'error_details' => 'The ZIP file could not be opened for writing.' ) );
			}

			foreach ( $tables as $table_id ) {
				// Don't export tables for which the user doesn't have the necessary export rights.
				if ( ! current_user_can( 'tablepress_export_table', $table_id ) ) {
					continue;
				}
				// Load table, with table data, options, and visibility settings.
				$table = TablePress::$model_table->load( $table_id, true, true );
				// Don't export if the table could not be loaded.
				if ( is_wp_error( $table ) ) {
					continue;
				}
				// Don't export if the table is corrupted.
				if ( isset( $table['is_corrupted'] ) && $table['is_corrupted'] ) {
					continue;
				}
				$export_data = $exporter->export_table( $table, $export['format'], $export['csv_delimiter'] );
				/** This filter is documented in controllers/controller-admin.php */
				$export_data = apply_filters( 'tablepress_export_data', $export_data, $table, $export['format'], $export['csv_delimiter'] );
				$export_filename = sprintf( '%1$s-%2$s-%3$s.%4$s', $table['id'], $table['name'], wp_date( 'Y-m-d' ), $export['format'] );
				/** This filter is documented in controllers/controller-admin.php */
				$export_filename = apply_filters( 'tablepress_export_filename', $export_filename, $table['id'], $table['name'], $export['format'], $export_to_zip );
				$export_filename = sanitize_file_name( $export_filename );
				$zip_file->addFromString( $export_filename, $export_data );
			}

			// If something went wrong, or no files were added to the ZIP file, bail out.
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( ZipArchive::ER_OK !== $zip_file->status || 0 === $zip_file->numFiles ) {
				$zip_file->close();
				@unlink( $full_filename ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				TablePress::redirect( array( 'action' => 'export', 'message' => 'error_create_zip_file', 'export_format' => $export['format'], 'csv_delimiter' => $export['csv_delimiter'], 'error_details' => 'The ZIP file could not be written or is empty.' ) );
			}
			$zip_file->close();

			// Load contents of the ZIP file, to send it as a download.
			$download_data = file_get_contents( $full_filename );
			if ( false === $download_data ) {
				@unlink( $full_filename ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				TablePress::redirect( array( 'action' => 'export', 'message' => 'error_create_zip_file', 'export_format' => $export['format'], 'csv_delimiter' => $export['csv_delimiter'], 'error_details' => 'The ZIP file content could not be read.' ) );
			}
			@unlink( $full_filename ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		// Send download headers for export file.
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( "Content-Disposition: attachment; filename=\"{$download_filename}\"" );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . strlen( $download_data ) );
		// $filetype = text/csv, text/html, application/json
		// header( 'Content-Type: ' . $filetype. '; charset=' . get_option( 'blog_charset' ) );
		@ob_end_clean(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		flush();
		echo $download_data;
		exit;
	}

	/**
	 * Import data from existing source (Upload, URL, Server, Direct input).
	 *
	 * @since 1.0.0
	 */
	public function handle_post_action_import(): void {
		TablePress::check_nonce( 'import' );

		if ( ! current_user_can( 'tablepress_import_tables' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'default' ), 403 );
		}

		if ( empty( $_POST['import'] ) || ! is_array( $_POST['import'] ) ) {
			TablePress::redirect( array( 'action' => 'import', 'message' => 'error_import', 'error_details' => 'The HTTP POST data is empty.' ) );
		}

		$import_config = wp_unslash( $_POST['import'] );

		if ( empty( $import_config['source'] ) ) {
			TablePress::redirect( array( 'action' => 'import', 'message' => 'error_import', 'error_details' => 'The HTTP POST does not contain an import configuration.' ) );
		}

		// For security reasons, the "server" source is only available for super admins on multisite and admins on single sites.
		if ( 'server' === $import_config['source'] ) {
			if ( ! is_super_admin() && ! ( ! is_multisite() && current_user_can( 'manage_options' ) ) ) {
				TablePress::redirect( array( 'action' => 'import', 'message' => 'error_import', 'error_details' => 'You do not have the required access rights.' ) );
			}
		}

		// For security reasons, the "url" source is only available admins and editors via a custom capability.
		if ( 'url' === $import_config['source'] ) {
			if ( ! current_user_can( 'tablepress_import_tables_url' ) ) {
				TablePress::redirect( array( 'action' => 'import', 'message' => 'error_import', 'error_details' => 'You do not have the required access rights.' ) );
			}
		}

		// Move file upload data to the main import configuration.
		$import_config['file-upload'] = $_FILES['import_file_upload'] ?? null;

		// Check if the source data for the chosen import source is defined.
		if ( empty( $import_config[ $import_config['source'] ] ) ) {
			TablePress::redirect( array( 'action' => 'import', 'message' => 'error_import', 'error_details' => 'The HTTP POST data does not contain an import source.' ) );
		}

		// Set default values for non-essential configuration variables.
		if ( ! isset( $import_config['type'] ) ) {
			$import_config['type'] = 'add';
		}
		if ( ! isset( $import_config['existing_table'] ) ) {
			$import_config['existing_table'] = '';
		}

		$import_config['legacy_import'] = ( isset( $import_config['legacy_import'] ) && 'true' === $import_config['legacy_import'] );

		$importer = TablePress::load_class( 'TablePress_Import', 'class-import.php', 'classes' );
		$import = $importer->run( $import_config );

		if ( is_wp_error( $import ) || 0 < count( $import['errors'] ) ) {
			$redirect_parameters = array(
				'action'                => 'import',
				'message'               => 'error_import',
				'import_type'           => $import_config['type'],
				'import_existing_table' => $import_config['existing_table'],
				'import_source'         => $import_config['source'],
				'legacy_import'         => $import_config['legacy_import'],
			);
			if ( in_array( $import_config['source'], array( 'url', 'server' ), true ) ) {
				$redirect_parameters[ "import_{$import_config['source']}" ] = $import_config[ $import_config['source'] ];
			}
			if ( is_wp_error( $import ) ) {
				$redirect_parameters['error_details'] = TablePress::get_wp_error_string( $import );
			} elseif ( 0 < count( $import['errors'] ) ) {
				$wp_error_strings = array();
				foreach ( $import['errors'] as $file ) {
					$wp_error_strings[] = TablePress::get_wp_error_string( $file->error );
				}
				$redirect_parameters['error_details'] = implode( ', ', $wp_error_strings );
			}
			TablePress::redirect( $redirect_parameters );
		}

		// At this point, there were no import errors.
		if ( count( $import['tables'] ) > 1 ) {
			TablePress::redirect( array( 'action' => 'list', 'message' => 'success_import' ) );
		} elseif ( 1 === count( $import['tables'] ) ) {
			TablePress::redirect( array( 'action' => 'edit', 'table_id' => $import['tables'][0]['id'], 'message' => 'success_import' ) );
		} else {
			TablePress::redirect( array( 'action' => 'import', 'message' => 'error_import', 'error_details' => 'The number of imported tables is invalid.' ) );
		}
	}

	/*
	 * HTTP GET actions.
	 */

	/**
	 * Hide a header message on an admin screen.
	 *
	 * @since 1.0.0
	 */
	public function handle_get_action_hide_message(): void {
		$message_item = ! empty( $_GET['item'] ) ? $_GET['item'] : '';
		TablePress::check_nonce( 'hide_message', $message_item );

		if ( ! current_user_can( 'tablepress_list_tables' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'default' ), 403 );
		}

		TablePress::$model_options->update( "message_{$message_item}", false );

		$return = ! empty( $_GET['return'] ) ? $_GET['return'] : 'list';
		TablePress::redirect( array( 'action' => $return ) );
	}

	/**
	 * Delete a table.
	 *
	 * @since 1.0.0
	 */
	public function handle_get_action_delete_table(): void {
		$table_id = ( ! empty( $_GET['item'] ) ) ? $_GET['item'] : false;
		TablePress::check_nonce( 'delete_table', $table_id );

		$return = ! empty( $_GET['return'] ) ? $_GET['return'] : 'list';
		$return_item = ! empty( $_GET['return_item'] ) ? $_GET['return_item'] : false;

		// The nonce check should actually catch this already.
		if ( false === $table_id ) {
			TablePress::redirect( array( 'action' => $return, 'message' => 'error_delete', 'table_id' => $return_item ) );
		}

		if ( ! current_user_can( 'tablepress_delete_table', $table_id ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'default' ), 403 );
		}

		$deleted = TablePress::$model_table->delete( $table_id );
		if ( is_wp_error( $deleted ) ) {
			TablePress::redirect( array( 'action' => $return, 'message' => 'error_delete', 'table_id' => $return_item, 'error_details' => TablePress::get_wp_error_string( $deleted ) ) );
		}

		/*
		 * Slightly more complex redirect method, to account for sort, search, and pagination in the WP_List_Table on the List View,
		 * but only if this action succeeds, to have everything fresh in the event of an error.
		 */
		$sendback = wp_get_referer();
		if ( ! $sendback ) {
			$sendback = TablePress::url( array( 'action' => 'list', 'message' => 'success_delete', 'table_id' => $return_item ) );
		} else {
			$sendback = remove_query_arg( array( 'action', 'message', 'table_id' ), $sendback );
			$sendback = add_query_arg( array( 'action' => 'list', 'message' => 'success_delete', 'table_id' => $return_item ), $sendback );
		}
		wp_redirect( $sendback );
		exit;
	}

	/**
	 * Copy a table.
	 *
	 * @since 1.0.0
	 */
	public function handle_get_action_copy_table(): void {
		$table_id = ( ! empty( $_GET['item'] ) ) ? $_GET['item'] : false;
		TablePress::check_nonce( 'copy_table', $table_id );

		$return = ! empty( $_GET['return'] ) ? $_GET['return'] : 'list';
		$return_item = ! empty( $_GET['return_item'] ) ? $_GET['return_item'] : false;

		// The nonce check should actually catch this already.
		if ( false === $table_id ) {
			TablePress::redirect( array( 'action' => $return, 'message' => 'error_copy', 'table_id' => $return_item ) );
		}

		if ( ! current_user_can( 'tablepress_copy_table', $table_id ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'default' ), 403 );
		}

		$copy_table_id = TablePress::$model_table->copy( $table_id );
		if ( is_wp_error( $copy_table_id ) ) {
			TablePress::redirect( array( 'action' => $return, 'message' => 'error_copy', 'table_id' => $return_item, 'error_details' => TablePress::get_wp_error_string( $copy_table_id ) ) );
		}
		$return_item = $copy_table_id;

		/*
		 * Slightly more complex redirect method, to account for sort, search, and pagination in the WP_List_Table on the List View,
		 * but only if this action succeeds, to have everything fresh in the event of an error.
		 */
		$sendback = wp_get_referer();
		if ( ! $sendback ) {
			$sendback = TablePress::url( array( 'action' => $return, 'message' => 'success_copy', 'table_id' => $return_item ) );
		} else {
			$sendback = remove_query_arg( array( 'action', 'message', 'table_id' ), $sendback );
			$sendback = add_query_arg( array( 'action' => $return, 'message' => 'success_copy', 'table_id' => $return_item ), $sendback );
		}
		wp_redirect( $sendback );
		exit;
	}

	/**
	 * Preview a table.
	 *
	 * @since 1.0.0
	 */
	public function handle_get_action_preview_table(): void {
		$table_id = ( ! empty( $_GET['item'] ) ) ? $_GET['item'] : false;
		TablePress::check_nonce( 'preview_table', $table_id );

		// Nonce check should actually catch this already.
		if ( false === $table_id ) {
			wp_die( __( 'The preview could not be loaded.', 'tablepress' ), __( 'Preview', 'tablepress' ) );
		}

		if ( ! current_user_can( 'tablepress_preview_table', $table_id ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'default' ), 403 );
		}

		// Load table, with table data, options, and visibility settings.
		$table = TablePress::$model_table->load( $table_id, true, true );
		if ( is_wp_error( $table ) ) {
			wp_die( __( 'The table could not be loaded.', 'tablepress' ), __( 'Preview', 'tablepress' ) );
		}

		// Sanitize all table data to remove unsafe HTML from the preview output, if the user is not allowed to work with unfiltered HTML.
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$table = TablePress::$model_table->sanitize( $table );
		}

		// Create a render class instance.
		$_render = TablePress::load_class( 'TablePress_Render', 'class-render.php', 'classes' );
		// Merge desired options with default render options (see TablePress_Controller_Frontend::shortcode_table()).
		$default_render_options = $_render->get_default_render_options();
		/** This filter is documented in controllers/controller-frontend.php */
		$default_render_options = apply_filters( 'tablepress_shortcode_table_default_shortcode_atts', $default_render_options );
		$render_options = shortcode_atts( $default_render_options, $table['options'] );
		/** This filter is documented in controllers/controller-frontend.php */
		$render_options = apply_filters( 'tablepress_shortcode_table_shortcode_atts', $render_options );
		$render_options['html_id'] = "tablepress-{$table['id']}";
		$render_options['block_preview'] = true;
		$_render->set_input( $table, $render_options );
		$view_data = array(
			'table_id'         => $table_id,
			'head_html'        => $_render->get_preview_css(),
			'body_html'        => $_render->get_output( 'html' ),
			'site_used_editor' => TablePress::site_used_editor(),
		);

		$custom_css = TablePress::$model_options->get( 'custom_css' );
		$use_custom_css = ( TablePress::$model_options->get( 'use_custom_css' ) && '' !== $custom_css );
		if ( $use_custom_css ) {
			$view_data['head_html'] .= "<style>\n{$custom_css}\n</style>\n";
		}

		// Prepare, initialize, and render the view.
		$this->view = TablePress::load_view( 'preview_table', $view_data );
		$this->view->render();
	}

	/**
	 * Shows a list of tables in the Editor toolbar Thickbox (opened by TinyMCE or Quicktags button).
	 *
	 * @since 1.0.0
	 */
	public function handle_get_action_editor_button_thickbox(): void {
		TablePress::check_nonce( 'editor_button_thickbox' );

		if ( ! current_user_can( 'tablepress_list_tables' ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'default' ), 403 );
		}

		$view_data = array(
			// Load all table IDs without priming the post meta cache, as table options/visibility are not needed.
			'table_ids' => TablePress::$model_table->load_all( false ),
		);

		set_current_screen( 'tablepress_editor_button_thickbox' );

		// Prepare, initialize, and render the view.
		$this->view = TablePress::load_view( 'editor_button_thickbox', $view_data );
		$this->view->render();
	}

	/**
	 * Uninstall TablePress, and delete all tables and options.
	 *
	 * @since 1.0.0
	 */
	public function handle_get_action_uninstall_tablepress(): void {
		TablePress::check_nonce( 'uninstall_tablepress' );

		$plugin = TABLEPRESS_BASENAME;

		if ( ! current_user_can( 'deactivate_plugin', $plugin ) || ! current_user_can( 'tablepress_edit_options' ) || ! current_user_can( 'tablepress_delete_tables' ) || is_plugin_active_for_network( $plugin ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'default' ), 403 );
		}

		// Deactivate TablePress for the site (but not for the network).
		deactivate_plugins( $plugin, false, false );
		update_option( 'recently_activated', array( $plugin => time() ) + (array) get_option( 'recently_activated', array() ) );

		// Delete all tables, "Custom CSS" files, and options.
		TablePress::$model_table->delete_all();
		$tablepress_css = TablePress::load_class( 'TablePress_CSS', 'class-css.php', 'classes' );
		$css_files_deleted = $tablepress_css->delete_custom_css_files();
		TablePress::$model_options->remove_access_capabilities();

		TablePress::$model_table->destroy();
		TablePress::$model_options->destroy();

		$output = '<strong>' . __( 'TablePress was uninstalled successfully.', 'tablepress' ) . '</strong><br><br>';
		$output .= __( 'All tables, data, and options were deleted.', 'tablepress' );
		if ( is_multisite() ) {
			$output .= ' ' . __( 'You may now ask the network admin to delete the plugin&#8217;s folder <code>tablepress</code> from the server, if no other site in the network uses it.', 'tablepress' );
		} else {
			$output .= ' ' . __( 'You may now manually delete the plugin&#8217;s folder <code>tablepress</code> from the <code>plugins</code> directory on your server or use the &#8220;Delete&#8221; link for TablePress on the WordPress &#8220;Plugins&#8221; page.', 'tablepress' );
		}
		if ( $css_files_deleted ) {
			$output .= ' ' . __( 'Your TablePress &#8220;Custom CSS&#8221; files have been deleted automatically.', 'tablepress' );
		} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
			if ( is_multisite() ) {
				$output .= ' ' . __( 'Please also ask him to delete your TablePress &#8220;Custom CSS&#8221; files from the server.', 'tablepress' );
			} else {
				$output .= ' ' . __( 'You may now also delete your TablePress &#8220;Custom CSS&#8221; files in the <code>wp-content</code> folder.', 'tablepress' );
			}
		}
		$output .= "</p>\n<p>";
		if ( ! is_multisite() || is_super_admin() ) {
			$output .= '<a class="button" href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . __( 'Go to &#8220;Plugins&#8221; page', 'tablepress' ) . '</a> ';
		}
		$output .= '<a class="button" href="' . esc_url( admin_url( 'index.php' ) ) . '">' . __( 'Go to Dashboard', 'tablepress' ) . '</a>';

		wp_die( $output, __( 'Uninstall TablePress', 'tablepress' ), array( 'response' => 200, 'back_link' => false ) );
	}

} // class TablePress_Admin_Controller
