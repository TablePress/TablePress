<?php
/**
 * List Tables View
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * List Tables View class
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_List_View extends TablePress_View {

	/**
	 * Number of screen columns for the List View
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $screen_columns = 2;

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

		$this->admin_page->enqueue_script( 'list', array( 'jquery' ), array(
			'list' => array(
				'shortcode_popup' => __( 'To embed this table into a post or page, use this Shortcode:', 'tablepress' )
			)
		) );

		if ( $data['messages']['first_visit'] )
			$this->add_header_message(
				'<strong><em>Welcome!</em></strong><br />Thank you for using TablePress for the first time!<br/>'
				. $this->ajax_link( array( 'action' => 'hide_message', 'item' => 'first_visit', 'return' => 'list' ) , __( 'Hide', 'tablepress' ) )
			);

		if ( $data['messages']['plugin_update'] )
			$this->add_header_message(
				'<strong><em>Thank you for updating to TablePress' . TablePress::version . ' (revision ' . TablePress::db_version . ')</em></strong><br />'
				. $this->ajax_link( array( 'action' => 'hide_message', 'item' => 'plugin_update', 'return' => 'list' ) , __( 'Hide', 'tablepress' ) )
			);

		$this->action_messages = array(
			'success_delete' => __( 'The table was deleted successfully.', 'tablepress' ),
			'error_delete' => __( 'Error: The table could not be deleted.', 'tablepress' ),
			'success_copy' => __( 'The table was copied successfully.', 'tablepress' ),
			'error_copy' => __( 'Error: The table could not be copied.', 'tablepress' ),
			'error_no_table' => __( 'Error: You did not specify a valid table ID.', 'tablepress' ),
			'error_load_table' => __( 'Error: This table could not be loaded!', 'tablepress' )
		);
		if ( $data['message'] && isset( $this->action_messages[ $data['message'] ] ) ) {
			$class = ( in_array( $data['message'], array( 'error_delete', 'error_no_table', 'error_load_table' ) ) ) ? 'error' : 'updated' ;
			$this->add_header_message( "<strong>{$this->action_messages[ $data['message'] ]}</strong>", $class );
		}

		$this->add_meta_box( 'support', __( 'Support', 'tablepress' ), array( &$this, 'postbox_support' ), 'side' );
		$this->add_text_box( 'head1', array( &$this, 'textbox_head1' ), 'normal' );
		$this->add_text_box( 'head2', array( &$this, 'textbox_head2' ), 'normal' );
		$this->add_text_box( 'tables-list', array( &$this, 'textbox_tables_list' ), 'normal' );

	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function postbox_support( $data, $box ) {
		_e( 'These people are proud supporters of TablePress:', 'tablepress' );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_head1( $data, $box ) {
		?>
		<p><?php _e( 'This is a list of all available tables.', 'tablepress' ); ?> <?php _e( 'You may add, edit, copy, delete or preview tables here.', 'tablepress' ); ?></p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_head2( $data, $box ) {
		?>
		<p><?php printf( __( 'To insert the table into a page, post or text-widget, copy the shortcode %s and paste it into the corresponding place in the editor.', 'tablepress' ), '<input type="text" class="table-shortcode table-shortcode-inline" value="[table id=&lt;ID&gt; /]" readonly="readonly" />' ); ?> <?php _e( 'Each table has a unique ID that needs to be adjusted in that shortcode.', 'tablepress' ); ?> <?php printf( __( 'You can also click the button &quot;%s&quot; in the editor toolbar to select and insert a table.', 'tablepress' ), __( 'Table', 'tablepress' ) ); ?></p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_tables_list( $data, $box ) {
	?>
<table id="tablepress-all-tables" class="widefat fixed" cellspacing="0">
<thead>
	<tr>
		<th scope="col" class="check-column"><input type="checkbox" class="hide-if-no-js" /></th>
		<th scope="col" class="table-id">ID<span></span></th>
		<th scope="col">Table Name<span></span></th>
		<th scope="col">Description<span></span></th>
		<th scope="col">Author<span></span></th>
		<th scope="col">Last Modified<span></span></th>
	</tr>
</thead>
<tfoot>
	<tr>
		<th scope="col" class="check-column"><input type="checkbox" class="hide-if-no-js" /></th>
		<th scope="col">ID<span></span></th>
		<th scope="col">Table Name<span></span></th>
		<th scope="col">Description<span></span></th>
		<th scope="col">Author<span></span></th>
		<th scope="col">Last Modified<span></span></th>
	</tr>
</tfoot>
<tbody>
<?php

if ( $data['tables_count'] < 1 ):
	$add_url = TablePress::url( array( 'action' => 'add' ) );
	$import_url = TablePress::url( array( 'action' => 'import' ) );
	echo '<tr class="no-items"><td class="colspanchange" colspan="6">' . __( 'No tables found.', 'tablepress' ) . ' ' . sprintf( __( 'You should <a href="%s">add</a> or <a href="%s">import</a> a table to get started!', 'tablepress' ), $add_url, $import_url ) . '</td></tr>';
else:
	$table_count = 0;
	foreach ( $data['tables'] as $table ) :
		$edit_url = TablePress::url( array( 'action' => 'edit', 'table_id' => $table['id'] ) );
		$delete_url = TablePress::url( array( 'action' => 'delete_table', 'item' => $table['id'], 'return' => 'list', 'return_item' => $table['id'] ), true, 'admin-post.php' );
		$copy_url = TablePress::url( array( 'action' => 'copy_table', 'item' => $table['id'], 'return' => 'list', 'return_item' => $table['id'] ), true, 'admin-post.php' );

		/* $export_link = '<a href="' . TablePress::url( array( 'action' => 'export', 'table_id' => $table['id'] ) ) . '">' . __( 'Export', 'tablepress' ) . '</a>'; */

		$table_count++;
		$row_class = ( 0 == ( $table_count % 2) ) ? ' class="alternate"' : '';
