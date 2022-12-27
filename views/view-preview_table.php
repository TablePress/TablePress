<?php
/**
 * Table Preview View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Table Preview View class
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Preview_Table_View extends TablePress_View {

	/**
	 * Initialize the View class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Intentionally left empty, to void code from parent::__construct().
	}

	/**
	 * Set up the view with data and do things that are specific for this view.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action for this view.
	 * @param array  $data   Data for this view.
	 */
	public function setup( $action, array $data ) {
		$this->action = $action;
		$this->data = $data;
	}

	/**
	 * Render the current view.
	 *
	 * @since 1.0.0
	 */
	public function render() {
		_wp_admin_html_begin();
		?>
<title><?php printf( __( '%1$s &lsaquo; %2$s', 'tablepress' ), __( 'Preview', 'tablepress' ), 'TablePress' ); ?></title>
		<?php echo $this->data['head_html']; ?>
</head>
<body>
<div id="tablepress-page">
<p>
		<?php _e( 'This is a preview of your table.', 'tablepress' ); ?> <?php _e( 'Because of CSS styling in your theme, the table might look different on your page!', 'tablepress' ); ?> <?php _e( 'The Table Features for Site Visitors, like sorting, filtering, and pagination, are also not available in this preview!', 'tablepress' ); ?><br />
		<?php
		// Show the instructions string depending on whether the Block Editor is used on the site or not.
		if ( $this->data['site_uses_block_editor'] ) {
			printf( __( 'To insert a table into a post or page, add a “%1$s” block in the block editor and select the desired table.', 'tablepress' ), __( 'TablePress table', 'tablepress' ) );
		} else {
			_e( 'To insert a table into a post or page, paste its Shortcode at the desired place in the editor.', 'tablepress' );
			echo ' ';
			_e( 'Each table has a unique ID that needs to be adjusted in that Shortcode.', 'tablepress' );
		}
		?>
</p>
		<?php echo $this->data['body_html']; ?>
</div>
</body>
</html>
		<?php
	}

} // class TablePress_Preview_Table_View
