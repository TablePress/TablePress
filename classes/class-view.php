<?php
/**
 * TablePress Base View with members and methods for all views
 *
 * @package TablePress
 * @subpackage TablePress Base View
 * @author Tobias Bäthge
 * @since 1.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Base View class
 */
abstract class TablePress_View {

	/*
	 * @var array Data for the view
	 */
	protected $data = array();

	/*
	 * @var int Number of screen columns for post boxes
	 */
	protected $screen_columns = 0;

	/*
	 * @var string User action for this screen
	 */	
	protected $action = '';

	/*
	 * @var string Title for this screen (next to the screen icon)
	 */	
	protected $page_title = '';

	/*
	 * @var object Instance of the Admin Page Helper Class, with necessary functions
	 */	
	protected $admin_page;

	/*
	 * @var array List of text boxes (similar to post boxes, but just with text and without extra functionality)
	 */	
	protected $textboxes = array();

	/*
	 * @var List of messages that are to be displayed as boxes below the page title
	 */	
	protected $header_messages = array();

	/*
	 * @var bool Whether there are post boxes registered for this screen
	 */	
	protected $has_meta_boxes = false; // is automatically set to true, when a meta box is added

	/*
	 * Initialize the View class, by setting the correct screen columns and adding help texts 
	 */	
	public function __construct() {
		$screen = get_current_screen();
		if ( 0 != $this->screen_columns )
			$screen->add_option( 'layout_columns', array( 'max' => $this->screen_columns ) );
		add_filter( "get_user_option_screen_layout_{$screen->id}", array( &$this, 'set_current_screen_layout_columns' ) ); // enable two column layout

		// add help tab
		$screen->add_help_tab( array(
			'id' => 'tablepress-help', // This should be unique for the screen.
			'title' => __( 'TablePress Help', 'tablepress' ),
			'content' => '<p>' . $this->help_tab_content() . '</p>'
		) );
		// "sidebar" in the help tab
		$screen->set_help_sidebar( '<p><strong>' . __( 'For more information:', 'tablepress' ) . '</strong></p><p><a href="http://tobias.baethge.com/wordpress/plugins/tablepress/" target="_blank">TablePress Website</a></p><p><a href="http://tobias.baethge.com/wordpress/plugins/tablepress/faq/" target="_blank">TablePress FAQ</a></p><p><a href="http://tobias.baethge.com/wordpress/plugins/tablepress/documentation/" target="_blank">TablePress Documentation</a></p><p><a href="http://tobias.baethge.com/wordpress/plugins/tablepress/support/" target="_blank">TablePress Support</a></p>' );
	}

	/*
	 * Change the value of the user option "screen_layout_{$screen->id}" through a filter
	 *
	 * @param int|bool Current value of the user option
	 * @return int New value for the user option
	 */	
	public function set_current_screen_layout_columns( $result ) {
		if ( false === $result || $result > $this->screen_columns )
			$result = $this->screen_columns;
		return $result;
	}

	/*
	 * Set up the view with data and do things that are necessary for all views
	 *
	 * @param string $action Action for this view
	 * @param array $data Data for this view
	 */
	public function setup( $action, $data ) {
		$this->action = $action;
		$this->data = $data;

		// admin page helpers, like script/style loading, could be moved to view
		$this->admin_page = TablePress::load_class( 'TablePress_Admin_Page', 'class-admin-page-helper.php', 'classes' );
		$this->admin_page->enqueue_style( 'common' );
		$this->admin_page->enqueue_script( 'common', array( 'jquery', 'postbox' ) );
		$this->admin_page->add_admin_footer_text();
		
		// necessary fields for all views
		$this->add_text_box( 'default_nonce_fields', array( &$this, 'default_nonce_fields' ), 'header', false );
		$this->add_text_box( 'action_nonce_field', array( &$this, 'action_nonce_field' ), 'header', false );
		$this->add_text_box( 'action_field', array( &$this, 'action_field' ), 'header', false );
	}

	/*
	 * Register a header message for the view
	 *
	 * @param string $text Text for the header message
	 * @param string $class (optional) Additional CSS class for the header message
	 */	
	public function add_header_message( $text, $class = 'updated' ) {
		$this->header_messages[] = "<div class=\"{$class}\"><p>{$text}</p></div>\n";
	}

	/*
	 * Register a text box for the view
	 *
	 * @param string $id Unique HTML ID for the text box container (only visible with $wrap = true)
	 * @param string $callback Callback that prints the contents of the text box
	 * @param string $context (optional) Context/position of the text box (normal, side, additional, header, submit)
	 * @param bool $wrap Whether the content of the text box shall be wrapped in a <div> container
	 */
	public function add_text_box( $id, $callback, $context = 'normal', $wrap = true ) {
		if ( ! isset( $this->textboxes[ $context ] ) )
			$this->textboxes[ $context ] = array();

		$long_id = "tablepress_{$this->action}-{$id}";
		$this->textboxes[ $context ][ $id ] = array(
			'id' => $long_id,
			'callback' => $callback,
			'context' => $context,
			'wrap' => $wrap
		);
	}

