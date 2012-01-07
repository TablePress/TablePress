<?php
/**
 * List Tables View
 *
 * @package TablePress
 * @subpackage List Tables View
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * List Tables View class
 *
 * @since 1.0.0
 */
class TablePress_List_View extends TablePress_View {

	/**
	 * @var int Number of screen columns for the List View
	 *
	 * @since 1.0.0
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

		$this->add_meta_box( 'support', __( 'Support', 'tablepress' ), array( &$this, 'postbox_support' ), 'side' );
		$this->add_text_box( 'head1', array( &$this, 'textbox_head1' ), 'normal' );
		$this->add_text_box( 'head2', array( &$this, 'textbox_head2' ), 'normal' );
/*
		if ( 0 < $data['tables_count'] )
			$this->add_text_box( 'tables-list', array( &$this, 'textbox_list_of_tables' ), 'normal' );
		else
			$this->add_text_box( 'no-tables', array( &$this, 'textbox_no_tables' ), 'normal' );
*/
		// temporary
		$this->add_text_box( 'submit', array( &$this, 'textbox_submit_button' ), 'normal' );
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
		echo '<a href="' . TablePress::url( array( 'action' => 'edit', 'table_id' => 3 ) ) . '">' . __( 'Edit', 'tablepress' ) . '</a>';
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
		<p><?php printf( __( 'To insert the table into a page, post or text-widget, copy the shortcode <strong>[table id=%s /]</strong> and paste it into the corresponding place in the editor.', 'tablepress' ), '&lt;ID&gt;' ); ?> <?php _e( 'Each table has a unique ID that needs to be adjusted in that shortcode.', 'tablepress' ); ?> <?php printf( __( 'You can also click the button &quot;%s&quot; in the editor toolbar to select and insert a table.', 'tablepress' ), __( 'Table', 'tablepress' ) ); ?></p>
		<?php
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_list_of_tables( $data, $box ) {
		echo "<table>\n";
		foreach ( $data['tables'] as $table ) {
			$table['data'] = print_r( $table['data'], true );
			$edit_link = '<a href="' . TablePress::url( array( 'action' => 'edit', 'table_id' => $table['id'] ) ) . '">' . __( 'Edit', 'tablepress' ) . '</a>';
			$delete_link = '<a href="' . TablePress::url( array( 'action' => 'delete_table', 'item' => $table['id'] ), true, 'admin-post.php' ) . '">' . __( 'Delete', 'tablepress' ) . '</a>';
			$export_link = '<a href="' . TablePress::url( array( 'action' => 'export', 'table_id' => $table['id'] ) ) . '">' . __( 'Export', 'tablepress' ) . '</a>';
			echo "\t";
			printf ( '<tr><td>%1$s</td><td>%2$s</td><td>%3$s</td><td>%4$s</td><td>%5$s</td><td>%6$s</td><td>%7$s</td></tr>', $table['id'], $table['name'], $table['description'], $table['data'], $edit_link, $export_link, $delete_link );
			echo "\n";
			}
		echo "</table>\n";
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function textbox_no_tables( $data, $box ) {
		$add_url = TablePress::url( array( 'action' => 'add' ) );
		$import_url = TablePress::url( array( 'action' => 'import' ) );
		?>
		<p><?php _e( 'No tables were found.', 'tablepress' ); ?></p>
		<p><?php printf( __( 'You should <a href="%s">add</a> or <a href="%s">import</a> a table to get started!', 'tablepress' ), $add_url, $import_url ); ?></p>
		<?php
	}

	/**
	 * Return the content for the help tab for this screen
	 *
	 * @since 1.0.0
	 */
	protected function help_tab_content() {
		return 'Help for the List Tables screen';
	}

} // class TablePress_List_View