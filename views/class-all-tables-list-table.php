<?php
/**
 * List Tables View List Table
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress All Tables List Table class
 *
 * @package TablePress
 * @subpackage Views
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_All_Tables_List_Table extends WP_List_Table {

	/**
	 * Number of items of the initial data set (before sort, search, and pagination).
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $items_count = 0;

	/**
	 * Initialize the List Table.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$screen = get_current_screen();

		// Hide "Last Modified By" column by default.
		if ( false === get_user_option( "manage{$screen->id}columnshidden" ) ) {
			update_user_option( get_current_user_id(), "manage{$screen->id}columnshidden", array( 'table_last_modified_by' ), true );
		}

		parent::__construct( array(
			'singular' => 'tablepress-table',      // Singular name of the listed records.
			'plural'   => 'tablepress-all-tables', // Plural name of the listed records.
			'ajax'     => false,                   // Does this list table support AJAX?
			'screen'   => $screen,                 // WP_Screen object.
		) );
	}

	/**
	 * Set the data items (here: tables) that are to be displayed by the List Tables, and their original count.
	 *
	 * @since 1.0.0
	 *
	 * @param array $items Tables to be displayed in the List Table.
	 */
	public function set_items( array $items ) {
		$this->items = $items;
		$this->items_count = count( $items );
	}

	/**
	 * Check whether the user has permissions for certain AJAX actions.
	 * (not used, but must be implemented in this child class)
	 *
	 * @since 1.0.0
	 *
	 * @return bool true (Default value).
	 */
	public function ajax_user_can() {
		return true;
	}

	/**
	 * Get a list of columns in this List Table.
	 *
	 * Format: 'internal-name' => 'Column Title'.
	 *
	 * @since 1.0.0
	 *
	 * @return array List of columns in this List Table.
	 */
	public function get_columns() {
		$columns = array(
			'cb'                     => $this->has_items() ? '<input type="checkbox" />' : '', // Checkbox for "Select all", but only if there are items in the table.
			// "name" is special in WP, which is why we prefix every entry here, to be safe!
			'table_id'               => __( 'ID', 'tablepress' ),
			'table_name'             => __( 'Table Name', 'tablepress' ),
			'table_description'      => __( 'Description', 'tablepress' ),
			'table_author'           => __( 'Author', 'tablepress' ),
			'table_last_modified_by' => __( 'Last Modified By', 'tablepress' ),
			'table_last_modified'    => __( 'Last Modified', 'tablepress' ),
		);
		return $columns;
	}

	/**
	 * Get a list of columns that are sortable.
	 *
	 * Format: 'internal-name' => array( $field for $item[ $field ], true for already sorted ).
	 *
	 * @since 1.0.0
	 *
	 * @return array List of sortable columns in this List Table.
	 */
	protected function get_sortable_columns() {
		// No sorting on the Empty List placeholder.
		if ( ! $this->has_items() ) {
			return array();
		}

		$sortable_columns = array(
			'table_id'               => array( 'id', true ), // true means its already sorted.
			'table_name'             => array( 'name', false ),
			'table_description'      => array( 'description', false ),
			'table_author'           => array( 'author', false ),
			'table_last_modified_by' => array( 'last_modified_by', false ),
			'table_last_modified'    => array( 'last_modified', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 * @since 1.7.0
	 *
	 * @return string Name of the default primary column, in this case, the table name.
	 */
	protected function get_default_primary_column_name() {
		return 'table_name';
	}

	/**
	 * Render a cell in the "cb" column.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Data item for the current row.
	 * @return string HTML content of the cell.
	 */
	protected function column_cb( /* array */ $item ) {
		// Don't use `array` type hint in method declaration to prevent a Strict Standards notice, as the method is inherited.
		$user_can_copy_table = current_user_can( 'tablepress_copy_table', $item['id'] );
		$user_can_delete_table = current_user_can( 'tablepress_delete_table', $item['id'] );
		$user_can_export_table = current_user_can( 'tablepress_export_table', $item['id'] );

		if ( ! ( $user_can_copy_table || $user_can_delete_table || $user_can_export_table ) ) {
			return '';
		}

		return sprintf(
			'<label><span class="screen-reader-text">%1$s</span><input type="checkbox" name="table[]" value="%2$s" /></label>',
			esc_html__( 'Bulk action selector', 'tablepress' ),
			esc_attr( $item['id'] )
		);
	}

	/**
	 * Render a cell in the "table_id" column.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Data item for the current row.
	 * @return string HTML content of the cell.
	 */
	protected function column_table_id( array $item ) {
		return esc_html( $item['id'] );
	}

	/**
	 * Render a cell in the "table_name" column.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Data item for the current row.
	 * @return string HTML content of the cell.
	 */
	protected function column_table_name( array $item ) {
		$user_can_edit_table = current_user_can( 'tablepress_edit_table', $item['id'] );
		$user_can_copy_table = current_user_can( 'tablepress_copy_table', $item['id'] );
		$user_can_export_table = current_user_can( 'tablepress_export_table', $item['id'] );
		$user_can_delete_table = current_user_can( 'tablepress_delete_table', $item['id'] );
		$user_can_preview_table = current_user_can( 'tablepress_preview_table', $item['id'] );

		$edit_url = TablePress::url( array( 'action' => 'edit', 'table_id' => $item['id'] ) );
		$copy_url = TablePress::url( array( 'action' => 'copy_table', 'item' => $item['id'], 'return' => 'list', 'return_item' => $item['id'] ), true, 'admin-post.php' );
		$export_url = TablePress::url( array( 'action' => 'export', 'table_id' => $item['id'] ) );
		$delete_url = TablePress::url( array( 'action' => 'delete_table', 'item' => $item['id'], 'return' => 'list', 'return_item' => $item['id'] ), true, 'admin-post.php' );
		$preview_url = TablePress::url( array( 'action' => 'preview_table', 'item' => $item['id'], 'return' => 'list', 'return_item' => $item['id'] ), true, 'admin-post.php' );

		if ( '' === trim( $item['name'] ) ) {
			$item['name'] = __( '(no name)', 'tablepress' );
		}

		if ( $user_can_edit_table ) {
			$row_text = '<strong><a title="' . esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'tablepress' ), esc_attr( $item['name'] ) ) ) . '" class="row-title" href="' . $edit_url . '">' . esc_html( $item['name'] ) . '</a></strong>';
		} else {
			$row_text = '<strong>' . esc_html( $item['name'] ) . '</strong>';
		}

		$row_actions = array();
		if ( $user_can_edit_table ) {
			$row_actions['edit'] = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $edit_url, esc_attr( sprintf( __( 'Edit &#8220;%s&#8221;', 'tablepress' ), $item['name'] ) ), __( 'Edit', 'tablepress' ) );
		}
		$row_actions['shortcode hide-if-no-js'] = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', '#', esc_attr( '[' . TablePress::$shortcode . " id={$item['id']} /]" ), __( 'Show Shortcode', 'tablepress' ) );
		if ( $user_can_copy_table ) {
			$row_actions['copy'] = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $copy_url, esc_attr( sprintf( __( 'Copy &#8220;%s&#8221;', 'tablepress' ), $item['name'] ) ), __( 'Copy', 'tablepress' ) );
		}
		if ( $user_can_export_table ) {
			$row_actions['export'] = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $export_url, esc_attr( sprintf( __( 'Export &#8220;%s&#8221;', 'tablepress' ), $item['name'] ) ), _x( 'Export', 'row action', 'tablepress' ) );
		}
		if ( $user_can_delete_table ) {
			$row_actions['delete'] = sprintf( '<a href="%1$s" title="%2$s" class="delete-link">%3$s</a>', $delete_url, esc_attr( sprintf( __( 'Delete &#8220;%s&#8221;', 'tablepress' ), $item['name'] ) ), __( 'Delete', 'tablepress' ) );
		}
		if ( $user_can_preview_table ) {
			$row_actions['table-preview'] = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $preview_url, esc_attr( sprintf( __( 'Preview of table “%1$s” (ID %2$s)', 'tablepress' ), $item['name'], $item['id'] ) ), __( 'Preview', 'tablepress' ) );
		}

		return $row_text . $this->row_actions( $row_actions );
	}

	/**
	 * Render a cell in the "table_description" column.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Data item for the current row.
	 * @return string HTML content of the cell.
	 */
	protected function column_table_description( array $item ) {
		if ( '' === trim( $item['description'] ) ) {
			$item['description'] = __( '(no description)', 'tablepress' );
		}
		return esc_html( $item['description'] );
	}

	/**
	 * Render a cell in the "table_author" column.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Data item for the current row.
	 * @return string HTML content of the cell.
	 */
	protected function column_table_author( array $item ) {
		return TablePress::get_user_display_name( $item['author'] );
	}

	/**
	 * Render a cell in the "last_modified_by" column.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Data item for the current row.
	 * @return string HTML content of the cell.
	 */
	protected function column_table_last_modified_by( array $item ) {
		return TablePress::get_user_display_name( $item['options']['last_editor'] );
	}

	/**
	 * Render a cell in the "table_last_modified" column.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item Data item for the current row.
	 * @return string HTML content of the cell.
	 */
	protected function column_table_last_modified( array $item ) {
		$modified_timestamp = date_create( $item['last_modified'], wp_timezone() );
		$modified_timestamp = $modified_timestamp->getTimestamp();
		$current_timestamp = time();
		$time_diff = $current_timestamp - $modified_timestamp;
		// Time difference is only shown up to one week.
		if ( $time_diff >= 0 && $time_diff < WEEK_IN_SECONDS ) {
			$time_diff = sprintf( __( '%s ago', 'default' ), human_time_diff( $modified_timestamp, $current_timestamp ) );
		} else {
			$time_diff = TablePress::format_datetime( $item['last_modified'], '<br />' );
		}
		$readable_time = TablePress::format_datetime( $item['last_modified'] );
		return '<abbr title="' . esc_attr( $readable_time ) . '">' . $time_diff . '</abbr>';
	}

	/**
	 * Handles output for the default column.
	 *
	 * @since 1.8.0
	 *
	 * @param array  $item        Data item for the current row.
	 * @param string $column_name Current column name.
	 */
	protected function column_default( /* array */ $item, $column_name ) {
		// Don't use `array` type hint in method declaration to prevent a Strict Standards notice, as the method is inherited.
		/**
		 * Fires inside each custom column of the TablePress list table.
		 *
		 * @since 1.8.0
		 *
		 * @param array  $column_name Current column name.
		 * @param string $item        Data item for the current row.
		 */
		do_action( 'manage_tablepress_list_custom_column', $column_name, $item );
	}

	/**
	 * Generates and display row actions links for the list table.
	 *
	 * The "Table Name" column already gets these, so return an empty string here to prevent them from being duplicated.
	 *
	 * @since 2.0.0
	 *
	 * @param object|array $item        The item being acted upon.
	 * @param string       $column_name Current column name.
	 * @param string       $primary     Primary column name.
	 * @return string The row actions HTML, or an empty string
	 *                if the current column is not the primary column.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		return '';
	}

	/**
	 * Get a list (name => title) bulk actions that are available.
	 *
	 * @since 1.0.0
	 *
	 * @return array Bulk actions for this table.
	 */
	protected function get_bulk_actions() {
		$bulk_actions = array();

		if ( current_user_can( 'tablepress_copy_tables' ) ) {
			$bulk_actions['copy'] = _x( 'Copy', 'bulk action', 'tablepress' );
		}
		if ( current_user_can( 'tablepress_export_tables' ) ) {
			$bulk_actions['export'] = _x( 'Export', 'bulk action', 'tablepress' );
		}
		if ( current_user_can( 'tablepress_delete_tables' ) ) {
			$bulk_actions['delete'] = _x( 'Delete', 'bulk action', 'tablepress' );
		}

		return $bulk_actions;
	}

	/**
	 * Render the bulk actions dropdown.
	 *
	 * In comparison with parent class, this has modified HTML (especially no field named "action" as that's being used already)!
	 *
	 * @since 1.0.0
	 *
	 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
	 *                      This is designated as optional for backwards-compatibility.
	 */
	protected function bulk_actions( $which = '' ) {
		if ( is_null( $this->_actions ) ) {
			$this->_actions = $this->get_bulk_actions();
			$no_new_actions = $this->_actions;
			/** This filter is documented in the WordPress function WP_List_Table::bulk_actions() in wp-admin/includes/class-wp-list-table.php */
			$this->_actions = apply_filters( 'bulk_actions-' . $this->screen->id, $this->_actions ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			$this->_actions = array_intersect_assoc( $this->_actions, $no_new_actions );
			$two = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) ) {
			return;
		}

		$name_id = "bulk-action-selector-{$which}";
		echo "<label for='{$name_id}' class='screen-reader-text'>" . __( 'Select Bulk Action', 'tablepress' ) . "</label>\n";
		echo "<select name='{$name_id}' id='{$name_id}'>\n";
		echo "<option value='-1' selected='selected'>" . __( 'Bulk Actions', 'tablepress' ) . "</option>\n";
		foreach ( $this->_actions as $name => $title ) {
			echo "\t<option value='{$name}'>{$title}</option>\n";
		}
		echo "</select>\n";
		submit_button( __( 'Apply', 'tablepress' ), 'action', '', false, array( 'id' => "doaction{$two}" ) );
		echo "\n";
	}

	/**
	 * Holds the message to be displayed when there are no items in the table.
	 *
	 * @since 1.0.0
	 */
	public function no_items() {
		_e( 'No tables found.', 'tablepress' );
		if ( 0 === $this->items_count ) {
			$user_can_add_tables = current_user_can( 'tablepress_add_tables' );
			$user_can_import_tables = current_user_can( 'tablepress_import_tables' );

			$add_url = TablePress::url( array( 'action' => 'add' ) );
			$import_url = TablePress::url( array( 'action' => 'import' ) );

			if ( $user_can_add_tables && $user_can_import_tables ) {
				echo ' ' . sprintf( __( 'You should <a href="%1$s">add</a> or <a href="%2$s">import</a> a table to get started!', 'tablepress' ), $add_url, $import_url );
			} elseif ( $user_can_add_tables ) {
				echo ' ' . sprintf( __( 'You should <a href="%s">add</a> a table to get started!', 'tablepress' ), $add_url );
			} elseif ( $user_can_import_tables ) {
				echo ' ' . sprintf( __( 'You should <a href="%s">import</a> a table to get started!', 'tablepress' ), $import_url );
			}
		}
	}

	/**
	 * Generate the elements above or below the table (like bulk actions and pagination).
	 *
	 * In comparison with parent class, this has no nonce field in its HTML code and a one-time filter around the pagination call, to change a string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $which Location ("top" or "bottom").
	 */
	protected function display_tablenav( $which ) {
		?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<?php if ( $this->has_items() ) : ?>
		<div class="alignleft actions bulkactions">
			<?php $this->bulk_actions( $which ); ?>
		</div>
			<?php
		endif;
		$this->extra_tablenav( $which );

		add_filter( 'ngettext_default', array( $this, 'change_pagination_items_string' ), 10, 5 );
		$this->pagination( $which );
		remove_filter( 'ngettext_default', array( $this, 'change_pagination_items_string' ), 10, 5 );
		?>

		<br class="clear" />
	</div>
		<?php
	}

	/**
	 * Replaces the "%s item/%s items" string in the pagination with "%s table/%s tables".
	 *
	 * @since 2.0.0
	 *
	 * @param string $translation The current translation of a singular or plural form.
	 * @param string $single      The text to be used if the number is singular.
	 * @param string $plural      The text to be used if the number is plural.
	 * @param int    $number      The number to compare against to use either the singular or plural form.
	 * @param string $domain      Text domain. Defaults to 'default'.
	 * @return string The changed translation.
	 */
	public function change_pagination_items_string( $translation, $single, $plural, $number, $domain ) {
		if ( '%s item' === $single && '%s items' === $plural ) {
			$translation = _n( '%s table', '%s tables', $number, 'tablepress' );
		}
		return $translation;
	}

	/**
	 * Callback to determine whether the given $item contains the search term.
	 *
	 * @since 1.0.0
	 *
	 * @param string $item Table ID that shall be searched.
	 * @return bool Whether the search term was found or not.
	 */
	protected function _search_callback( $item ) {
		static $term;
		static $json_encoded_term;
		if ( is_null( $term ) || is_null( $json_encoded_term ) ) {
			$term = wp_unslash( $_GET['s'] );
			$json_encoded_term = substr( wp_json_encode( $term, TABLEPRESS_JSON_OPTIONS ), 1, -1 );
		}

		static $debug;
		if ( is_null( $debug ) ) {
			// Set debug variable to allow searching in corrupted tables.
			$debug = isset( $_GET['debug'] ) ? ( 'true' === $_GET['debug'] ) : WP_DEBUG;
		}

		// load table again, with data and options (for last_editor).
		$item = TablePress::$model_table->load( $item, true, true );

		// Don't search corrupted tables, except when debug mode is enabled via $_GET parameter or WP_DEBUG constant.
		if ( ! $debug && isset( $item['is_corrupted'] ) && $item['is_corrupted'] ) {
			return false;
		}

		// Search from easy to hard, so that "expensive" code maybe doesn't have to run.
		if ( false !== stripos( $item['id'], $term )
		|| false !== stripos( $item['name'], $term )
		|| false !== stripos( $item['description'], $term )
		|| false !== stripos( TablePress::get_user_display_name( $item['author'] ), $term )
		|| false !== stripos( TablePress::get_user_display_name( $item['options']['last_editor'] ), $term )
		|| false !== stripos( TablePress::format_datetime( $item['last_modified'] ), $term )
		|| false !== stripos( wp_json_encode( $item['data'], TABLEPRESS_JSON_OPTIONS ), $json_encoded_term ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Callback to for the array sort function.
	 *
	 * @since 1.0.0
	 *
	 * @param array $item_a First item that shall be compared to.
	 * @param array $item_b The second item for the comparison.
	 * @return int (-1, 0, 1) depending on which item sorts "higher".
	 */
	protected function _order_callback( array $item_a, array $item_b ) {
		global $orderby, $order;

		if ( 'last_modified_by' !== $orderby ) {
			if ( $item_a[ $orderby ] === $item_b[ $orderby ] ) {
				return 0;
			}
		} else {
			if ( $item_a['options']['last_editor'] === $item_b['options']['last_editor'] ) {
				return 0;
			}
		}

		// Certain fields require some extra work before being sortable.
		switch ( $orderby ) {
			case 'last_modified':
				// Compare UNIX timestamps for "last modified", which actually is a mySQL datetime string.
				$result = ( strtotime( $item_a['last_modified'] ) > strtotime( $item_b['last_modified'] ) ) ? 1 : -1;
				break;
			case 'author':
				// Get the actual author name, plain value is just the user ID.
				$result = strnatcasecmp( TablePress::get_user_display_name( $item_a['author'] ), TablePress::get_user_display_name( $item_b['author'] ) );
				break;
			case 'last_modified_by':
				// Get the actual last editor name, plain value is just the user ID.
				$result = strnatcasecmp( TablePress::get_user_display_name( $item_a['options']['last_editor'] ), TablePress::get_user_display_name( $item_b['options']['last_editor'] ) );
				break;
			default:
				// Other fields (ID, name, description) are sorted as strings.
				$result = strnatcasecmp( $item_a[ $orderby ], $item_b[ $orderby ] );
		}

		return ( 'asc' === $order ) ? $result : - $result;
	}

	/**
	 * Prepares the list of items for displaying, by maybe searching and sorting, and by doing pagination.
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {
		global $orderby, $order, $s;
		wp_reset_vars( array( 'orderby', 'order', 's' ) );

		// Maybe search in the items.
		if ( $s ) {
			$this->items = array_filter( $this->items, array( $this, '_search_callback' ) );
		}

		// Load actual tables after search for less memory consumption.
		foreach ( $this->items as &$item ) {
			// Don't load data, but load table options for access to last_editor.
			$item = TablePress::$model_table->load( $item, false, true );
		}
		// Break reference in foreach iterator.
		unset( $item );

		// Maybe sort the items.
		$_sortable_columns = $this->get_sortable_columns();
		if ( $orderby && ! empty( $this->items ) && isset( $_sortable_columns[ "table_{$orderby}" ] ) ) {
			usort( $this->items, array( $this, '_order_callback' ) );
		}

		// Number of records to show per page.
		$per_page = $this->get_items_per_page( 'tablepress_list_per_page', 20 ); // Hard-coded, as in filter in Admin_Controller.
		// Page number the user is currently viewing.
		$current_page = $this->get_pagenum();
		// Number of records in the array.
		$total_items = count( $this->items );

		// Slice items array to hold only items for the current page.
		$this->items = array_slice( $this->items, ( ( $current_page - 1 ) * $per_page ), $per_page );

		// Register pagination options and calculation results.
		$this->set_pagination_args( array(
			'total_items' => $total_items, // Total number of records/items.
			'per_page'    => $per_page, // Number of items per page.
			'total_pages' => ceil( $total_items / $per_page ), // Total number of pages.
		) );
	}

} // class TablePress_All_Tables_List_Table