	/*
	 * Register a post meta box for the view, that is drag/droppable with WordPress functionality
	 *
	 * @param string $id Unique ID for the meta box
	 * @param string $title Title for the meta box
	 * @param string $callback Callback that prints the contents of the post meta box
	 * @param string $context (optional) Context/position of the post meta box (normal, side, additional)
	 * @param string $priority (optional) Order of the post meta box for the $context position (high, default, low) 
	 * @param bool $callback_args (optional) Additional data for the callback function (e.g. useful when in different class)
	 * @uses add_meta_box()
	 */
	public function add_meta_box( $id, $title, $callback, $context = 'normal', $priority = 'default', $callback_args = null ) {
		$this->has_meta_boxes = true;
		add_meta_box( "tablepress_{$this->action}-{$id}", $title, $callback, null, $context, $priority, $callback_args );
	}

	/*
	 * Render all text boxes for the given context
	 *
	 * @param string $context Context (normal, side, additional, header, submit) for which registered text boxes shall be rendered
	 */
	protected function do_text_boxes( $context ) {
		if ( empty( $this->textboxes[ $context ] ) )
			return;

		foreach ( $this->textboxes[ $context ] as $box ) {
			if ( $box['wrap'] )
				echo "<div id=\"{$box['id']}\" class=\"textbox\">\n";
			call_user_func( $box['callback'], $this->data, $box );
			if ( $box['wrap'] )
				echo "</div>\n";
		}
	}

	/*
	 * Render all post meta boxes for the given context, if there are post meta boxes
	 *
	 * @param string $context Context (normal, side, additional) for which registered post meta boxes shall be rendered#
	 * @uses do_meta_boxes()
	 */
	protected function do_meta_boxes( $context ) {
		if ( ! $this->has_meta_boxes )
			return;
		do_meta_boxes( null, $context, $this->data );
	}