?>
	<tr<?php echo $row_class;?> valign="top">
		<th scope="row" class="check-column"><input type="checkbox" name="post[]" value="1056" /></th>
		<td class="table-id"><?php echo $table['id']; ?></td>
		<td><strong><a title="Edit &#8220;<?php echo $table['name']; ?>&#8221;" class="row-title" href="<?php echo $edit_url; ?>"><?php echo $table['name']; ?></a></strong>
			<div class="row-actions">
				<span class="edit"><a href="<?php echo $edit_url; ?>" title="<?php _e( 'Edit this Table', 'tablepress' ); ?>"><?php _e( 'Edit', 'tablepress' ); ?></a> | </span>
				<span class="shortcode hide-if-no-js"><a href="#" title="<?php echo "[table id={$table['id']} /]"; ?>"><?php _e( 'Shortcode', 'tablepress' ); ?></a> | </span>
				<span class="copy"><a href="<?php echo $copy_url; ?>" title="<?php _e( 'Copy Table', 'tablepress' ); ?>"><?php _e( 'Copy', 'tablepress' ); ?></a> | </span>
				<span class="delete"><a href="<?php echo $delete_url; ?>" title="<?php _e( 'Delete', 'tablepress' ); ?>" class="delete-link"><?php _e( 'Delete', 'tablepress' ); ?></a></span>
			</div>
		</td>
		<td><?php echo $table['description']; ?></td>
		<td><?php echo TablePress::get_last_editor( $table['options']['last_editor'] ); ?></td>
		<td><?php echo TablePress::format_datetime( $table['options']['last_modified'], 'timestamp', '<br/>' ); ?></td>
	</tr>
<?php
	endforeach;
endif;
?>
</tbody>
</table>
	<?php
	} // function

	/**
	 * Return the content for the help tab for this screen
	 *
	 * @since 1.0.0
	 */
	protected function help_tab_content() {
		return 'Help for the List Tables screen';
	}

} // class TablePress_List_View