<?php
/**
 * Editor Button Thickbox List View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Editor Button Thickbox List View class
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Editor_Button_Thickbox_View extends TablePress_View {

	/**
	 * Object for the Editor Button Thickbox List Table.
	 *
	 * @since 1.0.0
	 * @var TablePress_Editor_Button_Thickbox_List_Table
	 */
	protected $wp_list_table;

	/**
	 * Initializes the View class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Intentionally left empty, to void code from parent::__construct().
	}

	/**
	 * Sets up the view with data and do things that are specific for this view.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action for this view.
	 * @param array  $data   Data for this view.
	 */
	public function setup( $action, array $data ) {
		$this->action = $action;
		$this->data = $data;

		$this->wp_list_table = TablePress::load_class( 'TablePress_Editor_Button_Thickbox_List_Table', 'class-editor-button-thickbox-list-table.php', 'views' );

		$this->wp_list_table->set_items( $this->data['table_ids'] );
		$this->wp_list_table->prepare_items();
	}

	/**
	 * Renders the current view.
	 *
	 * @since 1.0.0
	 */
	public function render() {
		_wp_admin_html_begin();

		wp_print_styles( 'colors' );
		wp_print_scripts( 'jquery-core' );
		?>
<title><?php printf( __( '%1$s &lsaquo; %2$s', 'tablepress' ), __( 'List of Tables', 'tablepress' ), 'TablePress' ); ?></title>
<style>
/* Account for .wp-toolbar. */
html {
	padding-top: 0 !important;
}
#tablepress-page {
	margin: 0 15px;
	padding: 0 0 15px 0;
}
#tablepress-page .subtitle {
	float: left;
	padding: 10px 0 0;
}
#tablepress-page p.search-box {
	position: relative;
}
/* Width and font weight for the columns. */
.tablepress-editor-button-list thead .column-table_id {
	width: 50px;
}
.tablepress-editor-button-list tbody .column-table_id,
.tablepress-editor-button-list tbody .column-table_name {
	font-weight: bold;
}
.tablepress-editor-button-list thead .column-table_action {
	min-width: 150px;
}
/* Responsiveness on the All Tables screen. */
@media screen and (max-width: 782px) {
	.tablepress-editor-button-list .column-table_id {
		display: none !important;
		padding: 3px 8px 3px 35%;
	}
}
		<?php if ( is_rtl() ) : ?>
/* RTL CSS */
.rtl #tablepress-page .subtitle {
	float: right;
}
<?php endif; ?>
</style>
</head>
<body class="wp-admin wp-core-ui js iframe<?php echo is_rtl() ? ' rtl' : ''; ?>">
<div id="tablepress-page" class="wrap">
<h1><?php printf( __( '%1$s &lsaquo; %2$s', 'tablepress' ), __( 'List of Tables', 'tablepress' ), 'TablePress' ); ?></h1>
<div id="poststuff">
<p><?php _e( 'This is a list of your tables.', 'tablepress' ); ?> <?php _e( 'You may insert a table into a post or page here.', 'tablepress' ); ?></p>
<p><?php printf( __( 'Click the “%1$s” button for the desired table to automatically insert its Shortcode into the editor.', 'tablepress' ), __( 'Insert Shortcode', 'tablepress' ) ); ?></p>
		<?php
		if ( ! empty( $_GET['s'] ) ) {
			printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;', 'tablepress' ) . '</span>', esc_html( wp_unslash( $_GET['s'] ) ) );
		}
		?>
<form method="get" action="">
	<input type="hidden" name="action" value="tablepress_<?php echo $this->action; ?>" />
		<?php
		wp_nonce_field( TablePress::nonce( $this->action ), '_wpnonce', false );
		$this->wp_list_table->search_box( __( 'Search Tables', 'tablepress' ), 'tables_search' );
		?>
</form>
		<?php $this->wp_list_table->display(); ?>
</div>
</div>
<script>
jQuery( function( $ ) {
	// Toggle list table rows on small screens.
	$( '.tablepress-editor-button-list' )
	.on( 'click', 'tr', function() {
		this.classList.toggle( 'is-expanded' );
	})
	.on( 'click', '.insert-shortcode', function() {
		const win = window.dialogArguments || opener || parent || top;
		win.send_to_editor( this.title );
	} );
} );
</script>
</body>
</html>
		<?php
	}

} // class TablePress_Editor_Button_View