	/*
	 * Print hidden fields with nonces for post meta box AJAX handling, if there are post meta boxes on the screen
	 * (check is possible as this function is executed after post meta boxes have to be registered)
	 *
	 * @param array $data Data for this screen
	 * @param array $box Information about the text box
	 * @uses wp_nonce_field()
	 */
	protected function default_nonce_fields( $data, $box ) {
		if ( ! $this->has_meta_boxes )
			return;
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); echo "\n";
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); echo "\n";
	}

	/*
	 * Print hidden field with a nonce for the screen's action, to be transmitted in HTTP requests
	 *
	 * @param array $data Data for this screen
	 * @param array $box Information about the text box
	 * @uses wp_nonce_field()
	 */
	protected function action_nonce_field( $data, $box ) {
		wp_nonce_field( TablePress::nonce( $this->action ) ); echo "\n";
	}

	/*
	 * Print hidden field with the screen action
	 *
	 * @param array $data Data for this screen
	 * @param array $box Information about the text box
	 * @uses wp_nonce_field()
	 */
	protected function action_field( $data, $box ) {
		echo "<input type=\"hidden\" name=\"action\" value=\"tablepress_{$this->action}\" />\n";
	}

	/*
	 * Render the current view
	 *
	 * @param string|bool $form_action Target for the form submission, default: admin-post.php
	 */
	public function render( $form_action = false ) {
		if ( ! $form_action )
			$form_action = admin_url( 'admin-post.php' );
		?>
		<div id="tablepress-page" class="wrap">
		<?php screen_icon( 'tablepress' ); ?>
		<!--<h2><?php echo esc_html( $this->page_title ); ?></h2>-->
		<?php
			$this->print_navigation();
			// print all header messages
			foreach ( $this->header_messages as $message ) {
				echo $message;
			}
		?>
		<form action="<?php echo $form_action; ?>" method="post" enctype="multipart/form-data">
			<?php
			$this->do_text_boxes( 'header' );

			//$this->print_submenu_navigation();
			?>
			<div id="poststuff" class="metabox-holder<?php echo ( 2 == $GLOBALS['screen_layout_columns'] ) ? ' has-right-sidebar' : ''; ?>">
				<div id="side-info-column" class="inner-sidebar">
				<?php
					// print all boxes in the sidebar
					$this->do_text_boxes( 'side' );
					$this->do_meta_boxes( 'side' );
				?>
				</div>
				<div id="post-body">
					<div id="post-body-content">
					<?php
					$this->do_text_boxes( 'normal' );
					$this->do_meta_boxes( 'normal' );

					$this->do_text_boxes( 'additional' );
					$this->do_meta_boxes( 'additional' );

					// print all submit buttons
					$this->do_text_boxes( 'submit' );
					?>
					</div>
				</div>
				<br class="clear" />
			</div>
		</form>
		</div>
		<?php
	}

	/**
	 * Render the submenu navigation with links to the possible actions, highlighting the current one,
	 * separated into table actions (List, Add, Import, Export) and plugin actions (Options, About, Debug)
	 */
	protected function print_submenu_navigation() {
		?>
		<ul class="subsubsub submenu-table-actions">
			<?php
			$table_actions = array(
				'list' =>  __( 'List Tables', 'tablepress' ),
				'add' =>  __( 'Add new Table', 'tablepress' ),
				'import' => __( 'Import a Table', 'tablepress' ),
				'export' => __( 'Export a Table', 'tablepress' )
			);
			$table_actions = apply_filters( 'tablepress_admin_table_actions', $table_actions );
			foreach ( $table_actions as $action => $name ) {
				$url = esc_url( TablePress::url( array( 'action' => $action ), false ) );
				$class = ( $action == $this->action ) ? ' class="current"' : '';
				$bar = ( end( $table_actions ) != $name ) ? ' | ' : '';
				echo "<li><a{$class} href=\"{$url}\">{$name}</a>{$bar}</li>";
			}
			?>
		</ul>
		<ul class="subsubsub submenu-plugin-actions">
			<?php
			$plugin_actions = array(
				'options' => __( 'Plugin Options', 'tablepress' ),
				'about' => __( 'About TablePress', 'tablepress' ),
				'debug' => __( 'Debug', 'tablepress' ) // temporary
			);
			$plugin_actions = apply_filters( 'tablepress_admin_plugin_actions', $plugin_actions );
			foreach ( $plugin_actions as $action => $name ) {
				$url = esc_url( TablePress::url( array( 'action' => $action ), false ) );
				$class = ( $action == $this->action ) ? ' class="current"' : '';
				$bar = ( end( $plugin_actions ) != $name ) ? ' | ' : '';
				echo "<li><a{$class} href=\"{$url}\">{$name}</a>{$bar}</li>";
			}
			?>
		</ul>
		<br class="clear" />
		<?php
	}

	/**
	 * Render the navigation menu with links to the possible actions, highlighting the current one,
	 * separated into table actions (List, Add, Import, Export) and plugin actions (Options, About, Debug)
	 */
	protected function print_navigation() {
		?>
		<h2 id="tablepress-nav" class="nav-tab-wrapper">
			<?php
			echo __( 'TablePress', 'tablepress' );
			echo '<span class="separator"></span>';
			$table_actions = array(
				'list' =>  __( 'List Tables', 'tablepress' ),
				'add' =>  __( 'Add new Table', 'tablepress' ),
				'import' => __( 'Import a Table', 'tablepress' ),
				'export' => __( 'Export a Table', 'tablepress' )
			);
			$table_actions = apply_filters( 'tablepress_admin_table_actions', $table_actions );
			foreach ( $table_actions as $action => $name ) {
				$url = esc_url( TablePress::url( array( 'action' => $action ), false ) );
				$class = ( $action == $this->action ) ? ' nav-tab-active' : '';
				$bar = ( end( $table_actions ) != $name ) ? ' | ' : '';
				echo "<a class=\"nav-tab{$class}\" href=\"{$url}\">{$name}</a>";
			}
			echo '<span class="separator"></span>';
			echo '<span class="separator"></span>';
			$plugin_actions = array(
				'options' => __( 'Plugin Options', 'tablepress' ),
				'about' => __( 'About TablePress', 'tablepress' ),
				'debug' => __( 'Debug', 'tablepress' ) // temporary
			);
			$plugin_actions = apply_filters( 'tablepress_admin_plugin_actions', $plugin_actions );
			foreach ( $plugin_actions as $action => $name ) {
				$url = esc_url( TablePress::url( array( 'action' => $action ), false ) );
				$class = ( $action == $this->action ) ? ' nav-tab-active' : '';
				$bar = ( end( $plugin_actions ) != $name ) ? ' | ' : '';
				echo "<a class=\"nav-tab{$class}\" href=\"{$url}\">{$name}</a>";
			}
			?>
		</h2>
		<?php
	}

	/*
	 * Print a submit button (only done when function is used as a callback for a text box)
	 */
	protected function textbox_submit_button( $data, $box ) {
		$caption = isset( $data['submit_button_caption'] ) ? $data['submit_button_caption'] : __( 'Save Changes', 'tablepress' );
		?>
		<p class="submit"><input type="submit" value="<?php echo esc_attr( $caption ); ?>" class="button-primary" name="submit" /></p>
		<?php
	}

	/*
	 * Create HTML code for an AJAXified link
	 *
	 * @param array $params Parameters for the URL
	 * @param string $text Text for the link
	 * @return string HTML code for the link
	 */
	protected function ajax_link( $params = array( 'action' => 'list', 'item' => '' ), $text ) {
		$action = esc_attr( $params['action'] );
		$item = esc_attr( $params['item'] );
		$url = esc_url( TablePress::url( $params, true, 'admin-post.php' ) );
		return "<a id=\"{$action}-{$item}\" class=\"ajax-link {$action}\" href=\"{$url}\">{$text}</a>";
	}

	/*
	 * Return the content for the help tab for this screen
	 *
	 * To be implemented in every derived class of this base class
	 */
	abstract protected function help_tab_content();
	
} // class TablePress_View