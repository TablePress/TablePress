<?php
/**
 * TablePress Base View with members and methods for all views
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Base View class
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
abstract class TablePress_View {

	/**
	 * Data for the view.
	 *
	 * @since 1.0.0
	 * @var array<string, mixed>
	 */
	protected array $data = array();

	/**
	 * Number of screen columns for post boxes.
	 *
	 * @since 1.0.0
	 */
	protected int $screen_columns = 0;

	/**
	 * User action for this screen.
	 *
	 * @since 1.0.0
	 */
	protected string $action = '';

	/**
	 * Instance of the Admin Page Helper Class, with necessary functions.
	 *
	 * @since 1.0.0
	 */
	protected \TablePress_Admin_Page $admin_page;

	/**
	 * List of text boxes (similar to post boxes, but just with text and without extra functionality).
	 *
	 * @since 1.0.0
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	protected array $textboxes = array();

	/**
	 * List of messages that are to be displayed as boxes below the page title.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	protected array $header_messages = array();

	/**
	 * Whether there are post boxes registered for this screen,
	 * is automatically set to true, when a meta box is added.
	 *
	 * @since 1.0.0
	 */
	protected bool $has_meta_boxes = false;

	/**
	 * List of WP feature pointers for this view.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	protected array $wp_pointers = array();

	/**
	 * Initializes the View class, by setting the correct screen columns and adding help texts.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$screen = get_current_screen();
		if ( 0 !== $this->screen_columns ) {
			$screen->add_option( 'layout_columns', array( 'max' => $this->screen_columns ) ); // @phpstan-ignore method.nonObject
		}
		// Enable two column layout.
		add_filter( "get_user_option_screen_layout_{$screen->id}", array( $this, 'set_current_screen_layout_columns' ) ); // @phpstan-ignore property.nonObject

		$common_content = '<p>' . sprintf( __( 'More information about TablePress can be found on the <a href="%1$s">plugin website</a> or on its page in the <a href="%2$s">WordPress Plugin Directory</a>.', 'tablepress' ), 'https://tablepress.org/', 'https://wordpress.org/plugins/tablepress/' ) . '</p>';
		$common_content .= '<p>' . sprintf( __( 'For technical information, please see the <a href="%s">Documentation</a>.', 'tablepress' ), 'https://tablepress.org/documentation/' ) . ' ' . sprintf( __( 'Common questions are answered in the <a href="%s">FAQ</a>.', 'tablepress' ), 'https://tablepress.org/faq/' ) . '</p>';

		if ( tb_tp_fs()->is_free_plan() ) {
			$common_content .= '<p>'
				. sprintf( __( '<a href="%1$s">Support</a> is provided through the <a href="%2$s">WordPress Support Forums</a>.', 'tablepress' ), 'https://tablepress.org/support/', 'https://wordpress.org/tags/tablepress' )
				. ' '
				. sprintf( __( 'Before asking for support, please carefully read the <a href="%s">Frequently Asked Questions</a>, where you will find answers to the most common questions, and search through the forums.', 'tablepress' ), 'https://tablepress.org/faq/' )
				. '</p>';
			$common_content .= '<p><strong>' . sprintf( __( 'More great features for you and your site’s visitors and priority email support are available with a Premium license plan of TablePress. <a href="%s">Go check them out!</a>', 'tablepress' ), 'https://tablepress.org/premium/?utm_source=plugin&utm_medium=textlink&utm_content=help-tab' ) . '</strong></p>';
		}

		$screen->add_help_tab( array( // @phpstan-ignore method.nonObject
			'id'      => 'tablepress-help', // This should be unique for the screen.
			'title'   => __( 'TablePress Help', 'tablepress' ),
			'content' => '<p>' . $this->help_tab_content() . '</p>' . $common_content,
		) );
		// "Sidebar" in the help tab.
		$screen->set_help_sidebar( // @phpstan-ignore method.nonObject
			'<p><strong>' . __( 'For more information:', 'tablepress' ) . '</strong></p>'
			. '<p><a href="https://tablepress.org/">TablePress Website</a></p>'
			. '<p><a href="https://tablepress.org/faq/">TablePress FAQ</a></p>'
			. '<p><a href="https://tablepress.org/documentation/">TablePress Documentation</a></p>'
			. '<p><a href="https://tablepress.org/support/">TablePress Support</a></p>'
		);
	}

	/**
	 * Changes the value of the user option "screen_layout_{$screen->id}" through a filter.
	 *
	 * @since 1.0.0
	 *
	 * @param int|false $result Current value of the user option.
	 * @return int New value for the user option.
	 */
	public function set_current_screen_layout_columns( /* int|false */ $result ): int {
		if ( false === $result ) {
			// The user option does not yet exist.
			$result = $this->screen_columns;
		} elseif ( $result > $this->screen_columns ) {
			// The value of the user option is bigger than what is possible on this screen (e.g. because the number of columns was reduced in an update).
			$result = $this->screen_columns;
		}
		return $result;
	}

	/**
	 * Sets up the view with data and do things that are necessary for all views.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $action Action for this view.
	 * @param array<string, mixed> $data   Data for this view.
	 */
	public function setup( /* string */ $action, array $data ) /* : void */ {
		// Don't use type hints (except array $data) in method declaration, as the method is extended in some TablePress Extensions which are no longer updated.

		$this->action = $action;
		$this->data = $data;

		// Set page title.
		$GLOBALS['title'] = sprintf( __( '%1$s &lsaquo; %2$s', 'tablepress' ), $this->data['view_actions'][ $this->action ]['page_title'], 'TablePress' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		// Admin page helpers, like script/style loading, could be moved to view.
		$this->admin_page = TablePress::load_class( 'TablePress_Admin_Page', 'class-admin-page-helper.php', 'classes' );
		$this->admin_page->enqueue_style( 'common', array( 'wp-components' ) );
		// RTL styles for the admin interface.
		if ( is_rtl() ) {
			$this->admin_page->enqueue_style( 'common-rtl', array( 'tablepress-common' ) );
		}
		$this->admin_page->enqueue_script( 'common', array( 'jquery-core', 'postbox' ) );

		$this->admin_page->add_admin_footer_text();

		// Initialize WP feature pointers for TablePress.
		$this->_init_wp_pointers();

		// Necessary fields for all views.
		$this->add_text_box( 'default_nonce_fields', array( $this, 'default_nonce_fields' ), 'header', false );
		$this->add_text_box( 'action_nonce_field', array( $this, 'action_nonce_field' ), 'header', false );
		$this->add_text_box( 'action_field', array( $this, 'action_field' ), 'header', false );
	}

	/**
	 * Registers a header message for the view.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text      Text for the header message.
	 * @param string $css_class Optional. Additional CSS class for the header message.
	 * @param string $title     Optional. Text for the header title.
	 */
	protected function add_header_message( string $text, string $css_class = 'is-success', string $title = '' ): void {
		if ( ! str_contains( $css_class, 'not-dismissible' ) ) {
			$css_class .= ' is-dismissible';
		}
		if ( '' !== $title ) {
			$title = "<h3>{$title}</h3>";
		}
		// Wrap the message text in HTML <p> tags if it does not already start with one (potentially with attributes), indicating custom message HTML.
		if ( '' !== $text && ! str_starts_with( $text, '<p' ) ) {
			$text = "<p>{$text}</p>";
		}
		$this->header_messages[] = "<div class=\"notice components-notice {$css_class}\"><div class=\"components-notice__content\">{$title}{$text}</div></div>\n";
	}

	/**
	 * Processes header action messages, i.e. check if a message should be added to the page.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, string> $action_messages Action messages for the screen.
	 */
	protected function process_action_messages( array $action_messages ): void {
		if ( $this->data['message'] && isset( $action_messages[ $this->data['message'] ] ) ) {
			$class = ( str_starts_with( $this->data['message'], 'error' ) ) ? 'is-error' : 'is-success';

			if ( '' !== $this->data['error_details'] ) {
				$this->data['error_details'] = '</p><p>' . sprintf( __( 'Error code: %s', 'tablepress' ), '<code>' . esc_html( $this->data['error_details'] ) . '</code>' );
			}

			$this->add_header_message( "<strong>{$action_messages[ $this->data['message'] ]}</strong>{$this->data['error_details']}", $class );
		}
	}

	/**
	 * Registers a text box for the view.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $id       Unique HTML ID for the text box container (only visible with $wrap = true).
	 * @param callable $callback Callback that prints the contents of the text box.
	 * @param string   $context  Optional. Context/position of the text box (normal, side, additional, header, submit).
	 * @param bool     $wrap     Whether the content of the text box shall be wrapped in a <div> container.
	 */
	protected function add_text_box( string $id, callable $callback, string $context = 'normal', bool $wrap = false ): void {
		if ( ! isset( $this->textboxes[ $context ] ) ) {
			$this->textboxes[ $context ] = array();
		}

		$long_id = "tablepress_{$this->action}-{$id}";
		$this->textboxes[ $context ][ $id ] = array(
			'id'       => $long_id,
			'callback' => $callback,
			'context'  => $context,
			'wrap'     => $wrap,
		);
	}

	/**
	 * Registers a post meta box for the view, that is drag/droppable with WordPress functionality.
	 *
	 * @since 1.0.0
	 *
	 * @param string                        $id            Unique ID for the meta box.
	 * @param string                        $title         Title for the meta box.
	 * @param callable                      $callback      Callback that prints the contents of the post meta box.
	 * @param 'normal'|'side'|'additional'  $context       Optional. Context/position of the post meta box (normal, side, additional).
	 * @param 'core'|'default'|'high'|'low' $priority      Optional. Order of the post meta box for the $context position (high, default, low).
	 * @param mixed[]|null                  $callback_args Optional. Additional data for the callback function (e.g. useful when in different class).
	 */
	protected function add_meta_box( string $id, string $title, callable $callback, string $context = 'normal', string $priority = 'default', ?array $callback_args = null ): void {
		$this->has_meta_boxes = true;
		add_meta_box( "tablepress_{$this->action}-{$id}", $title, $callback, null, $context, $priority, $callback_args );
	}

	/**
	 * Renders all text boxes for the given context.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Context (normal, side, additional, header, submit) for which registered text boxes shall be rendered.
	 */
	protected function do_text_boxes( string $context ): void {
		if ( empty( $this->textboxes[ $context ] ) ) {
			return;
		}

		foreach ( $this->textboxes[ $context ] as $box ) {
			if ( $box['wrap'] ) {
				echo "<div id=\"{$box['id']}\" class=\"textbox\">\n";
			}
			call_user_func( $box['callback'], $this->data, $box );
			if ( $box['wrap'] ) {
				echo "</div>\n";
			}
		}
	}

	/**
	 * Renders all post meta boxes for the given context, if there are post meta boxes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context Context (normal, side, additional) for which registered post meta boxes shall be rendered.
	 */
	protected function do_meta_boxes( string $context ): void {
		if ( $this->has_meta_boxes ) {
			do_meta_boxes( get_current_screen(), $context, $this->data ); // @phpstan-ignore argument.type
		}
	}

	/**
	 * Prints hidden fields with nonces for post meta box AJAX handling, if there are post meta boxes on the screen.
	 *
	 * The check is possible as this function is executed after post meta boxes have to be registered.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	protected function default_nonce_fields( array $data, array $box ): void {
		if ( ! $this->has_meta_boxes ) {
			return;
		}
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		echo "\n";
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		echo "\n";
	}

	/**
	 * Prints hidden field with a nonce for the screen's action, to be transmitted in HTTP requests.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	protected function action_nonce_field( array $data, array $box ): void {
		wp_nonce_field( TablePress::nonce( $this->action ) );
		echo "\n";
	}

	/**
	 * Prints hidden field with the screen action.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	protected function action_field( array $data, array $box ): void {
		echo "<input type=\"hidden\" name=\"action\" value=\"tablepress_{$this->action}\">\n";
	}

	/**
	 * Renders the current view.
	 *
	 * @since 1.0.0
	 */
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
		// "Import" screen has file upload.
		$enctype = ( 'import' === $this->action ) ? ' enctype="multipart/form-data"' : '';
		?>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"<?php echo $enctype; ?> id="tablepress-page-form">
			<?php
				$this->do_text_boxes( 'header' );
				$hide_if_no_js = ( in_array( $this->action, array( 'export', 'import' ), true ) ) ? ' class="hide-if-no-js"' : '';
			?>
			<div id="poststuff"<?php echo $hide_if_no_js; ?>>
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
		</form>
		</div>
		</div>
		<?php
	}

	/**
	 * Renders the navigation menu with links to the possible actions, highlighting the current one.
	 *
	 * @since 1.0.0
	 */
	protected function print_nav_tab_menu(): void {
		$name = __( 'TablePress', 'tablepress' );
		$filename = 'admin/img/tablepress.svg';
		?>
		<div id="tablepress-header" class="header">
			<h1 class="name">
				<img src="<?php echo plugins_url( $filename, TABLEPRESS__FILE__ ); ?>" alt="<?php esc_attr_e( 'TablePress plugin logo', 'tablepress' ); ?>">
				<span class="screen-reader-text"><?php echo $name; ?></span>
			</h1>
			<?php if ( ! TABLEPRESS_IS_PLAYGROUND_PREVIEW && tb_tp_fs()->is_free_plan() ) : ?>
				<div class="buttons">
					<a href="<?php echo esc_url( tb_tp_fs()->pricing_url( WP_FS__PERIOD_ANNUALLY, false ) ); ?>" class="tablepress-button">
						<span><?php _e( 'Upgrade to Premium', 'tablepress' ); ?></span>
						<span class="dashicons dashicons-arrow-right-alt"></span>
					</a>
				</div>
			<?php endif; ?>
		</div>
		<nav id="tablepress-nav">
			<ul class="nav-menu">
			<?php
			foreach ( $this->data['view_actions'] as $action => $entry ) {
				if ( '' === $entry['nav_tab_title'] ) {
					continue;
				}
				if ( ! current_user_can( $entry['required_cap'] ) ) {
					continue;
				}

				$url = esc_url( TablePress::url( array( 'action' => $action ) ) );
				$active = ( $action === $this->action ) ? ' active' : '';
				$separator = ( 'export' === $action ) ? ' separator' : ''; // Make the "Export" entry a separator, for some spacing.
				echo "<li class=\"nav-item\"><a id=\"tablepress-nav-item-{$action}\" class=\"nav-link{$active}{$separator}\" href=\"{$url}\">{$entry['nav_tab_title']}</a></li>";
			}
			?>
			</ul>
		</nav>
		<?php
	}

	/**
	 * Prints a notification about JavaScript not being activated in the browser.
	 *
	 * @since 2.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	public function textbox_no_javascript( array $data, array $box ): void {
		?>
		<div class="notice components-notice is-error hide-if-js">
			<div class="components-notice__content">
				<h3><em>
					<?php _e( 'Attention: Unfortunately, there is a problem!', 'tablepress' ); ?>
				</em></h3>
				<p style="font-size:14px">
					<strong><?php _e( 'This screen requires JavaScript. Please enable JavaScript in your browser settings.', 'tablepress' ); ?></strong><br>
					<?php _e( 'For help, please follow <a href="https://www.enable-javascript.com/">the instructions on how to enable JavaScript in your browser</a>.', 'tablepress' ); ?>
				</p>
				<p>
					<?php echo '<a href="' . esc_url( TablePress::url( array( 'action' => 'list' ) ) ) . '">' . __( 'Back to the List of Tables', 'tablepress' ) . '</a>'; ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Prints a submit button (only done when function is used as a callback for a text box).
	 *
	 * This method is soft-deprecated. It's no longer used in TablePress, but e.g. in the "TablePress Debug Extension".
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $data Data for this screen.
	 * @param array<string, mixed> $box  Information about the text box.
	 */
	protected function textbox_submit_button( array $data, array $box ): void {
		?>
		<p class="submit"><input type="submit" class="components-button is-primary button-save-changes" value="<?php esc_attr_e( 'Save Changes', 'tablepress' ); ?>"></p>
		<?php
	}

	/**
	 * Returns a safe JSON representation of a variable for printing inside of JavaScript code.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $data Variable to convert to JSON.
	 * @return string Safe JSON representation of a variable for printing inside of JavaScript code.
	 */
	public function convert_to_json_parse_output( /* string|array|bool|int|float|null */ $data ): string {
		$json = wp_json_encode( $data, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			// JSON encoding failed, return an error object. Use a prefixed "_error" key to avoid conflicts with intentionally added "error" keys.
			$json = '{ "_error": "The data could not be encoded to JSON!" }';
		}
		// Print the JSON data inside a `JSON.parse()` call in JS for speed gains, with necessary escaping of `\` and `'`.
		$json = str_replace( array( '\\', "'" ), array( '\\\\', "\'" ), $json );
		return "JSON.parse( '{$json}' )";
	}

	/**
	 * Prints JavaScript variables for the screen.
	 *
	 * @since 3.1.0
	 *
	 * @param string               $variable Name of the JavaScript variable.
	 * @param array<string, mixed> $data     Information about the text box.
	 */
	protected function print_script_data_json( string $variable, array $data ): void {
		echo "<script>\n";
		echo "window.tp = window.tp || {};\n";
		echo "tp.{$variable} = {\n";
		foreach ( $data as $key => $value ) {
			$value = $this->convert_to_json_parse_output( $value );
			echo "\t{$key}: {$value},\n";
		}
		echo "};\n";
		echo "</script>\n";
	}

	/**
	 * Returns the content for the help tab for this screen.
	 *
	 * Has to be implemented for every view that is visible in the WP Dashboard!
	 *
	 * @since 1.0.0
	 *
	 * @return string Help tab content for the view.
	 */
	protected function help_tab_content(): string {
		// Has to be implemented for every view that is visible in the WP Dashboard!
		return '';
	}

	/**
	 * Initializes the WP feature pointers for TablePress.
	 *
	 * @since 1.0.0
	 */
	protected function _init_wp_pointers(): void {
		// Check if there are WP pointers for this view.
		if ( empty( $this->wp_pointers ) ) {
			return;
		}

		// Get dismissed pointers.
		$dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );

		$pointers_on_page = false;
		foreach ( array_diff( $this->wp_pointers, $dismissed ) as $pointer ) {
			// Bind pointer print function.
			add_action( "admin_footer-{$GLOBALS['hook_suffix']}", array( $this, 'wp_pointer_' . $pointer ) ); // @phpstan-ignore argument.type
			$pointers_on_page = true;
		}

		if ( $pointers_on_page ) {
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
		}
	}

} // class TablePress_View
