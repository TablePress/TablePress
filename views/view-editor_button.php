<?php
/**
 * Editor Button List View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Editor Button List View class
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Editor_Button_View extends TablePress_View {

	/**
	 * Initialize the View class
	 *
	 * Intentionally left empty, to void code from parent::__construct()
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// intentionally left empty, to void code from parent::__construct()
	}

	/**
	 * Set up the view with data and do things that are specific for this view
	 *
	 * @since 1.0.0
	 *
	 * @param string $action Action for this view
	 * @param array $data Data for this view
	 */
	public function setup( $action, $data ) {
		$this->action = $action;
		$this->data = $data;
	}

	/**
	 * Render the current view
	 *
	 * @since 1.0.0
	 */
	public function render() {
	_wp_admin_html_begin();

	wp_print_styles( 'colors' );
	wp_print_scripts( 'jquery' );
?>
<title><?php printf( __( '%s &lsaquo; TablePress', 'tablepress' ), __( 'List of Tables', 'tablepress' ) ); ?></title>
<style type="text/css">
body {
	margin: 2px 0px 15px 15px;
}

#icon-tablepress {
	background: transparent url( '<?php echo plugins_url( 'admin/tablepress-icon.png', TABLEPRESS__FILE__ ); ?>' ) no-repeat;
}

#tablepress-all-tables thead .table-id {
	width: 50px;
}

#tablepress-all-tables thead .shortcode-button {
	width: 150px;
}

#tablepress-page .table-shortcode-inline {
	background: transparent;
	border: none;
	color: #333333;
	width: 90px;
	margin: 0;
	padding: 0;
	font-weight: bold;
}

#tablepress-page .table-shortcode {
	cursor: text;
}

#tablepress-all-tables td {
	vertical-align: middle;
}
</style>
</head>
<body class="wp-admin js iframe">
<div id="tablepress-page" class="wrap">
<?php screen_icon( 'tablepress' ); ?>
<h2><?php printf( __( '%s &lsaquo; TablePress', 'tablepress' ), __( 'List of Tables', 'tablepress' ) ); ?></h2>
<div id="poststuff">
<p>
<?php _e( 'This is a list of all available tables.', 'tablepress' ); ?> <?php _e( 'You may insert a table into a post or page here.', 'tablepress' ); ?><br />
<?php printf( __( 'Click the &quot;%s&quot; button after the desired table and the corresponding shortcode will be inserted into the editor (%s).', 'tablepress' ), __( 'Insert Shortcode', 'tablepress' ), '<input type="text" class="table-shortcode table-shortcode-inline" value="[' . TablePress::$shortcode . ' id=&lt;ID&gt; /]" readonly="readonly" />' ); ?>
</p>
<table id="tablepress-all-tables" class="widefat fixed" cellspacing="0">
<thead>
	<tr>
		<th scope="col" class="table-id">ID<span></span></th>
		<th scope="col">Table Name<span></span></th>
		<th scope="col">Description<span></span></th>
		<th scope="col" class="shortcode-button">Action<span></span></th>
	</tr>
</thead>
<tfoot>
	<tr>
		<th scope="col">ID<span></span></th>
		<th scope="col">Table Name<span></span></th>
		<th scope="col">Description<span></span></th>
		<th scope="col" class="shortcode-button">Action<span></span></th>
	</tr>
</tfoot>
<tbody>
<?php
if ( $this->data['tables_count'] < 1 ):
	echo '<tr class="no-items"><td class="colspanchange" colspan="4">' . __( 'No tables found.', 'tablepress' ) . ' ' . __( 'You should add or import a table on the TablePress screens to get started!', 'tablepress' ) . '</td></tr>';
else:
	$table_count = 0;
	foreach ( $this->data['tables'] as $table ) :
		$table_count++;
		$row_class = ( 0 == ( $table_count % 2) ) ? ' class="alternate"' : '';
		if ( '' == trim( $table['name'] ) )
			$table['name'] = __( '(no name)', 'tablepress' );
		if ( '' == trim( $table['description'] ) )
			$table['description'] = __( '(no description)', 'tablepress' );
?>
	<tr<?php echo $row_class;?> valign="top">
		<th scope="row" class="table-id"><?php echo esc_html( $table['id'] ); ?></th>
		<td><strong><?php echo esc_html( $table['name'] ); ?></strong></td>
		<td><?php echo esc_html( $table['description'] ); ?></td>
		<td class="shortcode-button"><input type="button" class="insert-shortcode button-secondary" title="<?php echo '[' . TablePress::$shortcode . ' id=' . esc_attr( $table['id'] ) . ' /]'; ?>" value="<?php _e( 'Insert Shortcode', 'tablepress' ); ?>" /></td>
	</tr>
<?php
	endforeach;
endif;
?>
</tbody>
</table>
</div>
</div>
<script type="text/javascript">
	jQuery(document).ready( function($) {
		$( '#tablepress-all-tables' ).on( 'click', '.insert-shortcode', function() {
			var win = window.dialogArguments || opener || parent || top;
			win.send_to_editor( $(this).attr( 'title' ) );
		} );
	} );
</script>
</body>
</html>
<?php
	}

} // class TablePress_Editor_Button_View